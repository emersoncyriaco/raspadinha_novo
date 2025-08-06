<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);
session_start();
// require_once 'includes/auth.php';
require_once 'includes/db.php';
require_once 'includes/bspay_api.php';
require_once 'includes/bspay_config.php';
require_once 'includes/qr_generator.php';

// Verifica se o usuário está logado
// if (!isset($_SESSION["user_id"])) {
//     header("Location: login.php");
//     exit;
// }

// Simula um user_id para testes
$_SESSION["user_id"] = 1;

$user_id = $_SESSION["user_id"];

// Busca dados do usuário
// Simula o retorno de um usuário para testes
$user = ["id" => 1, "name" => "Usuário Teste", "email" => "teste@example.com"];

// $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
// $stmt->bind_param("i", $user_id);
// $stmt->execute();
// $user = $stmt->get_result()->fetch_assoc();

$error = '';
$success = '';
$qr_code_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor = floatval($_POST['valor'] ?? 0);
    
    if ($valor <= 0) {
        $error = 'Valor deve ser maior que zero';
    } else {
        try {
            // Gera ID único para a transação
            $external_id = 'DEP_' . $user_id . '_' . time() . '_' . rand(1000, 9999);
            
            // Inicializa a API BSPay
            $bspay = new BSPayAPI(BSPayConfig::getClientId(), BSPayConfig::getClientSecret());
            
            // Dados para gerar o QR Code
            $dados_qr = [
                'amount' => $valor,
                'external_id' => $external_id,
                'payerQuestion' => 'Depósito na conta - ' . $user['name'],
                'payer' => [
                    'name' => $user['name'],
                    'document' => '00000000000',
                    'email' => $user['email']
                ],
                'postbackUrl' => 'mock_webhook_url'
            ];
            
            // Gera o QR Code
            $response = $bspay->gerarQRCode($dados_qr);
            
            // Salva o depósito pendente no banco
            $stmt = $conn->prepare("INSERT INTO depositos (usuario_id, valor, status, external_id, qr_code, pix_code, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            
            if ($stmt === false) {
                $error = 'Erro na preparação da consulta: ' . $conn->error;
            } else {
                $qr_code = $response["qr_code"] ?? '';
                $pix_code = $response["pix_code"] ?? '';
                $status = 'pendente';
                $stmt->bind_param("idssss", $user_id, $valor, $status, $external_id, $qr_code, $pix_code);
                $stmt->execute();
                
                // Gera QR Code visual
                if ($pix_code) {
                    $qr_code_image = QRGenerator::gerarQRCodePIX($pix_code);
                    $qr_code_data = [
                        'external_id' => $external_id,
                        'valor' => $valor,
                        'pix_code' => $pix_code,
                        'qr_image' => $qr_code_image
                    ];
                    $success = 'QR Code gerado com sucesso! Escaneie para realizar o pagamento.';
                } else {
                    $error = 'Erro ao gerar código PIX';
                }
            }
        } catch (Exception $e) {
            $error = 'Erro ao gerar QR Code: ' . $e->getMessage();
        }
    }
}
?>

