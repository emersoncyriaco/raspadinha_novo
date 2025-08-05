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
$ganhou = $data["ganhou"] ?? false;
$premio = $data["premio"] ?? 0;

// Valida o valor do prêmio
if ($ganhou && ($premio <= 0 || $premio > 1000)) {
    http_response_code(400);
    echo json_encode(["erro" => "Valor do prêmio inválido"]);
    exit;
}

try {
    // Inicia uma transação para garantir consistência
    $conn->begin_transaction();
    
    // Se ganhou, adiciona o prêmio ao saldo
    if ($ganhou && $premio > 0) {
        // Atualiza o saldo do usuário
        $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->bind_param("di", $premio, $userId);
        
        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar saldo");
        }
        
        // Registra a transação no histórico (opcional - você pode criar esta tabela)
        $stmt = $conn->prepare("INSERT INTO transacoes (usuario_id, tipo, valor, descricao, data_criacao) VALUES (?, 'premio_raspadinha', ?, 'Prêmio ganho na raspadinha', NOW())");
        $stmt->bind_param("id", $userId, $premio);
        $stmt->execute(); // Não é crítico se falhar
    }
    
    // Registra a jogada no histórico
    $resultado = $ganhou ? 'ganhou' : 'perdeu';
    $stmt = $conn->prepare("INSERT INTO jogadas_raspadinha (usuario_id, resultado, premio, data_jogada) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("isd", $userId, $resultado, $premio);
    $stmt->execute(); // Não é crítico se falhar
    
    // Busca o saldo atualizado
    $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("Usuário não encontrado");
    }
    
    // Confirma a transação
    $conn->commit();
    
    // Retorna o resultado
    echo json_encode([
        "sucesso" => true,
        "ganhou" => $ganhou,
        "premio" => $premio,
        "saldo" => number_format($user["balance"], 2, ",", "."),
        "saldo_numerico" => $user["balance"]
    ]);
    
} catch (Exception $e) {
    // Desfaz a transação em caso de erro
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        "erro" => "Erro interno do servidor",
        "detalhes" => $e->getMessage()
    ]);
}
?>

