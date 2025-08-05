<?php
session_start();
require 'includes/db.php';

$userId = $_SESSION['usuario_id'];

$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo json_encode(['saldo' => number_format($user['balance'], 2, ',', '.')]);
?>

