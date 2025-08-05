<?php
session_start();
require 'includes/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION["usuario_id"])) {
    http_response_code(401);
    echo json_encode(["erro" => "Usuário não autenticado"]);
    exit;
}

// Valida se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["erro" => "Método não permitido"]);
    exit;
}

// Decodifica os dados JSON
$data = json_decode(file_get_contents("php://input"), true);

// Valida se os dados foram recebidos corretamente
if (!$data) {
    http_response_code(400);
    echo json_encode(["erro" => "Dados inválidos"]);
    exit;
}

$userId = $_SESSION["usuario_id"];
$valorAposta = $data["valor_aposta"] ?? 0;

// Valida o valor da aposta
if ($valorAposta <= 0 || !in_array($valorAposta, [1, 5, 10, 20, 50, 100])) {
    http_response_code(400);
    echo json_encode(["erro" => "Valor de aposta inválido"]);
    exit;
}

try {
    // Inicia uma transação para garantir consistência
    $conn->begin_transaction();
    
    // Verifica o saldo atual do usuário
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("Usuário não encontrado");
    }
    
    // Verifica se o usuário tem saldo suficiente
    if ($user['balance'] < $valorAposta) {
        throw new Exception("Saldo insuficiente");
    }
    
    // Desconta o valor da aposta do saldo
    $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $stmt->bind_param("di", $valorAposta, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao descontar saldo");
    }
    
    // Registra a transação no histórico
    $stmt = $conn->prepare("INSERT INTO transacoes (usuario_id, tipo, valor, descricao, data_criacao) VALUES (?, 'aposta_raspadinha', ?, 'Aposta na raspadinha', NOW())");
    $stmt->bind_param("id", $userId, $valorAposta);
    $stmt->execute(); // Não é crítico se falhar
    
    // Busca o saldo atualizado
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Confirma a transação
    $conn->commit();
    
    // Retorna o resultado
    echo json_encode([
        "sucesso" => true,
        "valor_apostado" => $valorAposta,
        "saldo" => number_format($user["balance"], 2, ",", "."),
        "saldo_numerico" => $user["balance"]
    ]);
    
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        "erro" => $e->getMessage()
    ]);
}
?>

