<?php
/**
 * Plugin Name: Portabilidade no WooCommerce
 * Plugin URI: https://cefet-rj.br
 * Description: Plugin responsavel por transferir os dados de um usuario para outro site com woocommerce respeitando o direito da LGPD.
 * Version: 1.0
 * Author: João Guylherme e Celso Junior
 * Author URI: github.com/JGuylherme e github.com/CelsoDamesJunior
 */

// Scripts e CSS's
function wdei_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('wdei-script', plugin_dir_url(__FILE__) . 'js/wdei-script.js', array('jquery'), '1.0', true);
    wp_enqueue_style('wdei-style', plugin_dir_url(__FILE__) . 'css/wdei-style.css');
}
add_action('wp_enqueue_scripts', 'wdei_enqueue_scripts');

// Exportar dados do Usuário
function wdei_export_user_data() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();

        $dados_usuario = array(
            'email' => $user->user_email,
            'nome' => $user->display_name,
            'telefone' => get_user_meta($user->ID, 'phone', true),
            'enderecoFaturamento' => array(
                'nome' => get_user_meta($user->ID, 'billing_first_name', true),
                'sobrenome' => get_user_meta($user->ID, 'billing_last_name', true),
                'rua' => get_user_meta($user->ID, 'billing_address_1', true),
                'numero' => get_user_meta($user->ID, 'billing_address_2', true),
                'cidade' => get_user_meta($user->ID, 'billing_city', true),
                'estado' => get_user_meta($user->ID, 'billing_state', true),
                'cep' => get_user_meta($user->ID, 'billing_postcode', true)
            ),
            'enderecoEntrega' => array(
                
                'nome' => get_user_meta($user->ID, 'shipping_first_name', true),
                'sobrenome' => get_user_meta($user->ID, 'shipping_last_name', true),
                'rua' => get_user_meta($user->ID, 'shipping_address_1', true),
                'numero' => get_user_meta($user->ID, 'shipping_address_2', true),
                'cidade' => get_user_meta($user->ID, 'shipping_city', true),
                'estado' => get_user_meta($user->ID, 'shipping_state', true),
                'cep' => get_user_meta($user->ID, 'shipping_postcode', true)
            ),
            'historico_compras' => array()
        );

        $pedidos = wc_get_orders(array(
            'customer' => $user->ID,
            'status' => 'completed'
        ));

        foreach ($pedidos as $pedido) {
            $dados_pedido = array(
                'numero_pedido' => $pedido->get_order_number(),
                'status' => $pedido->get_status(),
                'total' => $pedido->get_total(),
                'data' => $pedido->get_date_created()->format('Y-m-d'),
                'produtos' => array()
            );

            $line_items = $pedido->get_items();

        foreach ($line_items as $item_id => $item) {
            $product = $item->get_product();
            $product_name = $product->get_name();
            $product_quantity = $item->get_quantity();
            $dados_produto = array(
                'nome' => $product_name,
                'quantidade' => $product_quantity
            );
            $dados_pedido['produtos'][] = $dados_produto;
        }

            $dados_usuario['historico_compras'][] = $dados_pedido;
        }

        $json = json_encode($dados_usuario);
        $senha = hash('sha256', $user->display_name);


        $encrypted_data = encriptografar_json($json, $senha);

        $popup_html = '<div id="wdei-popup-container">';
        $popup_html .= '<div id="wdei-popup">';
        $popup_html .= '<p>Sua senha de descriptografia é: <strong>' . $senha . '</strong></p>';
        $popup_html .= '</div>';
        $popup_html .= '</div>';
        $popup_html .= '<script>';
        $popup_html .= 'function downloadEncryptedData() {';
        $popup_html .= '   var link = document.createElement("a");';
        $popup_html .= '   link.href = "' . plugin_dir_url(__FILE__) . 'download.php?data=' . base64_encode($encrypted_data) . '&filename=dados_usuario.json";';
        $popup_html .= '   link.download = "dados_usuario.json";';
        $popup_html .= '   link.click();';
        $popup_html .= '   alert("Senha de descriptografia: ' . $senha . '");';
        $popup_html .= '   var popupContainer = document.getElementById("wdei-popup-container");';
        $popup_html .= '   popupContainer.parentNode.removeChild(popupContainer);';
        $popup_html .= '   history.go(-1);'; // Redirect back to previous page
        $popup_html .= '}';
        $popup_html .= 'window.onload = downloadEncryptedData;';
        $popup_html .= '</script>';

        echo $popup_html;
    } else {
        echo '<p>Faça login para exportar seus dados.</p>';
    }
}
add_shortcode('wdei_export_button', 'wdei_export_user_data');

// Importar dados do Usuário
function wdei_import_user_data() {
    if (isset($_FILES['wdei_import_file']) && isset($_POST['wdei_import_password'])) {
        $file = $_FILES['wdei_import_file'];
        $password = $_POST['wdei_import_password'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $encrypted_data = file_get_contents($file['tmp_name']);

            $json = descriptografar_json($encrypted_data, $password);

            if ($json !== false) {
                $dados_usuario = json_decode($json, true);

                // Puxar dados do Usuario logado
                $user = wp_get_current_user();
                $user_id = $user->ID;

                // Atualizar os dados meta (endereço e telefone) do usuario Logado
                update_user_meta($user_id, 'billing_first_name', $dados_usuario['enderecoFaturamento']['nome']);
                update_user_meta($user_id, 'billing_last_name', $dados_usuario['enderecoFaturamento']['sobrenome']);
                update_user_meta($user_id, 'phone', $dados_usuario['telefone']);
                update_user_meta($user_id, 'billing_address_1', $dados_usuario['enderecoFaturamento']['rua']);
                update_user_meta($user_id, 'billing_address_2', $dados_usuario['enderecoFaturamento']['numero']);
                update_user_meta($user_id, 'billing_city', $dados_usuario['enderecoFaturamento']['cidade']);
                update_user_meta($user_id, 'billing_state', $dados_usuario['enderecoFaturamento']['estado']);
                update_user_meta($user_id, 'billing_postcode', $dados_usuario['enderecoFaturamento']['cep']);

                update_user_meta($user_id, 'shipping_first_name', $dados_usuario['enderecoEntrega']['nome']);
                update_user_meta($user_id, 'shipping_last_name', $dados_usuario['enderecoEntrega']['sobrenome']);
                update_user_meta($user_id, 'shipping_address_1', $dados_usuario['enderecoEntrega']['rua']);
                update_user_meta($user_id, 'shipping_address_2', $dados_usuario['enderecoEntrega']['numero']);
                update_user_meta($user_id, 'shipping_city', $dados_usuario['enderecoEntrega']['cidade']);
                update_user_meta($user_id, 'shipping_state', $dados_usuario['enderecoEntrega']['estado']);
                update_user_meta($user_id, 'shipping_postcode', $dados_usuario['enderecoEntrega']['cep']);


                // Atualizar o dados do usuario atual
                wp_update_user(array(
                    'ID' => $user_id,
                    'user_email' => $dados_usuario['email'],
                    'display_name' => $dados_usuario['nome']
                ));

                function get_product_by_name($name) {
                    $args = array(
                        'post_type'      => 'product',
                        'post_status'    => 'publish',
                        'posts_per_page' => 1,
                        'meta_query'     => array(
                            array(
                                'key'     => '_name',
                                'value'   => $name,
                                'compare' => '=',
                            )
                        )
                    );

                    $query = new WP_Query($args);

                    if ($query->have_posts()) {
                        $query->the_post();
                        return wc_get_product(get_the_ID());
                    }

                    return null;
                }
                // Atualiza os dados de pedidos do usuario
                $historico_compras = $dados_usuario['historico_compras'];

                foreach ($historico_compras as $compra) {
                    $order = wc_create_order();
                    $order->set_customer_id($user_id);
                    $order->set_status($compra['status']);
                    $order->set_total($compra['total']);
                    $order->set_date_created(new WC_DateTime($compra['data']));

                    // Adiciona os produtos dentro de cada pedido (ainda em desenvolvimento)
                    $produtos = $compra['produtos'];

                    foreach ($produtos as $produto) {
                        $product_name = $produto['nome'];
                        $product = get_product_by_name($product_name);

                        if ($product) {
                            $item = new WC_Order_Item_Product();
                            $item->set_quantity($produto['quantidade']);
                            $item->set_props(array(
                                'product' => $product,
                            ));
                            $item->set_order_id($order->get_id());
                            $item->save();
                        }
                    }

                    $order->save();
                }

                //Sucesso?

                echo '<p>Dados importados com sucesso!</p>';
            } else {
                echo '<p>O arquivo não pode ser descriptografado. Verifique a senha fornecida.</p>';
            }
        } else {
            echo '<p>Ocorreu um erro ao fazer upload do arquivo.</p>';
        }
    }

    // Form para importar os dados do usuario
    $form_html = '<form method="post" enctype="multipart/form-data" class="wdei-import-form">';
    $form_html .= '<input type="file" name="wdei_import_file" required>';
    $form_html .= '<input type="password" name="wdei_import_password" placeholder="Senha de descriptografia" required>';
    $form_html .= '<input type="submit" value="Importar" class="wdei-import-button">';
    $form_html .= '</form>';

    echo $form_html;
}
add_shortcode('wdei_import_form', 'wdei_import_user_data');

// Trigger para ativar o ?export_data=1 e o ?import_data=1
function wdei_trigger_export_script() {
    if (isset($_GET['export_data'])) {
        wdei_export_user_data();
        exit;
    }
}
add_action('wp', 'wdei_trigger_export_script');

function wdei_trigger_import_script() {
    if (isset($_GET['import_data'])) {
        wdei_import_user_data();
        exit;
    }
}
add_action('wp', 'wdei_trigger_import_script');

// Encripta os dados dentro do JSON
function encriptografar_json($json, $senha) {
    $method = 'aes-256-cbc';
    $key = hash('sha256', $senha, true);
    $ivLength = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($ivLength);
    $ciphertext = openssl_encrypt($json, $method, $key, OPENSSL_RAW_DATA, $iv);
    $encrypted_data = base64_encode($iv . $ciphertext);
    return $encrypted_data;
}

// Descripta os dados dentro do JSON
function descriptografar_json($encrypted_data, $senha) {
    $method = 'aes-256-cbc';
    $key = hash('sha256', $senha, true);
    $data = base64_decode($encrypted_data);
    $ivLength = openssl_cipher_iv_length($method);
    $iv = substr($data, 0, $ivLength);
    $ciphertext = substr($data, $ivLength);
    $json = openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
    if ($json === false) {
        return false;
    }
    return $json;
}
