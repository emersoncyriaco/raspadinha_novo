<?php
/**
 * Sistema de rastreamento de afiliados
 * Este arquivo deve ser incluído no início de páginas que podem receber tráfego de afiliados
 */

require_once 'includes/db.php';
require_once 'includes/affiliate_functions.php';

// Iniciar sessão se não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se há parâmetro 'ref' na URL
if (isset($_GET['ref']) && !empty($_GET['ref'])) {
    $affiliate_code = trim($_GET['ref']);
    
    // Validar o código do afiliado (apenas letras, números e alguns caracteres especiais)
    if (preg_match('/^[a-zA-Z0-9_-]+$/', $affiliate_code)) {
        // Armazenar em cookie por 30 dias
        setcookie('affiliate_ref', $affiliate_code, time() + (86400 * 30), "/", "", false, true);
        
        // Também armazenar na sessão para uso imediato
        $_SESSION['affiliate_ref'] = $affiliate_code;
        
        // Registrar o clique
        registerAffiliateClick($conn, $affiliate_code);
        
        // Redirecionar para a mesma página sem o parâmetro ref para limpar a URL
        $current_url = strtok($_SERVER["REQUEST_URI"], '?');
        $query_params = $_GET;
        unset($query_params['ref']);
        
        if (!empty($query_params)) {
            $current_url .= '?' . http_build_query($query_params);
        }
        
        header("Location: " . $current_url);
        exit();
    }
}

/**
 * Função para obter o código do afiliado atual (da sessão ou cookie)
 */
function getCurrentAffiliateCode() {
    // Primeiro verificar na sessão
    if (isset($_SESSION['affiliate_ref']) && !empty($_SESSION['affiliate_ref'])) {
        return $_SESSION['affiliate_ref'];
    }
    
    // Se não estiver na sessão, verificar no cookie
    if (isset($_COOKIE['affiliate_ref']) && !empty($_COOKIE['affiliate_ref'])) {
        // Também armazenar na sessão para próximas verificações
        $_SESSION['affiliate_ref'] = $_COOKIE['affiliate_ref'];
        return $_COOKIE['affiliate_ref'];
    }
    
    return null;
}

/**
 * Função para obter o ID do usuário referrer baseado no código do afiliado
 */
function getReferrerUserId($conn, $affiliate_code) {
    if (!$affiliate_code) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT user_id FROM affiliates WHERE affiliate_code = ? AND is_active = 1");
        $stmt->bind_param("s", $affiliate_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($affiliate = $result->fetch_assoc()) {
            return $affiliate['user_id'];
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar referrer: " . $e->getMessage());
    }
    
    return null;
}
?>

