# Plugin para implentação do direito a portabilidade no wordpress com woocommerce.


#### Programa criado por [Celso Dames Junior](github.com/CelsoDamesJunior) e [João Guylherme Alves Silva](github.com/JGuylherme) para a disciplina de Segurança e Auditoria de sistema pelo Professor Nilson Mori. ####


## Motivação ##

O intuito deste plugin é mostrar uma padronização e transferência de dados entre sites com o woocommerce.
Neste com os dados para serem transferidos como Nome,e-mail,telefone,endereço de entrega e fatura, histórico de pedidos (ainda em desenvolvimento).


## Importante ##

Este plugin está em fase inicial, devido a isso alguns processos em grande escala podem apresentar dificuldades e bugs.
Um detectado é o fato da implementação do e-mail substituir o atual ao puxar o arquivo, desta forma se o seu site utiliza e-mails para login, deve-se atentar a esse detalhe, e por se tratar de um modelo teste, as devidas tratativas de validações dos arquivos ficam a cargo da empresa a implementar.

## Instalação ##

Para instalação do plugin, os arquios deste repositório deve ser instalado em uma pasta criado com o nome 'portabilidade' dentro pasta “plugins” em wp-content na pasta do seu projeto/site , em seguida ative-o pelo menu de plugins no painel admin com o nome 	Portabilidade no WooCommerce do Wordpress. Com isso adicione em uma página do site em que deseja realizar o processo de portabilidade, para exportar atribua o botão com a url da página com sufixo “. /?data_export=1 ou o sufixo “. /?data_import=1” para import, aconselhamos ambos na mesma pagina para tornar o processo mais dinamico e intuitivo, assim o plugin já está funcionando.

## Funcionamento ##

O Funcionamento do plugin é divido em duas partes principais, a exportação e a importação. 
A exportação ocorre o processo de adquirir os dados do usuario logado jogando a um arquivo JSON e em seguida é realizada uma criptografia AES-256-cbc com a chave gerada a partir de um codigo hash gerada pelo nome do usuario (Não recomendado para implementações geradas e sim para testes) com isso o arquivo json criptografado é baixado no computador do usuario junto a um pop-up gerado na pagina a senha para descriptografia para o usuario.

A importação ocorre o processo de puxar os dados do json criptografado com uma senha para atribuição no usuario logado, para isso ao clicar no botão importar informado pela instalação, uma pagina dinamica é gerada para envio do arquivo e atribuição de senha. Clique em importar nesta nova pagina para realizar o processo de decriptografia. Caso o processo for realizado , uma mensagem de importado com sucesso é gerado, caso contrario uma mensagem informando que a senha está equivocada é informada.

Lembrando que as validações podem variar de acordo com a empresa a implementar se alguns dados serão substituidos ou não.



