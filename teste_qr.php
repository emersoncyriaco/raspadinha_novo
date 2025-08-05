<?php
require_once 'includes/qr_generator.php';

// Teste básico do gerador de QR Code
$codigo_teste = "00020126580014br.gov.bcb.pix013636c7f4d9-9bb2-4e17-8364-5f7a3dc2b1f15204000053039865802BR5925TESTE PAGAMENTO PIX6009SAO PAULO62070503***6304A7B2";

echo "<h1>Teste do Gerador de QR Code PIX</h1>";

echo "<h2>1. Teste de URL direta:</h2>";
$qr_url = QRGenerator::gerarQRCode($codigo_teste, 250);
echo "<p>URL: <a href='$qr_url' target='_blank'>$qr_url</a></p>";
echo "<img src='$qr_url' alt='QR Code Teste' style='border: 2px solid #ccc;'>";

echo "<h2>2. Teste de Base64:</h2>";
$qr_base64 = QRGenerator::gerarQRCodeBase64($codigo_teste, 250);
if ($qr_base64) {
    if (strpos($qr_base64, 'data:image') === 0) {
        echo "<p>Base64 gerado com sucesso!</p>";
        echo "<img src='$qr_base64' alt='QR Code Base64' style='border: 2px solid #green;'>";
    } else {
        echo "<p>Fallback para URL: $qr_base64</p>";
        echo "<img src='$qr_base64' alt='QR Code Fallback' style='border: 2px solid #orange;'>";
    }
} else {
    echo "<p style='color: red;'>Erro ao gerar QR Code Base64</p>";
}

echo "<h2>3. Teste específico PIX:</h2>";
$qr_pix = QRGenerator::gerarQRCodePIX($codigo_teste);
if ($qr_pix) {
    echo "<p>QR Code PIX gerado com sucesso!</p>";
    echo "<img src='$qr_pix' alt='QR Code PIX' style='border: 2px solid #28a745;'>";
} else {
    echo "<p style='color: red;'>Erro ao gerar QR Code PIX</p>";
}

echo "<h2>4. Validação do código:</h2>";
$valido = QRGenerator::validarCodigoPIX($codigo_teste);
echo "<p>Código é válido: " . ($valido ? "SIM" : "NÃO") . "</p>";

echo "<h2>5. Código para copiar:</h2>";
echo "<textarea style='width: 100%; height: 100px;'>$codigo_teste</textarea>";
?>
