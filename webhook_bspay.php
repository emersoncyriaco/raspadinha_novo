<?php
require_once 'includes/db.php';

error_log("Arquivo executado: " . __FILE__);

$data = json_decode(file_get_contents("php://input"), true);

if (
    isset($data['requestBody']['transactionType']) &&
    $data['requestBody']['transactionType'] === 'RECEIVEPIX' &&
    $data['requestBody']['status'] === 'PAID'
) {
    $external_id = $data['requestBody']['external_id'];
    $valor = floatval($data['requestBody']['amount']);
    $payment_id = $data['requestBody']['transactionId'];

    if (preg_match('/^DEP_(\d+)_/', $external_id, $matches)) {
        $user_id = intval($matches[1]);

        // Verifica se o depósito já existe e está pendente
        $stmt = $conn->prepare("SELECT id, status FROM deposits WHERE user_id = ? AND amount = ? AND external_id = ? LIMIT 1");
        $stmt->bind_param("ids", $user_id, $valor, $external_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $deposit = $result->fetch_assoc();

        if ($deposit && $deposit['status'] !== 'pago') {
            // Atualiza o saldo do usuário
            $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->bind_param("di", $valor, $user_id);
            $stmt->execute();

            // Marca depósito como pago
            $stmt = $conn->prepare("UPDATE deposits SET status = 'pago', payment_id = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $payment_id, $deposit['id']);
            $stmt->execute();
        }

        http_response_code(200);
        echo json_encode(['status' => 'sucesso']);
        exit;
    }
}

http_response_code(400);
echo json_encode(['erro' => 'Dados inválidos']);
