<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/GoogleAuthenticator.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT is_admin, two_factor_secret FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !$user['is_admin']) {
    header("Location: ../login.php");
    exit();
}

$ga = new PHPGangsta_GoogleAuthenticator();

// Se já tem 2FA configurado, redirecionar
/*
if (!empty($user["two_factor_secret"])) {
    header("Location: index.php");
    exit();
}
*/

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'setup') {
        $secret = $_POST['secret'];
        $code = $_POST['code'];
        
        // Verificar o código
        // if ($ga->verifyCode($secret, $code, 2)) {
            // Salvar o segredo no banco
            $stmt = $conn->prepare("UPDATE users SET two_factor_secret = ? WHERE id = ?");
            $stmt->bind_param("si", $secret, $_SESSION['usuario_id']);
            
            if ($stmt->execute()) {
                $message = "2FA configurado com sucesso! Você será redirecionado para o painel.";
                header("refresh:3;url=index.php");
            } else {
                $message = "Erro ao salvar configuração 2FA.";
            }
        // } else {
        //     $message = "Código inválido. Tente novamente.";
        // }
    }
}

// Gerar novo segredo
$secret = $ga->createSecret();
$qrCodeUrl = $ga->getQRCodeGoogleUrl('Raspa Sorte Admin', $secret, 'Raspa Sorte');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar 2FA - Admin</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .setup-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .qr-code {
            text-align: center;
            margin: 2rem 0;
        }

        .qr-code img {
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            padding: 1rem;
            background: white;
        }

        .secret-code {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            font-family: monospace;
            word-break: break-all;
            margin: 1rem 0;
        }

        .step {
            margin: 1.5rem 0;
            padding: 1rem;
            border-left: 4px solid #007bff;
            background: #f8f9fa;
            border-radius: 0 0.5rem 0.5rem 0;
        }

        .step h5 {
            color: #007bff;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-container">
            <div class="text-center mb-4">
                <h2><i class="bi bi-shield-lock"></i> Configurar Autenticação de Dois Fatores</h2>
                <p class="text-muted">Configure o 2FA para aumentar a segurança do painel administrativo</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="step">
                <h5><i class="bi bi-1-circle"></i> Baixe o Google Authenticator</h5>
                <p>Instale o aplicativo Google Authenticator no seu smartphone:</p>
                <ul>
                    <li><strong>Android:</strong> <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Google Play Store</a></li>
                    <li><strong>iOS:</strong> <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank">App Store</a></li>
                </ul>
            </div>

            <div class="step">
                <h5><i class="bi bi-2-circle"></i> Escaneie o QR Code</h5>
                <p>Abra o Google Authenticator e escaneie o código QR abaixo:</p>
                
                <div class="qr-code">
                    <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code 2FA">
                </div>

                <p><strong>Ou digite manualmente o código secreto:</strong></p>
                <div class="secret-code">
                    <?php echo $secret; ?>
                </div>
            </div>

            <div class="step">
                <h5><i class="bi bi-3-circle"></i> Confirme a Configuração</h5>
                <p>Digite o código de 6 dígitos gerado pelo Google Authenticator:</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="setup">
                    <input type="hidden" name="secret" value="<?php echo $secret; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Código de Verificação</label>
                        <input type="text" class="form-control" name="code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required>
                        <div class="form-text">Digite o código de 6 dígitos do seu aplicativo</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle"></i> Confirmar e Ativar 2FA
                    </button>
                </form>
            </div>

            <div class="alert alert-warning mt-4">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Importante:</strong> Guarde o código secreto em local seguro. Se perder o acesso ao seu telefone, você precisará deste código para reconfigurar o 2FA.
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

