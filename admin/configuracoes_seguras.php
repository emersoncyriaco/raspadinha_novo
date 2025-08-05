<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/bspay_config.php';
require_once '../includes/security.php';

// Verifica se é admin com segurança
Security::requireAdmin($conn);

$success = '';
$error = '';

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Valida CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!Security::validateCSRFToken($csrf_token)) {
        $error = 'Token de segurança inválido';
        Security::logSecurityEvent('csrf_token_invalid', [
            'user_id' => $_SESSION['user_id'],
            'action' => 'configuracoes_update'
        ]);
    } else {
        $action = Security::sanitizeInput($_POST['action'] ?? '');
        
        if ($action === 'update_bspay') {
            $client_id = Security::sanitizeInput($_POST['client_id'] ?? '');
            $client_secret = Security::sanitizeInput($_POST['client_secret'] ?? '');
            $webhook_url = Security::sanitizeInput($_POST['webhook_url'] ?? '');
            
            // Validações
            if (empty($client_id) || empty($client_secret) || empty($webhook_url)) {
                $error = 'Todos os campos são obrigatórios!';
            } elseif (!filter_var($webhook_url, FILTER_VALIDATE_URL)) {
                $error = 'URL do webhook inválida!';
            } elseif (strlen($client_id) < 10 || strlen($client_secret) < 20) {
                $error = 'Credenciais BSPay parecem inválidas!';
            } else {
                try {
                    BSPayConfig::setClientId($client_id);
                    BSPayConfig::setClientSecret($client_secret);
                    BSPayConfig::setWebhookUrl($webhook_url);
                    
                    $success = 'Configurações BSPay atualizadas com sucesso!';
                    Security::logSecurityEvent('bspay_config_updated', [
                        'user_id' => $_SESSION['user_id'],
                        'client_id' => substr($client_id, 0, 10) . '...',
                        'webhook_url' => $webhook_url
                    ]);
                } catch (Exception $e) {
                    $error = 'Erro ao salvar configurações: ' . $e->getMessage();
                    Security::logSecurityEvent('bspay_config_error', [
                        'user_id' => $_SESSION['user_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        if ($action === 'upload_image') {
            $image_type = Security::sanitizeInput($_POST['image_type'] ?? '');
            $allowed_types = ['banana', 'maça', 'uva', 'logo', 'background'];
            
            if (!in_array($image_type, $allowed_types)) {
                $error = 'Tipo de imagem inválido!';
            } elseif (isset($_FILES['image'])) {
                // Valida o upload
                $validation_errors = Security::validateFileUpload($_FILES['image']);
                
                if (!empty($validation_errors)) {
                    $error = implode(', ', $validation_errors);
                } else {
                    $upload_dir = '../assets/images/';
                    
                    // Cria diretório se não existir
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Gera nome seguro para o arquivo
                    $secure_filename = Security::generateSecureFilename($_FILES['image']['name']);
                    $upload_path = $upload_dir . $secure_filename;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        // Remove imagem anterior se existir
                        $stmt = $conn->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
                        $config_key = 'image_' . $image_type;
                        $stmt->bind_param("s", $config_key);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            $old_file = $upload_dir . $row['valor'];
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }
                        
                        // Salva o novo caminho no banco
                        $stmt = $conn->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = ?");
                        $stmt->bind_param("sss", $config_key, $secure_filename, $secure_filename);
                        $stmt->execute();
                        
                        $success = 'Imagem ' . $image_type . ' atualizada com sucesso!';
                        Security::logSecurityEvent('image_uploaded', [
                            'user_id' => $_SESSION['user_id'],
                            'image_type' => $image_type,
                            'filename' => $secure_filename
                        ]);
                    } else {
                        $error = 'Erro ao fazer upload da imagem.';
                    }
                }
            } else {
                $error = 'Nenhuma imagem selecionada.';
            }
        }
    }
}

// Busca configurações atuais
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

// Gera novo token CSRF
$csrf_token = Security::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações Seguras - Admin BSPay</title>
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
            position: relative;
        }
        
        .security-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-bottom: 1px solid #e9ecef;
            position: relative;
        }
        
        .card-header h2 {
            color: #333;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .security-indicator {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input[type="password"] {
            font-family: monospace;
        }
        
        .btn {
            padding: 15px 30px;
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
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
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
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border: 3px solid #ddd;
            border-radius: 10px;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .image-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        .image-item {
            text-align: center;
            padding: 25px;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            transition: all 0.3s ease;
        }
        
        .image-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }
        
        .nav-links {
            text-align: center;
            margin-top: 30px;
        }
        
        .nav-links a {
            display: inline-block;
            margin: 0 15px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .nav-links a:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .security-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
            font-weight: 500;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .file-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="security-badge">🔒 ÁREA SEGURA</div>
        <h1>⚙️ Configurações Seguras do Sistema</h1>
        <p>Gerencie as configurações do BSPay e imagens do sistema com segurança</p>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error">🚨 <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="security-warning">
            <strong>⚠️ Aviso de Segurança:</strong> Estas configurações afetam diretamente o funcionamento do gateway de pagamento. 
            Certifique-se de que as credenciais estão corretas antes de salvar.
        </div>
        
        <!-- Configurações BSPay -->
        <div class="card">
            <div class="card-header">
                <h2>🔑 Configurações BSPay</h2>
                <div class="security-indicator">CRIPTOGRAFADO</div>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="update_bspay">
                    
                    <div class="form-group">
                        <label for="client_id">🆔 Client ID</label>
                        <input type="text" id="client_id" name="client_id" 
                               value="<?= htmlspecialchars($current_client_id) ?>" 
                               required minlength="10" maxlength="100">
                        <div class="file-info">Identificador único fornecido pelo BSPay</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="client_secret">🔐 Client Secret</label>
                        <input type="password" id="client_secret" name="client_secret" 
                               value="<?= htmlspecialchars($current_client_secret) ?>" 
                               required minlength="20" maxlength="200">
                        <div class="password-strength">Chave secreta - mantenha em segurança</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="webhook_url">🌐 URL do Webhook</label>
                        <input type="url" id="webhook_url" name="webhook_url" 
                               value="<?= htmlspecialchars($current_webhook_url) ?>" 
                               required pattern="https://.*">
                        <div class="file-info">URL que receberá as notificações de pagamento (deve usar HTTPS)</div>
                    </div>
                    
                    <button type="submit" class="btn">💾 Salvar Configurações BSPay</button>
                </form>
            </div>
        </div>
        
        <!-- Gerenciamento de Imagens -->
        <div class="card">
            <div class="card-header">
                <h2>🖼️ Gerenciamento Seguro de Imagens</h2>
                <div class="security-indicator">VALIDADO</div>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="upload_image">
                    
                    <div class="form-group">
                        <label for="image_type">📂 Tipo de Imagem</label>
                        <select id="image_type" name="image_type" required>
                            <option value="">Selecione o tipo</option>
                            <option value="banana">🍌 Banana</option>
                            <option value="maça">🍎 Maçã</option>
                            <option value="uva">🍇 Uva</option>
                            <option value="logo">🏢 Logo do Site</option>
                            <option value="background">🖼️ Imagem de Fundo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">📁 Selecionar Imagem</label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif" required>
                        <div class="file-info">
                            Formatos aceitos: JPG, PNG, GIF | Tamanho máximo: 5MB | 
                            Dimensões recomendadas: 512x512px
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">📤 Upload Seguro</button>
                </form>
                
                <!-- Preview das imagens atuais -->
                <div class="image-grid">
                    <div class="image-item">
                        <h4>🍌 Banana</h4>
                        <img src="<?= getImagePath('banana') ?>" alt="Banana" class="image-preview" 
                             onerror="this.src='../assets/images/banana.png'">
                    </div>
                    <div class="image-item">
                        <h4>🍎 Maçã</h4>
                        <img src="<?= getImagePath('maça') ?>" alt="Maçã" class="image-preview" 
                             onerror="this.src='../assets/images/maça.png'">
                    </div>
                    <div class="image-item">
                        <h4>🍇 Uva</h4>
                        <img src="<?= getImagePath('uva') ?>" alt="Uva" class="image-preview" 
                             onerror="this.src='../assets/images/uva.png'">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="nav-links">
            <a href="index.php">🏠 Voltar ao Painel</a>
            <a href="../inicio.php">🌐 Ir para o Site</a>
            <a href="logout.php">🚪 Sair com Segurança</a>
        </div>
    </div>
    
    <script>
        // Validação em tempo real
        document.getElementById('client_secret').addEventListener('input', function() {
            const strength = this.nextElementSibling;
            const value = this.value;
            
            if (value.length < 20) {
                strength.textContent = 'Muito curto - mínimo 20 caracteres';
                strength.style.color = '#dc3545';
            } else if (value.length < 40) {
                strength.textContent = 'Comprimento adequado';
                strength.style.color = '#ffc107';
            } else {
                strength.textContent = 'Comprimento seguro';
                strength.style.color = '#28a745';
            }
        });
        
        // Validação de URL
        document.getElementById('webhook_url').addEventListener('input', function() {
            const info = this.nextElementSibling;
            const value = this.value;
            
            if (value && !value.startsWith('https://')) {
                info.textContent = '⚠️ URL deve usar HTTPS para segurança';
                info.style.color = '#dc3545';
            } else {
                info.textContent = 'URL que receberá as notificações de pagamento (deve usar HTTPS)';
                info.style.color = '#666';
            }
        });
        
        // Preview de imagem antes do upload
        document.getElementById('image').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Cria preview temporário
                    let preview = document.getElementById('temp-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = 'temp-preview';
                        preview.style.maxWidth = '200px';
                        preview.style.maxHeight = '200px';
                        preview.style.border = '2px solid #667eea';
                        preview.style.borderRadius = '10px';
                        preview.style.marginTop = '10px';
                        document.getElementById('image').parentNode.appendChild(preview);
                    }
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>

