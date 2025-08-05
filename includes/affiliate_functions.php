<?php
/**
 * Funções auxiliares para o sistema de afiliados
 */

/**
 * Gera um código de afiliado único baseado no username e user_id
 */
function generateAffiliateCode($username, $user_id) {
    // Remove espaços e caracteres especiais do username
    $clean_username = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($username));
    
    // Se o username limpo for muito curto, usa apenas o user_id
    if (strlen($clean_username) < 3) {
        return 'user' . $user_id;
    }
    
    // Combina username limpo com user_id
    return $clean_username . $user_id;
}

/**
 * Registra um clique de afiliado
 */
function registerAffiliateClick($conn, $affiliate_code) {
    try {
        // Buscar o ID do afiliado pelo código
        $stmt = $conn->prepare("SELECT id FROM affiliates WHERE affiliate_code = ? AND is_active = 1");
        $stmt->bind_param("s", $affiliate_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($affiliate = $result->fetch_assoc()) {
            $affiliate_id = $affiliate['id'];
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            
            // Registrar o clique
            $stmt = $conn->prepare("INSERT INTO affiliate_clicks (affiliate_id, ip_address, user_agent) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $affiliate_id, $ip_address, $user_agent);
            $stmt->execute();
            
            return true;
        }
    } catch (Exception $e) {
        error_log("Erro ao registrar clique de afiliado: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Registra uma conversão de afiliado
 */
function registerAffiliateConversion($conn, $affiliate_id, $converted_user_id, $conversion_type, $amount = 0) {
    try {
        $stmt = $conn->prepare("INSERT INTO affiliate_conversions (affiliate_id, converted_user_id, conversion_type, amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisd", $affiliate_id, $converted_user_id, $conversion_type, $amount);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Erro ao registrar conversão de afiliado: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcula e registra comissões recursivamente para até 4 níveis
 */
function calculateAndRegisterCommissions($conn, $converted_user_id, $conversion_type, $conversion_amount, $current_user_id, $level = 1) {
    if ($level > 4) {
        return;
    }
    
    try {
        // Buscar dados do afiliado
        $stmt = $conn->prepare("
            SELECT a.id as affiliate_id, a.cpa_commission_rate, a.revshare_commission_rate, 
                   a.cpa_commission_rate_admin, a.revshare_commission_rate_admin,
                   a.fixed_commission_per_signup, a.allow_sub_affiliate_earnings 
            FROM affiliates a 
            WHERE a.user_id = ? AND a.is_active = 1
        ");
        $stmt->bind_param("i", $current_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($affiliate_data = $result->fetch_assoc()) {
            $affiliate_id = $affiliate_data['affiliate_id'];
            $commission_amount = 0;
            
            // Se for nível 1 ou se sub-afiliados são permitidos para este afiliado
            if ($level == 1 || $affiliate_data['allow_sub_affiliate_earnings']) {
                if ($conversion_type == 'signup') { $commission_amount = 0; }
                
                if ($commission_amount > 0) {
                    // Registrar a comissão
                    $commission_type = ($conversion_type == 'signup') ? 'CPA' : 'RevShare';
                    $stmt = $conn->prepare("INSERT INTO commissions (affiliate_id, referred_user_id, type, amount, level) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisdi", $affiliate_id, $converted_user_id, $commission_type, $commission_amount, $level);
                    $stmt->execute();
                    
                    // Atualizar saldo do afiliado
                    $stmt = $conn->prepare("UPDATE users SET affiliate_balance = affiliate_balance + ? WHERE id = ?");
                    $stmt->bind_param("di", $commission_amount, $current_user_id);
                    $stmt->execute();
                }
            }
            
            // Buscar o próximo afiliado na cadeia
            $stmt = $conn->prepare("SELECT referrer_id FROM users WHERE id = ?");
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($next_referrer = $result->fetch_assoc()) {
                if ($next_referrer['referrer_id']) {
                    calculateAndRegisterCommissions($conn, $converted_user_id, $conversion_type, $conversion_amount, $next_referrer['referrer_id'], $level + 1);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao calcular comissões: " . $e->getMessage());
    }
}

/**
 * Registra indicações em cascata (até 4 níveis)
 */
function registerReferralChain($conn, $new_user_id, $referrer_id) {
    try {
        $current_referrer_id = $referrer_id;
        $level = 1;
        
        while ($level <= 4 && $current_referrer_id) {
            // Registrar a indicação
            $stmt = $conn->prepare("INSERT INTO referrals (referrer_id, referred_id, level) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $current_referrer_id, $new_user_id, $level);
            $stmt->execute();
            
            // Buscar o próximo referrer na cadeia
            $stmt = $conn->prepare("SELECT referrer_id FROM users WHERE id = ?");
            $stmt->bind_param("i", $current_referrer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($parent = $result->fetch_assoc()) {
                $current_referrer_id = $parent['referrer_id'];
                $level++;
            } else {
                break;
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro ao registrar cadeia de indicações: " . $e->getMessage());
        return false;
    }
}

/**
 * Cria um afiliado se não existir
 */
function createAffiliateIfNotExists($conn, $user_id, $username) {
    try {
        // Verificar se já é afiliado
        $stmt = $conn->prepare("SELECT id FROM affiliates WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Gerar código de afiliado
            $affiliate_code = generateAffiliateCode($username, $user_id);
            
            // Verificar se o código já existe (improvável, mas por segurança)
            $stmt = $conn->prepare("SELECT id FROM affiliates WHERE affiliate_code = ?");
            $stmt->bind_param("s", $affiliate_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Se existir, adicionar timestamp
                $affiliate_code = $affiliate_code . time();
            }
            
            // Criar registro de afiliado
            $stmt = $conn->prepare("INSERT INTO affiliates (user_id, affiliate_code, cpa_commission_rate, revshare_commission_rate) VALUES (?, ?, 10.00, 5.00)");
            $stmt->bind_param("is", $user_id, $affiliate_code);
            $stmt->execute();
            
            // Atualizar status de afiliado do usuário
            $stmt = $conn->prepare("UPDATE users SET affiliate_status = 1 WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            return $affiliate_code;
        } else {
            // Já é afiliado, buscar código
            $stmt = $conn->prepare("SELECT affiliate_code FROM affiliates WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $affiliate = $result->fetch_assoc();
            return $affiliate['affiliate_code'];
        }
    } catch (Exception $e) {
        error_log("Erro ao criar afiliado: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtém estatísticas do afiliado
 */
function getAffiliateStats($conn, $user_id) {
    try {
        $stats = [
            'clicks' => 0,
            'signups' => 0,
            'deposits' => 0,
            'total_commission' => 0,
            'cpa_commission' => 0,
            'revshare_commission' => 0,
            'balance' => 0
        ];
        
        // Buscar ID do afiliado
        $stmt = $conn->prepare("SELECT id FROM affiliates WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($affiliate = $result->fetch_assoc()) {
            $affiliate_id = $affiliate['id'];
            
            // Cliques
            $stmt = $conn->prepare("SELECT COUNT(*) as clicks FROM affiliate_clicks WHERE affiliate_id = ?");
            $stmt->bind_param("i", $affiliate_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['clicks'] = $result->fetch_assoc()['clicks'];
            
            // Cadastros
            $stmt = $conn->prepare("SELECT COUNT(*) as signups FROM affiliate_conversions WHERE affiliate_id = ? AND conversion_type = 'signup'");
            $stmt->bind_param("i", $affiliate_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['signups'] = $result->fetch_assoc()['signups'];
            
            // Depósitos
            $stmt = $conn->prepare("SELECT COUNT(*) as deposits FROM affiliate_conversions WHERE affiliate_id = ? AND conversion_type = 'deposit'");
            $stmt->bind_param("i", $affiliate_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['deposits'] = $result->fetch_assoc()['deposits'];
            
            // Comissões CPA
            $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as cpa_commission FROM commissions WHERE affiliate_id = ? AND type = 'CPA'");
            $stmt->bind_param("i", $affiliate_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['cpa_commission'] = $result->fetch_assoc()['cpa_commission'];
            
            // Comissões RevShare
            $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as revshare_commission FROM commissions WHERE affiliate_id = ? AND type = 'RevShare'");
            $stmt->bind_param("i", $affiliate_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['revshare_commission'] = $result->fetch_assoc()['revshare_commission'];
            
            $stats['total_commission'] = $stats['cpa_commission'] + $stats['revshare_commission'];
        }
        
        // Saldo do usuário
        $stmt = $conn->prepare("SELECT affiliate_balance FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stats['balance'] = $user['affiliate_balance'] ?? 0;
        
        return $stats;
    } catch (Exception $e) {
        error_log("Erro ao obter estatísticas do afiliado: " . $e->getMessage());
        return false;
    }
}
?>

