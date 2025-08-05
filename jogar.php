<?php
session_start();
require 'includes/db.php';

// Verifica se está logado
if (!isset($_SESSION['usuario_id'])) {
  echo json_encode(['erro' => 'Faça login novamente.']);
  exit;
}

$userId = $_SESSION['usuario_id'];

// Identifica tipo da raspadinha
$tipo = $_GET['raspadinha'] ?? 'esperanca';

// Pega o valor da URL se fornecido, senão usa o padrão do tipo
$valorAposta = isset($_GET['valor']) ? floatval($_GET['valor']) : 1.00;

// Define configurações baseadas no tipo
switch ($tipo) {
  case 'alegria':
    if (!isset($_GET['valor'])) $valorAposta = 2.00;
    $premioMaximo = 100.00;
    $chance = 0.0012;
    break;
  case 'emocao':
    if (!isset($_GET['valor'])) $valorAposta = 20.00;
    $premioMaximo = 500.00;
    $chance = 0.0010;
    break;
  default:
    if (!isset($_GET['valor'])) $valorAposta = 1.00;
    $premioMaximo = 50.00;
    $chance = 0.01;
    break;
}

// Pega saldo atual
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$saldoAtual = floatval($user['balance']);

// Verifica saldo suficiente
if ($saldoAtual < $valorAposta) {
  echo json_encode(['erro' => 'Saldo insuficiente.']);
  exit;
}

// Desconta aposta imediatamente
$novoSaldo = $saldoAtual - $valorAposta;
$stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
$stmt->bind_param("di", $novoSaldo, $userId);
$stmt->execute();

// Verifica config do prêmio forçado
$config = $conn->query("SELECT * FROM raspadinha_config LIMIT 1")->fetch_assoc();

$premioForcadoAtivo = $config && intval($config['ativo']) === 1;
$maxPremios = intval($config['max_premios']);
$premiosPagos = intval($config['premios_pagos']);
$valorPremio = floatval($config['valor_premio']);

$allSimbolos = ['maça.png', 'banana.png', 'uva.png', 'laranja.png', 'abacaxi.png', 'morango.png'];
shuffle($allSimbolos);
$simbolosSorteados = array_slice($allSimbolos, 0, 6);


// Decide se ganha ou não
$ganhou = false;
$mensagem = 'Que pena, tente de novo!';
$valorGanho = 0;

// Se manipulado pelo admin e ainda não atingiu o limite de prêmios
if ($premioForcadoAtivo && $premiosPagos < $maxPremios) {
  $ganhou = true;
  $valorGanho = $valorPremio;
  $mensagem = "🎉 Parabéns! Você ganhou R$ " . number_format($valorPremio, 2, ',', '.');

  // Atualiza saldo com prêmio
  $novoSaldo += $valorPremio;
  $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
  $stmt->bind_param("di", $novoSaldo, $userId);
  $stmt->execute();

  // Incrementa contador de prêmios pagos
  $conn->query("UPDATE raspadinha_config SET premios_pagos = premios_pagos + 1");
} else {
  // Sorteio aleatório com base na chance configurada
  if (mt_rand(0, 1000000) / 1000000 <= $chance) {
    $ganhou = true;
    $valorGanho = $premioMaximo;
    $mensagem = "🎉 Parabéns! Você ganhou R$ " . number_format($valorGanho, 2, ',', '.');

    $novoSaldo += $valorGanho;
    $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->bind_param("di", $novoSaldo, $userId);
    $stmt->execute();
  }
}

// Resposta JSON para o frontend
echo json_encode([
  'simbolos' => $simbolosSorteados,
  'ganhou' => $ganhou,
  'premio' => $valorGanho,
  'mensagem' => $mensagem,
  'saldo' => number_format($novoSaldo, 2, ',', '.')
]);

exit;?>