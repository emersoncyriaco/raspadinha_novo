<?php
/**
 * Script para processar comissões manualmente
 * Pode ser usado para processar comissões de vendas, depósitos ou outras conversões
 */

require_once 'includes/db.php';
require_once 'includes/affiliate_functions.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = (int)$_POST['user_id'];
    $conversion_type = $_POST['conversion_type']; // 'signup', 'deposit', 'sale'
    $amount = (float)$_POST['amount'];
    
    if ($user_id && $conversion_type) {
        try {
            $conn->begin_transaction();
            
            // Buscar se o usuário tem um referrer
            $stmt = $conn->prepare("SELECT referrer_id FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();
            
            if ($user_data && $user_data['referrer_id']) {
                $referrer_id = $user_data['referrer_id'];
                
                // Buscar o affiliate_id do referrer
                $stmt = $conn->prepare("SELECT id FROM affiliates WHERE user_id = ?");
                $stmt->bind_param("i", $referrer_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($affiliate = $result->fetch_assoc()) {
                    $affiliate_id = $affiliate['id'];
                    
                    // Registrar conversão
                    registerAffiliateConversion($conn, $affiliate_id, $user_id, $conversion_type, $amount);
                    
                    // Calcular e registrar comissões
                    calculateAndRegisterCommissions($conn, $user_id, $conversion_type, $amount, $referrer_id, 1);
                    
                    echo json_encode(['status' => 'success', 'message' => 'Comissões processadas com sucesso']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Afiliado não encontrado']);
                }
            } else {
                echo json_encode(['status' => 'info', 'message' => 'Usuário não possui referrer']);
            }
            
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Parâmetros inválidos']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método não permitido']);
}
?>

