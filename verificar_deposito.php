<?php
session_start();
require_once 'includes/db.php';

$user_id = $_SESSION['usuario_id'] ?? 0;

header('Content-Type: application/json');

// Busca o último depósito do usuário (ordem decrescente por ID)
$stmt = $conn->prepare("SELECT status FROM deposits WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$deposit = $result->fetch_assoc();

// Busca saldo atual do usuário
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo json_encode([
    'deposito_pago' => ($deposit && $deposit['status'] === 'pago'),
    'novo_saldo' => number_format($user['balance'], 2, ',', '.')
]);
