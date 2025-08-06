<?php
require_once 'includes/bspay_api.php';
require_once 'includes/bspay_config.php';

echo "<h1>Teste da Integração BSPay</h1>";

try {
    // Testa a obtenção do token
    error_log("Arquivo executado: " . __FILE__);
    echo "<h2>1. Testando obtenção de token...</h2>";
    $bspay = new BSPayAPI(BSPayConfig::getClientId(), BSPayConfig::getClientSecret());
    $token = $bspay->obterToken();
    echo "✅ Token obtido com sucesso: " . substr($token, 0, 20) . "...<br><br>";
    
    // Testa consulta de saldo
    echo "<h2>2. Testando consulta de saldo...</h2>";
    try {
        $saldo = $bspay->consultarSaldo();
        echo "✅ Saldo consultado com sucesso:<br>";
        echo "<pre>" . json_encode($saldo, JSON_PRETTY_PRINT) . "</pre><br>";
    } catch (Exception $e) {
        echo "⚠️ Erro ao consultar saldo: " . $e->getMessage() . "<br><br>";
    }
    
    // Testa geração de QR Code (com dados fictícios)
    echo "<h2>3. Testando geração de QR Code...</h2>";
    $dados_teste = [
        'amount' => 10.00,
        'external_id' => 'TESTE_' . time(),
        'payerQuestion' => 'Teste de integração BSPay',
        'payer' => [
            'name' => 'Usuário Teste',
            'document' => '00000000000',
            'email' => 'teste@exemplo.com'
        ],
        'postbackUrl' => 'https://exemplo.com/webhook'
    ];
    
    try {
        $qr_response = $bspay->gerarQRCode($dados_teste);
        echo "✅ QR Code gerado com sucesso:<br>";
        echo "<pre>" . json_encode($qr_response, JSON_PRETTY_PRINT) . "</pre><br>";
    } catch (Exception $e) {
        echo "⚠️ Erro ao gerar QR Code: " . $e->getMessage() . "<br><br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Configurações atuais:</h2>";
echo "Client ID: " . BSPayConfig::getClientId() . "<br>";
echo "Client Secret: " . substr(BSPayConfig::getClientSecret(), 0, 10) . "...<br>";
echo "Webhook URL: " . BSPayConfig::getWebhookUrl() . "<br>";

echo "<br><a href='inicio.php'>← Voltar ao início</a>";
?>

