<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/bspay_config.php';
require_once 'bspay_api.php';

// Função para log de debug
function logPixGeneration($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] PIX Generation - $message";
    if ($data) {
        $logMessage .= " - Data: " . json_encode($data, JSON_PRETTY_PRINT);
    }
    $logMessage .= "\n";
    
    // Cria o diretório de logs se não existir
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    file_put_contents('logs/pix_generation.log', $logMessage, FILE_APPEND | LOCK_EX);
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
logPixGeneration("Requisição recebida", $input);

$amount = floatval($input['amount'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($amount < 1) {
    logPixGeneration("Valor inválido", ['amount' => $amount]);
    http_response_code(400);
    echo json_encode(['error' => 'Valor mínimo é R$ 1,00']);
    exit;
}

try {
    // Busca dados do usuário
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        logPixGeneration("Usuário não encontrado", ['user_id' => $user_id]);
        throw new Exception("Usuário não encontrado");
    }
    
    // Gera external_id único
    $external_id = 'DEP_' . $user_id . '_' . time() . '_' . rand(1000, 9999);
    
    // Salva o depósito no banco
    $stmt = $conn->prepare("INSERT INTO deposits (user_id, amount, status, external_id, created_at) VALUES (?, ?, 'pendente', ?, NOW())");
    $stmt->bind_param("ids", $user_id, $amount, $external_id);
    $stmt->execute();
    
    logPixGeneration("Depósito salvo no banco", [
        'user_id' => $user_id,
        'amount' => $amount,
        'external_id' => $external_id
    ]);
    
    // Configura a API BSPay
    $bspay = new BSPayAPI(
        BSPayConfig::getClientId(),
        BSPayConfig::getClientSecret()
    );
    
    // Dados para gerar o PIX
    $pixData = [
        'amount' => $amount,
        'external_id' => $external_id,
        'payerQuestion' => 'Depósito Raspa Sorte',
        'payer' => [
            'name' => $user['name'],
            'document' => '00000000000', // CPF genérico - ajustar se necessário
            'email' => $user['email']
        ],
        'postbackUrl' => BSPayConfig::getWebhookUrl()
    ];
    
    logPixGeneration("Dados para BSPay", $pixData);
    
    // Gera o QR Code PIX
    $response = $bspay->gerarQRCode($pixData);
    
    logPixGeneration("Resposta da BSPay", $response);
    
    // Retorna os dados do PIX
    echo json_encode([
        'success' => true,
        'qr_code' => $response['qr_code'] ?? '',
        'qr_code_base64' => $response['qr_code_base64'] ?? '',
        'pix_code' => $response['pix_code'] ?? '',
        'external_id' => $external_id,
        'amount' => $amount,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes'))
    ]);
    
} catch (Exception $e) {
    logPixGeneration("Erro ao gerar PIX", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao gerar código PIX: ' . $e->getMessage()
    ]);
}
?>
