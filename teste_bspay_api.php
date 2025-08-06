<?php
require_once 'includes/bspay_api.php';
require_once 'includes/bspay_config.php';

header('Content-Type: application/json');

try {
    error_log("Arquivo executado: " . __FILE__);
    $bspay = new BSPayAPI(BSPayConfig::getClientId(), BSPayConfig::getClientSecret());
    $token = $bspay->obterToken();
    echo json_encode(['success' => true, 'token' => $token]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

