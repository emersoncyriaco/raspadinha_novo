<?php
session_start();
require 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

$userId = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$saldo = $usuario['balance'];

$valorAposta = 1.00;

if ($saldo < $valorAposta) {
    echo json_encode(['erro' => 'Saldo insuficiente']);
    exit;
}

$simbolos = $_POST['simbolos'] ?? [];
if (count($simbolos) !== 3) {
    echo json_encode(['erro' => 'Símbolos inválidos']);
    exit;
}

// Pega RTP
$res = $conn->query("SELECT value FROM config WHERE name = 'rtp'");
$rtp = intval($res->fetch_assoc()['value']);

$ganhou = rand(1, 100) <= $rtp;
$premio = $ganhou ? 5.00 : 0;
$mensagem = $ganhou ? "🎉 Parabéns! Você ganhou R$ {$premio}!" : "😢 Que pena! Tente novamente.";

$novoSaldo = $saldo - $valorAposta + $premio;

$conn->prepare("UPDATE users SET balance = ? WHERE id = ?")
     ->bind_param("di", $novoSaldo, $userId)
     ->execute();

$conn->prepare("INSERT INTO bets (user_id, symbols, win, prize) VALUES (?, ?, ?, ?)")
     ->bind_param("isid", $userId, implode(',', $simbolos), $ganhou ? 1 : 0, $premio)
     ->execute();

echo json_encode([
    'mensagem' => $mensagem,
    'ganhou' => $ganhou,
    'saldo' => $novoSaldo
]);
