<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/bspay_config.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}

// Verificar se o admin tem 2FA configurado
$stmt = $conn->prepare("SELECT two_factor_secret FROM users WHERE id = ? AND is_admin = 1");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || empty($user['two_factor_secret'])) {
    header("Location: setup_2fa.php");
    exit();
}

$success = '';
$error = '';

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_bspay') {
        $client_id = trim($_POST['client_id'] ?? '');
        $client_secret = trim($_POST['client_secret'] ?? '');
        $webhook_url = trim($_POST['webhook_url'] ?? '');
        
        if ($client_id && $client_secret && $webhook_url) {
            try {
                error_log("Arquivo executado:  linha 37 " . __FILE__);
                BSPayConfig::setClientId($client_id);
                BSPayConfig::setClientSecret($client_secret);
                BSPayConfig::setWebhookUrl($webhook_url);
                $success = 'Configurações BSPay atualizadas com sucesso!';
            } catch (Exception $e) {
                $error = 'Erro ao salvar configurações: ' . $e->getMessage();
            }
        } else {
            $error = 'Todos os campos são obrigatórios!';
        }
    }
    
    if ($action === 'upload_image') {
        $image_type = $_POST['image_type'] ?? '';
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = '../assets/images/';
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_filename = $image_type . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Salva o caminho da imagem no banco
                    $stmt = $conn->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
                    $config_key = 'image_' . $image_type;
                    $stmt->bind_param("sss", $config_key, $new_filename, $new_filename);
                    $stmt->execute();
                    
                    $success = 'Imagem ' . $image_type . ' atualizada com sucesso!';
                } else {
                    $error = 'Erro ao fazer upload da imagem.';
                }
            } else {
                $error = 'Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.';
            }
        } else {
            $error = 'Erro no upload da imagem.';
        }
    }
}

// Busca configurações atuais
error_log("Arquivo executado: Linha 84 " . __FILE__);
$current_client_id = BSPayConfig::getClientId();
$current_client_secret = BSPayConfig::getClientSecret();
$current_webhook_url = BSPayConfig::getWebhookUrl();

// Busca imagens atuais
function getImagePath($type) {
    global $conn;
    $stmt = $conn->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
    $config_key = 'image_' . $type;
    $stmt->bind_param("s", $config_key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return '../assets/images/' . $row['valor'];
    }
    return '../assets/images/' . $type . '.png'; // Imagem padrão
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Admin BSPay</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .card-header h2 {
            color: #333;
            font-size: 24px;
        }
        
        .card-body {
            padding: 30px;
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .image-item {
            text-align: center;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        
        .nav-links {
            text-align: center;
            margin-top: 30px;
        }
        
        .nav-links a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav-links a:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚙️ Configurações do Sistema</h1>
        <p>Gerencie as configurações do BSPay e imagens do sistema</p>
        <div style="margin-top: 15px;">
            <span style="background: #007bff; color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px; margin-right: 10px;">Plataforma liberada por QICBUSINESS</span>
            <a href="https://t.me/+QNv-hPVLFEAxNjAx" target="_blank" style="background: #ff6b35; border: 1px solid #ff6b35; color: white; padding: 5px 15px; border-radius: 5px; text-decoration: none; font-size: 12px;">
                📱 Acesse nosso grupo
            </a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <!-- Configurações BSPay -->
        <div class="card">
            <div class="card-header">
                <h2>🔑 Configurações BSPay</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_bspay">
                    
                    <div class="form-group">
                        <label for="client_id">Client ID</label>
                        <input type="text" id="client_id" name="client_id" value="<?= htmlspecialchars($current_client_id) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="client_secret">Client Secret</label>
                        <input type="password" id="client_secret" name="client_secret" value="<?= htmlspecialchars($current_client_secret) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="webhook_url">URL do Webhook</label>
                        <input type="url" id="webhook_url" name="webhook_url" value="<?= htmlspecialchars($current_webhook_url) ?>" required>
                    </div>
                    
                    <button type="submit" class="btn">Salvar Configurações BSPay</button>
                </form>
            </div>
        </div>
        
        <!-- Gerenciamento de Imagens -->
        <div class="card">
            <div class="card-header">
                <h2>🖼️ Gerenciamento de Imagens</h2>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_image">
                    
                    <div class="form-group">
                        <label for="image_type">Tipo de Imagem</label>
                        <select id="image_type" name="image_type" required>
                            <option value="">Selecione o tipo</option>
                            <option value="banana">Banana</option>
                            <option value="maça">Maçã</option>
                            <option value="uva">Uva</option>
                            <option value="logo">Logo do Site</option>
                            <option value="background">Imagem de Fundo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Selecionar Imagem</label>
                        <input type="file" id="image" name="image" accept="image/*" required>
                    </div>
                    
                    <button type="submit" class="btn">Upload Imagem</button>
                </form>
                
                <!-- Preview das imagens atuais -->
                <div class="image-grid">
                    <div class="image-item">
                        <h4>Banana</h4>
                        <img src="<?= getImagePath('banana') ?>" alt="Banana" class="image-preview" onerror="this.src='../assets/images/banana.png'">
                    </div>
                    <div class="image-item">
                        <h4>Maçã</h4>
                        <img src="<?= getImagePath('maça') ?>" alt="Maçã" class="image-preview" onerror="this.src='../assets/images/maça.png'">
                    </div>
                    <div class="image-item">
                        <h4>Uva</h4>
                        <img src="<?= getImagePath('uva') ?>" alt="Uva" class="image-preview" onerror="this.src='../assets/images/uva.png'">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="nav-links">
            <a href="index.php">← Voltar ao Painel</a>
            <a href="../inicio.php">Ir para o Site</a>
        </div>
    </div>
</body>
</html>

