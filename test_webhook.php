<?php
// Script para testar o webhook BSPay
require_once 'includes/db.php';

// Dados de teste simulando um webhook do BSPay
$test_data = [
    "requestBody" => [
        "transactionType" => "RECEIVEPIX",
        "transactionId" => "test_" . time(),
        "external_id" => "DEP_1_" . time() . "_" . rand(1000, 9999),
        "amount" => 10.00,
        "paymentType" => "PIX",
        "status" => "PAID",
        "dateApproval" => date('Y-m-d H:i:s'),
        "creditParty" => [
            "name" => "Teste Usuario",
            "email" => "teste@teste.com",
            "taxId" => "12345678901"
        ],
        "debitParty" => [
            "bank" => "BSPAY SOLUCOES DE PAGAMENTOS LTDA",
            "taxId" => "46872831000154"
        ]
    ]
];

echo "<h2>Teste do Webhook BSPay</h2>";

// Primeiro, vamos criar um depósito de teste
$user_id = 1; // ID do usuário admin
$amount = 10.00;
$external_id = $test_data['requestBody']['external_id'];

echo "<h3>1. Criando depósito de teste</h3>";
$stmt = $conn->prepare("INSERT INTO deposits (user_id, amount, status, external_id, created_at) VALUES (?, ?, 'pendente', ?, NOW())");
$stmt->bind_param("ids", $user_id, $amount, $external_id);

if ($stmt->execute()) {
    echo "✅ Depósito criado com sucesso<br>";
    echo "External ID: " . $external_id . "<br>";
    echo "Valor: R$ " . number_format($amount, 2, ',', '.') . "<br>";
} else {
    echo "❌ Erro ao criar depósito: " . $conn->error . "<br>";
    exit;
}

// Verificar saldo antes
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$balance_before = $user['balance'];

echo "<h3>2. Saldo antes do webhook</h3>";
echo "Saldo atual: R$ " . number_format($balance_before, 2, ',', '.') . "<br>";

echo "<h3>3. Simulando webhook</h3>";

// Simular o webhook
$webhook_url = "https://web.profelardev.site/webhook_bspay_novo.php";
$json_data = json_encode($test_data);

echo "URL do Webhook: " . $webhook_url . "<br>";
echo "Dados enviados:<br>";
echo "<pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>";

// Fazer a requisição para o webhook
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhook_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($json_data)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<h3>4. Resposta do Webhook</h3>";
echo "HTTP Code: " . $http_code . "<br>";

if ($curl_error) {
    echo "❌ Erro cURL: " . $curl_error . "<br>";
} else {
    echo "Resposta: " . $response . "<br>";
}

// Verificar saldo depois
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$balance_after = $user['balance'];

echo "<h3>5. Verificação Final</h3>";
echo "Saldo antes: R$ " . number_format($balance_before, 2, ',', '.') . "<br>";
echo "Saldo depois: R$ " . number_format($balance_after, 2, ',', '.') . "<br>";
echo "Diferença: R$ " . number_format($balance_after - $balance_before, 2, ',', '.') . "<br>";

if ($balance_after > $balance_before) {
    echo "✅ <strong>Webhook funcionando corretamente!</strong><br>";
} else {
    echo "❌ <strong>Webhook não processou o pagamento</strong><br>";
}

// Verificar status do depósito
$stmt = $conn->prepare("SELECT status FROM deposits WHERE external_id = ?");
$stmt->bind_param("s", $external_id);
$stmt->execute();
$deposit = $stmt->get_result()->fetch_assoc();

echo "Status do depósito: " . ($deposit['status'] ?? 'não encontrado') . "<br>";

// Mostrar logs se existirem
if (file_exists('logs/webhook_bspay.log')) {
    echo "<h3>6. Últimos logs do webhook</h3>";
    $logs = file_get_contents('logs/webhook_bspay.log');
    $log_lines = explode("\n", $logs);
    $recent_logs = array_slice($log_lines, -20); // Últimas 20 linhas
    echo "<pre>" . implode("\n", $recent_logs) . "</pre>";
}
?>
