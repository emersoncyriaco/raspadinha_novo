<?php
require 'includes/db.php';
require 'includes/affiliate_functions.php';
session_start();

header('Content-Type: application/json');

// Receber os dados JSON
$dados = json_decode(file_get_contents("php://input"), true);
$nome = trim($dados['nome']);
$email = trim($dados['email']);
$senha = password_hash($dados['senha'], PASSWORD_DEFAULT);

// Verificar se o e-mail já está cadastrado
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['status' => 'erro', 'mensagem' => 'E-mail já cadastrado']);
    exit;
}

// Verificar se há um afiliado referrer
$referrer_id = null;
$affiliate_code = null;

// Verificar na sessão primeiro
if (isset($_SESSION['affiliate_ref']) && !empty($_SESSION['affiliate_ref'])) {
    $affiliate_code = $_SESSION['affiliate_ref'];
} elseif (isset($_COOKIE['affiliate_ref']) && !empty($_COOKIE['affiliate_ref'])) {
    $affiliate_code = $_COOKIE['affiliate_ref'];
}

if ($affiliate_code) {
    // Buscar o ID do afiliado pelo código
    $stmt = $conn->prepare("SELECT user_id FROM affiliates WHERE affiliate_code = ? AND is_active = 1");
    $stmt->bind_param("s", $affiliate_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($affiliate = $result->fetch_assoc()) {
        $referrer_id = $affiliate['user_id'];
    }
}

try {
    $conn->begin_transaction();

    // Inserir novo usuário
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, balance, referrer_id) VALUES (?, ?, ?, 0.00, ?)");
    $stmt->bind_param("sssi", $nome, $email, $senha, $referrer_id);
    $stmt->execute();
    $userId = $conn->insert_id;

    // Se houver um afiliado, processar indicações e comissões
    if ($referrer_id) {
        // Registrar cadeia de indicações (até 4 níveis)
        registerReferralChain($conn, $userId, $referrer_id);
        
        // Buscar o affiliate_id do referrer para registrar conversão
        $stmt = $conn->prepare("SELECT id FROM affiliates WHERE user_id = ?");
        $stmt->bind_param("i", $referrer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($affiliate = $result->fetch_assoc()) {
            $affiliate_id = $affiliate['id'];
            
            // Registrar conversão (cadastro)
            registerAffiliateConversion($conn, $affiliate_id, $userId, 'signup');
            
            // Calcular e registrar comissões CPA
            calculateAndRegisterCommissions($conn, $userId, 'signup', 0, $referrer_id, 1);
        }
        
        // Limpar o código do afiliado da sessão e cookie após o cadastro
        unset($_SESSION['affiliate_ref']);
        setcookie('affiliate_ref', '', time() - 3600, "/");
    }

    $conn->commit();

    // Iniciar sessão do novo usuário
    $_SESSION['usuario_id'] = $userId;

    // Retornar resposta de sucesso
    echo json_encode(['status' => 'sucesso', 'mensagem' => 'Registrado com sucesso']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Erro no registro: " . $e->getMessage());
    echo json_encode(['status' => 'erro', 'mensagem' => 'Erro interno do servidor']);
}
