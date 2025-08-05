<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Caminho real até o arquivo com a função
require 'includes/bspay_token.php';

echo "<h2>Testando BSPay</h2>";

$token = obterTokenBSPay();

echo "<p><strong>Token recebido:</strong> $token</p>";
?>
