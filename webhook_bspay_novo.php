<?php
require_once 'includes/db.php';

error_log("Arquivo executado: " . __FILE__);

// Função para log de debug
function logWebhook($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data) {
        $logMessage .= " - Data: " . json_encode($data, JSON_PRETTY_PRINT);
    }
    $logMessage .= "\n";
    
    // Cria o diretório de logs se não existir
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    file_put_contents('logs/webhook_bspay.log', $logMessage, FILE_APPEND | LOCK_EX);
}

// Configurar headers para resposta
header('Content-Type: application/json');

// Recebe os dados do webhook
$input = file_get_contents('php://input');
logWebhook("Webhook recebido", ['raw_input' => $input, 'headers' => getallheaders()]);

// Decodifica o JSON
$data = json_decode($input, true);

if (!$data) {
    logWebhook("Erro: JSON inválido");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'JSON inválido']);
    exit;
}

logWebhook("Dados decodificados", $data);

// Verifica se tem requestBody (formato da documentação BSPay)
if (isset($data['requestBody'])) {
    $eventData = $data['requestBody'];
    logWebhook("Usando formato requestBody", $eventData);
} else {
    // Fallback para formato direto
    $eventData = $data;
    logWebhook("Usando formato direto", $eventData);
}

// Extrai os dados do pagamento
$external_id = $eventData['external_id'] ?? '';
$amount = floatval($eventData['amount'] ?? 0);
$status = $eventData['status'] ?? '';
$transactionType = $eventData['transactionType'] ?? '';
$transactionId = $eventData['transactionId'] ?? '';

logWebhook("Dados extraídos", [
    'external_id' => $external_id,
    'amount' => $amount,
    'status' => $status,
    'transactionType' => $transactionType,
    'transactionId' => $transactionId
]);

// Processa apenas transações de recebimento PIX com status PAID
if ($transactionType === 'RECEIVEPIX' && $status === 'PAID') {
    
    if ($external_id && $amount > 0) {
        // Busca o depósito pendente na tabela deposits
        $stmt = $conn->prepare("SELECT * FROM deposits WHERE external_id = ? AND status IN ('pendente', 'pending')");
        $stmt->bind_param("s", $external_id);
        $stmt->execute();
        $deposito = $stmt->get_result()->fetch_assoc();
        
        logWebhook("Busca depósito", [
            'external_id' => $external_id, 
            'found' => $deposito ? 'sim' : 'não',
            'deposit_data' => $deposito
        ]);
        
        if ($deposito) {
            // Verifica se o depósito já foi processado
            if ($deposito['status'] === 'pago' || $deposito['status'] === 'paid') {
                logWebhook("Depósito já processado", ['external_id' => $external_id]);
                http_response_code(200);
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Pagamento já processado anteriormente',
                    'external_id' => $external_id
                ]);
                exit;
            }
            
            // Inicia transação
            $conn->begin_transaction();
            
            try {
                // Atualiza o status do depósito
                $stmt = $conn->prepare("UPDATE deposits SET status = 'pago', payment_id = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("si", $transactionId, $deposito['id']);
                $stmt->execute();
                
                // Adiciona o valor ao saldo do usuário
                $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->bind_param("di", $amount, $deposito['user_id']);
                $stmt->execute();
                
                // Verifica se o update do saldo foi bem-sucedido
                if ($stmt->affected_rows > 0) {
                    // Registra a transação
                    $stmt = $conn->prepare("INSERT INTO transacoes (usuario_id, tipo, valor, descricao, status) VALUES (?, 'deposito_aprovado', ?, 'Depósito aprovado via BSPay', 'concluido')");
                    $stmt->bind_param("id", $deposito['user_id'], $amount);
                    $stmt->execute();
                    
                    // Confirma a transação
                    $conn->commit();
                    
                    logWebhook("Depósito aprovado com sucesso", [
                        'user_id' => $deposito['user_id'],
                        'amount' => $amount,
                        'external_id' => $external_id,
                        'transaction_id' => $transactionId
                    ]);
                    
                    // Resposta de sucesso
                    http_response_code(200);
                    echo json_encode([
                        'status' => 'success', 
                        'message' => 'Pagamento processado com sucesso',
                        'external_id' => $external_id,
                        'amount' => $amount,
                        'user_id' => $deposito['user_id']
                    ]);
                } else {
                    throw new Exception("Falha ao atualizar saldo do usuário");
                }
                
            } catch (Exception $e) {
                // Desfaz a transação em caso de erro
                $conn->rollback();
                logWebhook("Erro ao processar depósito", [
                    'error' => $e->getMessage(),
                    'external_id' => $external_id
                ]);
                
                http_response_code(500);
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Erro interno do servidor: ' . $e->getMessage()
                ]);
            }
            
        } else {
            logWebhook("Depósito não encontrado", [
                'external_id' => $external_id,
                'amount' => $amount
            ]);
            
            // Verifica se existe na tabela depositos (nome alternativo)
            $stmt = $conn->prepare("SELECT * FROM depositos WHERE codigo_transacao = ? AND status = 'pendente'");
            $stmt->bind_param("s", $external_id);
            $stmt->execute();
            $deposito_alt = $stmt->get_result()->fetch_assoc();
            
            if ($deposito_alt) {
                logWebhook("Depósito encontrado na tabela depositos", $deposito_alt);
                
                $conn->begin_transaction();
                try {
                    // Atualiza o status do depósito
                    $stmt = $conn->prepare("UPDATE depositos SET status = 'aprovado', data_aprovacao = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $deposito_alt['id']);
                    $stmt->execute();
                    
                    // Adiciona o valor ao saldo do usuário
                    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $stmt->bind_param("di", $amount, $deposito_alt['usuario_id']);
                    $stmt->execute();
                    
                    $conn->commit();
                    
                    logWebhook("Depósito aprovado (tabela depositos)", [
                        'user_id' => $deposito_alt['usuario_id'],
                        'amount' => $amount,
                        'external_id' => $external_id
                    ]);
                    
                    http_response_code(200);
                    echo json_encode([
                        'status' => 'success', 
                        'message' => 'Pagamento processado com sucesso',
                        'external_id' => $external_id,
                        'amount' => $amount
                    ]);
                } catch (Exception $e) {
                    $conn->rollback();
                    logWebhook("Erro ao processar depósito (tabela depositos)", ['error' => $e->getMessage()]);
                    
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Erro interno do servidor']);
                }
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Depósito não encontrado',
                    'external_id' => $external_id,
                    'searched_amount' => $amount
                ]);
            }
        }
    } else {
        logWebhook("Dados insuficientes", [
            'external_id' => $external_id,
            'amount' => $amount
        ]);
        
        http_response_code(400);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Dados insuficientes',
            'received_external_id' => $external_id,
            'received_amount' => $amount
        ]);
    }
} else {
    logWebhook("Evento não processado", [
        'transactionType' => $transactionType,
        'status' => $status,
        'full_data' => $eventData
    ]);
    
    http_response_code(200);
    echo json_encode([
        'status' => 'ignored', 
        'message' => 'Evento não processado - tipo ou status não correspondem',
        'transactionType' => $transactionType,
        'status' => $status,
        'expected' => ['transactionType' => 'RECEIVEPIX', 'status' => 'PAID']
    ]);
}
?>
