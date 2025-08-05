<?php
session_start();
require 'includes/db.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Suporte a JSON
  $input = json_decode(file_get_contents('php://input'), true);
  $email = trim($input['email'] ?? '');
  $senha = $input['senha'] ?? '';

  $stmt = $conn->prepare("SELECT id, password, is_admin FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (password_verify($senha, $user['password'])) {
      $_SESSION['usuario_id'] = $user['id'];
      $_SESSION['is_admin'] = $user['is_admin'];
      echo json_encode(["sucesso" => true]);
      exit;
    } else {
      echo json_encode(["sucesso" => false, "erro" => "Senha incorreta."]);
      exit;
    }
  } else {
    echo json_encode(["sucesso" => false, "erro" => "Usuário não encontrado."]);
    exit;
  }
} else {
  echo json_encode(["sucesso" => false, "erro" => "Método inválido."]);
}
