<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/security.php';

// Limpa dados antigos de segurança
Security::cleanupSecurityData();

$error = '';
$success = '';

// Verifica se já está logado
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user && $user['is_admin']) {
        header('Location: index.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Verifica tentativas de login
    $login_check = Security::checkLoginAttempts($ip);
    if ($login_check['blocked']) {
        $minutes = ceil($login_check['time_remaining'] / 60);
        $error = "Muitas tentativas de login. Tente novamente em {$minutes} minutos.";
        Security::logSecurityEvent('login_blocked', ['ip' => $ip, 'time_remaining' => $login_check['time_remaining']]);
    } else {
        // Valida CSRF token
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!Security::validateCSRFToken($csrf_token)) {
            $error = 'Token de segurança inválido';
            Security::logSecurityEvent('csrf_token_invalid', ['ip' => $ip]);
        } else {
            $email = Security::sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'Email e senha são obrigatórios';
            } elseif (!Security::validateEmail($email)) {
                $error = 'Email inválido';
            } else {
                // Busca o usuário
                $stmt = $conn->prepare("SELECT id, name, email, password, is_admin FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                
                if ($user && Security::verifyPassword($password, $user['password'])) {
                    if ($user['is_admin']) {
                        // Login bem-sucedido
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['is_admin'] = true;
                        
                        // Regenera ID da sessão por segurança
                        session_regenerate_id(true);
                        
                        Security::recordLoginAttempt($ip, true);
                        Security::logSecurityEvent('admin_login_success', [
                            'user_id' => $user['id'],
                            'email' => $email
                        ]);
                        
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = 'Acesso negado. Apenas administradores podem acessar esta área.';
                        Security::logSecurityEvent('admin_access_denied', [
                            'user_id' => $user['id'],
                            'email' => $email
                        ]);
                    }
                } else {
                    $error = 'Email ou senha incorretos';
                    Security::recordLoginAttempt($ip, false);
                    Security::logSecurityEvent('admin_login_failed', [
                        'email' => $email,
                        'ip' => $ip
                    ]);
                }
            }
        }
    }
}

// Gera novo token CSRF
$csrf_token = Security::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo - BSPay Gateway</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .security-badge {
            background: linear-gradient(135deg, #43e97b, #38f9d7);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            border: 1px solid;
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-color: rgba(220, 53, 69, 0.2);
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border-color: rgba(40, 167, 69, 0.2);
        }
        
        .security-info {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #667eea;
        }
        
        .security-info h4 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .security-info ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .security-info li {
            margin-bottom: 5px;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .login-attempts {
            font-size: 12px;
            color: #666;
            text-align: center;
            margin-top: 15px;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <div class="security-badge">🔒 ÁREA SEGURA</div>
            <h1>🛡️ Admin Login</h1>
            <p>Acesso restrito a administradores</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error shake"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="form-group">
                <label for="email">📧 Email do Administrador</label>
                <input type="email" id="email" name="email" required autocomplete="username" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">🔑 Senha</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn">🚀 Entrar no Painel</button>
        </form>
        
        <div class="security-info">
            <h4>🛡️ Recursos de Segurança Ativos:</h4>
            <ul>
                <li>Proteção CSRF</li>
                <li>Controle de tentativas de login</li>
                <li>Criptografia de senhas</li>
                <li>Log de eventos de segurança</li>
                <li>Validação de entrada</li>
            </ul>
        </div>
        
        <div class="login-attempts">
            Máximo de 5 tentativas por IP. Bloqueio de 15 minutos após exceder.
        </div>
        
        <a href="../inicio.php" class="back-link">← Voltar ao Site</a>
    </div>
    
    <script>
        // Adiciona efeito visual ao formulário
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = this.querySelector('.btn');
            btn.innerHTML = '🔄 Verificando...';
            btn.disabled = true;
        });
        
        // Remove classe shake após animação
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-error');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.classList.remove('shake');
                }, 500);
            });
        });
    </script>
</body>
</html>

