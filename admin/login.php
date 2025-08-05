<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $conn->prepare("SELECT id, password, is_admin FROM users WHERE email = ?");
    if (!$stmt) {
        die("Erro na preparação da query: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($senha, $user["password"]) && $user["is_admin"] == 1) {
            $_SESSION["usuario_id"] = $user["id"];
            $_SESSION["is_admin"] = $user["is_admin"];
            
            // Log de acesso bem-sucedido
            $log_entry = date("Y-m-d H:i:s") . " - Login bem-sucedido: " . $email . " (IP: " . $_SERVER["REMOTE_ADDR"] . ")\n";
            file_put_contents("security.log", $log_entry, FILE_APPEND | LOCK_EX);
            
            header("Location: index.php");
            exit;
        } else {
            $erro = "Senha incorreta ou não autorizado.";
            
            // Log de tentativa de acesso não autorizado
            $log_entry = date('Y-m-d H:i:s') . " - Tentativa de login não autorizado: " . $email . " (IP: " . $_SERVER['REMOTE_ADDR'] . ")\n";
            file_put_contents('security.log', $log_entry, FILE_APPEND | LOCK_EX);
        }
    } else {
        $erro = "Usuário não encontrado.";
        
        // Log de tentativa de acesso com usuário inexistente
        $log_entry = date('Y-m-d H:i:s') . " - Tentativa de login com usuário inexistente: " . $email . " (IP: " . $_SERVER['REMOTE_ADDR'] . ")\n";
        file_put_contents('security.log', $log_entry, FILE_APPEND | LOCK_EX);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Raspa Sorte</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #666;
            margin: 0;
        }
        .form-control {
            border-radius: 0.75rem;
            border: 1px solid #ddd;
            padding: 0.875rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 0.875rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .alert {
            border-radius: 0.75rem;
            border: none;
        }
        .security-info {
            background: #f8f9fa;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-top: 1.5rem;
            text-align: center;
        }
        .security-info small {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="bi bi-shield-lock"></i> Painel Admin</h2>
            <p>Acesso seguro ao painel administrativo</p>
        </div>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-envelope"></i> Email
                </label>
                <input type="email" name="email" class="form-control" placeholder="seu@email.com" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-lock"></i> Senha
                </label>
                <input type="password" name="senha" class="form-control" placeholder="Sua senha" required>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
            </button>
        </form>

        <div class="security-info">
            <small>
                <i class="bi bi-info-circle"></i>
                Todas as tentativas de login são registradas por segurança
            </small>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>