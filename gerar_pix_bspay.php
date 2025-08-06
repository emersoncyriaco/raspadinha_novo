<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/bspay_api.php';
require_once 'includes/bspay_config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$valor = floatval($input['valor'] ?? 0);

if ($valor < 1) {
    echo json_encode(['erro' => 'Valor mínimo R$1,00']);
    exit;
}

$user_id = $_SESSION['usuario_id'];

// Busca nome e email do usuário
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Gera um ID externo único para rastrear a transação
$external_id = 'DEP_' . $user_id . '_' . time() . '_' . rand(1000, 9999);

try {
    error_log("Arquivo executado: " . __FILE__);
    $bspay = new BSPayAPI(BSPayConfig::getClientId(), BSPayConfig::getClientSecret());
    $dados_qr = [
        'amount' => $valor,
        'external_id' => $external_id,
        'postbackUrl' => BSPayConfig::getWebhookUrl(),
        'payerQuestion' => 'Depósito na conta - ' . $user['name'],
        'payer' => [
            'name' => $user['name'],
            'document' => '00000000000', // CPF fictício
            'email' => $user['email']
        ],
    ];

    $response = $bspay->gerarQRCode($dados_qr);

    error_log("Resposta da BSPay para Pix: " . json_encode($response));

    if (isset($response['qrcode'])) {
        // Insere o depósito como pendente na tabela deposits
        $stmt = $conn->prepare("INSERT INTO deposits (user_id, amount, status, payment_id, created_at, updated_at, external_id) VALUES (?, ?, 'pendente', NULL, NOW(), NOW(), ?)");
        $stmt->bind_param("ids", $user_id, $valor, $external_id);
        $stmt->execute();

        echo json_encode(['qrcode' => $response['qrcode']]);
    } else {
        echo json_encode(['erro' => 'Falha ao gerar Pix: ' . ($response['message'] ?? 'Erro desconhecido')]);
    }

} catch (Exception $e) {
    echo json_encode([
        'erro' => 'Erro ao gerar QR Code: ' . $e->getMessage()
    ]);
}
?>
