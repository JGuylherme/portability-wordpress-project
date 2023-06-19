<?php
if (isset($_GET['data'])) {
    $data = base64_decode($_GET['data']);
    $filename = $_GET['filename'];

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($data));
    echo $data;
    exit;
}
