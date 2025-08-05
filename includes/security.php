<?php
/**
 * Classe de Segurança para o Sistema BSPay
 */
class Security {
    
    /**
     * Gera um token CSRF
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Valida o token CSRF
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitiza entrada de dados
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valida se é um email válido
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Gera hash seguro para senha
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Verifica senha
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Controle de tentativas de login
     */
    public static function checkLoginAttempts($ip) {
        $max_attempts = 5;
        $lockout_time = 900; // 15 minutos
        
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        $attempts = $_SESSION['login_attempts'];
        
        // Remove tentativas antigas
        $current_time = time();
        foreach ($attempts as $attempt_ip => $data) {
            if ($current_time - $data['last_attempt'] > $lockout_time) {
                unset($_SESSION['login_attempts'][$attempt_ip]);
            }
        }
        
        if (isset($attempts[$ip])) {
            if ($attempts[$ip]['count'] >= $max_attempts) {
                $time_remaining = $lockout_time - ($current_time - $attempts[$ip]['last_attempt']);
                if ($time_remaining > 0) {
                    return [
                        'blocked' => true,
                        'time_remaining' => $time_remaining
                    ];
                }
            }
        }
        
        return ['blocked' => false];
    }
    
    /**
     * Registra tentativa de login
     */
    public static function recordLoginAttempt($ip, $success = false) {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        if ($success) {
            // Remove tentativas em caso de sucesso
            unset($_SESSION['login_attempts'][$ip]);
        } else {
            // Incrementa tentativas em caso de falha
            if (!isset($_SESSION['login_attempts'][$ip])) {
                $_SESSION['login_attempts'][$ip] = ['count' => 0, 'last_attempt' => 0];
            }
            $_SESSION['login_attempts'][$ip]['count']++;
            $_SESSION['login_attempts'][$ip]['last_attempt'] = time();
        }
    }
    
    /**
     * Valida força da senha
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'A senha deve ter pelo menos 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra maiúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos uma letra minúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um número';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'A senha deve conter pelo menos um caractere especial';
        }
        
        return $errors;
    }
    
    /**
     * Gera log de segurança
     */
    public static function logSecurityEvent($event, $details = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details,
            'session_id' => session_id()
        ];
        
        $log_line = json_encode($log_entry) . "\n";
        file_put_contents('security.log', $log_line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Valida upload de arquivo
     */
    public static function validateFileUpload($file, $allowed_types = ['image/jpeg', 'image/png', 'image/gif'], $max_size = 5242880) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erro no upload do arquivo';
            return $errors;
        }
        
        if ($file['size'] > $max_size) {
            $errors[] = 'Arquivo muito grande. Máximo: ' . ($max_size / 1024 / 1024) . 'MB';
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = 'Tipo de arquivo não permitido';
        }
        
        // Verifica se é realmente uma imagem
        if (strpos($mime_type, 'image/') === 0) {
            $image_info = getimagesize($file['tmp_name']);
            if ($image_info === false) {
                $errors[] = 'Arquivo não é uma imagem válida';
            }
        }
        
        return $errors;
    }
    
    /**
     * Gera nome seguro para arquivo
     */
    public static function generateSecureFilename($original_name) {
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^a-zA-Z0-9]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
        $unique_id = uniqid();
        return $safe_name . '_' . $unique_id . '.' . $extension;
    }
    
    /**
     * Verifica se o usuário é admin
     */
    public static function requireAdmin($conn) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user || !$user['is_admin']) {
            self::logSecurityEvent('unauthorized_admin_access', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'requested_page' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);
            header('Location: ../login.php');
            exit;
        }
        
        return true;
    }
    
    /**
     * Limpa dados antigos de segurança
     */
    public static function cleanupSecurityData() {
        // Limpa tentativas de login antigas
        if (isset($_SESSION['login_attempts'])) {
            $current_time = time();
            foreach ($_SESSION['login_attempts'] as $ip => $data) {
                if ($current_time - $data['last_attempt'] > 900) {
                    unset($_SESSION['login_attempts'][$ip]);
                }
            }
        }
        
        // Limpa logs antigos (manter apenas últimos 30 dias)
        $log_file = 'security.log';
        if (file_exists($log_file)) {
            $lines = file($log_file);
            $cutoff_date = date('Y-m-d', strtotime('-30 days'));
            $new_lines = [];
            
            foreach ($lines as $line) {
                $data = json_decode($line, true);
                if ($data && isset($data['timestamp'])) {
                    if (substr($data['timestamp'], 0, 10) >= $cutoff_date) {
                        $new_lines[] = $line;
                    }
                }
            }
            
            file_put_contents($log_file, implode('', $new_lines));
        }
    }
}
?>

