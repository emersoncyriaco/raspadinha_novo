<?php
session_start();
require_once '../includes/db.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
}

// Verificar se o admin tem 2FA configurado
$stmt = $conn->prepare("SELECT two_factor_secret FROM users WHERE id = ? AND is_admin = 1");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || empty($user['two_factor_secret'])) {
    header("Location: setup_2fa.php");

}

$message = '';

// Criar tabela de configurações se não existir
$conn->query("
    CREATE TABLE IF NOT EXISTS global_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

// Criar tabela de configurações por usuário se não existir
$conn->query("
    CREATE TABLE IF NOT EXISTS user_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        min_deposit_amount DECIMAL(10,2) DEFAULT NULL,
        min_withdrawal_amount DECIMAL(10,2) DEFAULT NULL,
        influence_mode_enabled TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_settings (user_id)
    )");

// Criar tabela de banners se não existir
$conn->query("
    CREATE TABLE IF NOT EXISTS banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(500) NOT NULL,
        file_size INT DEFAULT 0,
        file_type VARCHAR(100),
        width INT DEFAULT 0,
        height INT DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        position VARCHAR(50) DEFAULT 'header',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

// Configurações padrão
$default_settings = [
    'default_revshare_rate' => ['value' => '5.0', 'description' => 'Taxa padrão de RevShare (%)'],
    'min_payout_amount' => ['value' => '10.00', 'description' => 'Valor mínimo para saque global (R$)'],
    'max_payout_amount' => ['value' => '5000.00', 'description' => 'Valor máximo para saque (R$)'],
    'min_deposit_amount' => ['value' => '5.00', 'description' => 'Valor mínimo para depósito global (R$)'],
    'initial_bonus_amount' => ['value' => '10.00', 'description' => 'Valor do bônus inicial para novos usuários (R$)'],
    'initial_bonus_enabled' => ['value' => '1', 'description' => 'Bônus inicial ativo (1=Sim, 0=Não)'],
    'affiliate_system_enabled' => ['value' => '1', 'description' => 'Sistema de afiliados ativo (1=Sim, 0=Não)'],
    'auto_approve_payouts' => ['value' => '0', 'description' => 'Aprovar saques automaticamente (1=Sim, 0=Não)'],
    'commission_delay_hours' => ['value' => '24', 'description' => 'Delay para liberar comissões (horas)'],
    'max_affiliate_levels' => ['value' => '4', 'description' => 'Número máximo de níveis de afiliados'],
    'level_2_percentage' => ['value' => '20', 'description' => 'Porcentagem do nível 2 (% da comissão do nível 1)'],
    'level_3_percentage' => ['value' => '10', 'description' => 'Porcentagem do nível 3 (% da comissão do nível 1)'],
    'level_4_percentage' => ['value' => '5', 'description' => 'Porcentagem do nível 4 (% da comissão do nível 1)'],
];

// Inserir configurações padrão se não existirem
foreach ($default_settings as $key => $data) {
    $stmt = $conn->prepare("INSERT IGNORE INTO global_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $key, $data['value'], $data['description']);
    $stmt->execute();
}

// Processar atualizações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'update_settings') {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $conn->prepare("UPDATE global_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->bind_param("ss", $value, $key);
            $stmt->execute();
        }
        $message = "Configurações atualizadas com sucesso!";
    }
    
    // Processar atualizações de configurações por usuário
    if (isset($_POST['action']) && $_POST['action'] == 'update_user_settings') {
        $user_id = $_POST['user_id'];
        $min_deposit = !empty($_POST['min_deposit_amount']) ? $_POST['min_deposit_amount'] : NULL;
        $min_withdrawal = !empty($_POST['min_withdrawal_amount']) ? $_POST['min_withdrawal_amount'] : NULL;
        $influence_mode = isset($_POST['influence_mode_enabled']) ? 1 : 0;
        
        // Inserir ou atualizar configurações do usuário
        $stmt = $conn->prepare("
            INSERT INTO user_settings (user_id, min_deposit_amount, min_withdrawal_amount, influence_mode_enabled) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            min_deposit_amount = VALUES(min_deposit_amount),
            min_withdrawal_amount = VALUES(min_withdrawal_amount),
            influence_mode_enabled = VALUES(influence_mode_enabled)
        ");
        $stmt->bind_param("iddi", $user_id, $min_deposit, $min_withdrawal, $influence_mode);
        $stmt->execute();
        
        $message = "Configurações do usuário atualizadas com sucesso!";
    }
    
    // Processar upload de banner
    if (isset($_POST['action']) && $_POST['action'] == 'upload_banner') {
    $upload_dir = '../uploads/banners/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    if (isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] == 0) {
        $file = $_FILES['banner_file'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']; 
        
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('banner_') . '.' . $file_extension;
        $file_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $image_info = @getimagesize($file_path); 
            $width = $image_info ? $image_info[0] : 0;
            $height = $image_info ? $image_info[1] : 0;
            
            $stmt = $conn->prepare("
                INSERT INTO banners (name, description, file_path, file_size, file_type, width, height, position, sort_order) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssisiiis", 
                $_POST['banner_name'], 
                $_POST['banner_description'], 
                $file_path, 
                $file['size'], 
                $file['type'], 
                $width, 
                $height, 
                $_POST['banner_position'], 
                $_POST['banner_order']
            );
            $stmt->execute();
            
            $message = "Banner enviado com sucesso!";
        } else {
            $message = "Erro ao fazer upload do arquivo.";
        }
    } else {
        $message = "Erro no upload do arquivo ou nenhum arquivo enviado.";
    }
}

    if (isset($_POST['action']) && $_POST['action'] == 'update_banner') {
        $banner_id = $_POST['banner_id'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $conn->prepare("
            UPDATE banners 
            SET name = ?, description = ?, position = ?, sort_order = ?, is_active = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("sssiis", 
            $_POST['banner_name'], 
            $_POST['banner_description'], 
            $_POST['banner_position'], 
            $_POST['banner_order'], 
            $is_active, 
            $banner_id
        );
        $stmt->execute();
        
        $message = "Banner atualizado com sucesso!";
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'delete_banner') {
        $banner_id = $_POST['banner_id'];
        
        $stmt = $conn->prepare("SELECT file_path FROM banners WHERE id = ?");
        $stmt->bind_param("i", $banner_id);
        $stmt->execute();
        $banner = $stmt->get_result()->fetch_assoc();
        
        if ($banner && file_exists($banner['file_path'])) {
            unlink($banner['file_path']);
        }
        
        $stmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
        $stmt->bind_param("i", $banner_id);
        $stmt->execute();
        
        $message = "Banner excluído com sucesso!";
    }
}

$client_id_result = $conn->query("SELECT valor FROM configuracoes WHERE chave='bspay_client_id'");
if ($client_id_result && $row = $result->fetch_assoc()) {
    $gatway_client_id = $row['valor'];
} else {
    $gatway_client_id = null; // ou valor padrão
}


$settings_result = $conn->query("SELECT * FROM global_settings ORDER BY setting_key");
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row;
}

$users_result = $conn->query("
    SELECT u.id, u.name as username, u.email,
           us.min_deposit_amount, us.min_withdrawal_amount, us.influence_mode_enabled
    FROM users u 
    LEFT JOIN user_settings us ON u.id = us.user_id 
    WHERE u.is_admin = 0 
    ORDER BY u.name");

// Buscar dados para relatório de afiliados - CORREÇÃO AQUI
$affiliates_report = $conn->query("
    SELECT 
        a.id,
        a.user_id,
        u.name as username,
        u.email,
        a.affiliate_code,
        a.is_active as status,
        a.created_at,
        COUNT(DISTINCT r.id) as total_referrals,
        COUNT(DISTINCT CASE WHEN c.status = 'approved' THEN c.id END) as approved_commissions,
        COALESCE(SUM(CASE WHEN c.status = 'approved' THEN c.amount ELSE 0 END), 0) as total_earnings,
        COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END), 0) as total_paid,
        (COALESCE(SUM(CASE WHEN c.status = 'approved' THEN c.amount ELSE 0 END), 0) - 
         COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount ELSE 0 END), 0)) as pending_balance
    FROM affiliates a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN referrals r ON a.id = r.referrer_id
    LEFT JOIN commissions c ON a.id = c.affiliate_id
    LEFT JOIN payouts p ON a.id = p.affiliate_id
    GROUP BY a.id, a.user_id, u.name, u.email, a.affiliate_code, a.is_active, a.created_at
    ORDER BY total_earnings DESC");

// Buscar banners
$banners_result = $conn->query("SELECT * FROM banners ORDER BY position, sort_order");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo Completo</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #7257b4;
            --secondary-color: #6876df;
            --success-color: #10b981;
            --warning-color: #fbbf24;
            --danger-color: #ef4444;
            --dark-color: #202c3e;
        }

        body {
            background: linear-gradient(135deg, var(--dark-color) 0%, #1e293b 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: white;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .main-container {
            padding: 1rem;
        }

        @media (min-width: 768px) {
            .main-container {
                padding: 2rem;
            }
        }

        .settings-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 768px) {
            .settings-card {
                padding: 2rem;
            }
        }

        .setting-group {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }

        @media (min-width: 768px) {
            .setting-group {
                padding: 1.5rem;
            }
        }

        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 0.5rem;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(114, 87, 180, 0.25);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-check-input {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
        }

        @media (min-width: 768px) {
            .btn-primary {
                padding: 0.75rem 2rem;
            }
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
            border-radius: 0.5rem;
        }

        .setting-description {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 0.25rem;
        }

        .danger-zone {
            border-left-color: var(--danger-color);
            background: rgba(239, 68, 68, 0.1);
        }

        .warning-zone {
            border-left-color: var(--warning-color);
            background: rgba(251, 191, 36, 0.1);
        }

        .user-settings-zone {
            border-left-color: var(--success-color);
            background: rgba(16, 185, 129, 0.1);
        }

        .nav-tabs .nav-link {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.8);
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        @media (min-width: 768px) {
            .nav-tabs .nav-link {
                margin-right: 0.5rem;
                margin-bottom: 0;
                border-radius: 0.5rem 0.5rem 0 0;
                font-size: 1rem;
                padding: 0.75rem 1.5rem;
            }
        }

        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .tab-content {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.75rem;
            padding: 1rem;
        }

        @media (min-width: 768px) {
            .tab-content {
                border-radius: 0 0.75rem 0.75rem 0.75rem;
                padding: 2rem;
            }
        }

        .table-dark {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
        }

        .table-dark th,
        .table-dark td {
            border-color: rgba(255, 255, 255, 0.2);
            font-size: 0.9rem;
        }

        @media (min-width: 768px) {
            .table-dark th,
            .table-dark td {
                font-size: 1rem;
            }
        }

        .modal-content {
            background: var(--dark-color);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .banner-preview {
            max-width: 200px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 0.5rem;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .stats-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        @media (min-width: 768px) {
            .stats-number {
                font-size: 2rem;
            }
        }

        .stats-label {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.7);
        }

        @media (min-width: 768px) {
            .stats-label {
                font-size: 0.9rem;
            }
        }

        /* Responsividade para DataTables */
        .dataTables_wrapper {
            color: white;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: white;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 0.25rem;
        }

        .page-link {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .page-link:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="index.php">
                <i class="bi bi-gear"></i> <span class="d-none d-md-inline">Admin - Painel Completo</span><span class="d-md-none">Admin</span>
            </a>
            <div class="navbar-nav me-auto">
                <span class="badge bg-primary me-2">Plataforma liberada por QICBUSINESS</span>
                <a href="https://t.me/+QNv-hPVLFEAxNjAx" target="_blank" class="btn btn-sm" style="background: #ff6b35; border-color: #ff6b35; color: white;">
                    <i class="fab fa-telegram me-1"></i>Acesse nosso grupo
                </a>
            </div>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white" href="index.php">
                    <i class="bi bi-house"></i> <span class="d-none d-md-inline">Dashboard</span>
                </a>
                <a class="nav-link text-white" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Sair</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid main-container">
        <!-- Mensagens -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs de Navegação -->
        <ul class="nav nav-tabs mb-4 flex-wrap" id="configTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="global-tab" data-bs-toggle="tab" data-bs-target="#global" type="button" role="tab">
                    <i class="bi bi-globe"></i> <span class="d-none d-sm-inline">Configurações Globais</span><span class="d-sm-none">Global</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                    <i class="bi bi-people"></i> <span class="d-none d-sm-inline">Config. Usuários</span><span class="d-sm-none">Usuários</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="affiliates-tab" data-bs-toggle="tab" data-bs-target="#affiliates" type="button" role="tab">
                    <i class="bi bi-graph-up"></i> <span class="d-none d-sm-inline">Relatório Afiliados</span><span class="d-sm-none">Afiliados</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="banners-tab" data-bs-toggle="tab" data-bs-target="#banners" type="button" role="tab">
                    <i class="bi bi-image"></i> <span class="d-none d-sm-inline">Gerenciar Banners</span><span class="d-sm-none">Banners</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="configTabsContent">
            <!-- Tab Configurações Globais -->
            <div class="tab-pane fade show active" id="global" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_settings">
                            
                            <!-- Configurações de Comissão -->
                            <!-- <div class="setting-group">
                                <h4 class="mb-3">
                                    <i class="bi bi-percent"></i> Configurações de Gatweay
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Client ID</label>
                                        <input type="text" class="form-control" name="client_id" value="<?php echo $gatway_client_id; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Client Secret</label>
                                        <input type="text" class="form-control" name="client_secret" value="<?php echo $gatway_client_secret; ?>">
                                    </div>
                                </div>
                            </div> -->

                            <div class="setting-group">
                                <h4 class="mb-3">
                                    <i class="bi bi-percent"></i> Configurações de Comissão
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Taxa Padrão RevShare (%)</label>
                                        <input type="number" class="form-control" name="settings[default_revshare_rate]" 
                                               value="<?php echo $settings['default_revshare_rate']['setting_value']; ?>" 
                                               step="0.1" min="0" max="100">
                                        <div class="setting-description">
                                            <?php echo $settings['default_revshare_rate']['description']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Delay para Comissões (horas)</label>
                                        <input type="number" class="form-control" name="settings[commission_delay_hours]" 
                                               value="<?php echo $settings['commission_delay_hours']['setting_value']; ?>" 
                                               min="0" max="168">
                                        <div class="setting-description">
                                            <?php echo $settings['commission_delay_hours']['description']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Configurações de Níveis -->
                            <div class="setting-group">
                                <h4 class="mb-3">
                                    <i class="bi bi-diagram-3"></i> Configurações de Níveis
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Máximo de Níveis</label>
                                        <select class="form-select" name="settings[max_affiliate_levels]">
                                            <option value="1" <?php echo $settings['max_affiliate_levels']['setting_value'] == '1' ? 'selected' : ''; ?>>1 Nível</option>
                                            <option value="2" <?php echo $settings['max_affiliate_levels']['setting_value'] == '2' ? 'selected' : ''; ?>>2 Níveis</option>
                                            <option value="3" <?php echo $settings['max_affiliate_levels']['setting_value'] == '3' ? 'selected' : ''; ?>>3 Níveis</option>
                                            <option value="4" <?php echo $settings['max_affiliate_levels']['setting_value'] == '4' ? 'selected' : ''; ?>>4 Níveis</option>
                                        </select>
                                        <div class="setting-description">
                                            <?php echo $settings['max_affiliate_levels']['description']; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Nível 2 (%)</label>
                                        <input type="number" class="form-control" name="settings[level_2_percentage]" 
                                               value="<?php echo $settings['level_2_percentage']['setting_value']; ?>" 
                                               min="0" max="100">
                                        <div class="setting-description">
                                            <?php echo $settings['level_2_percentage']['description']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Nível 3 (%)</label>
                                        <input type="number" class="form-control" name="settings[level_3_percentage]" 
                                               value="<?php echo $settings['level_3_percentage']['setting_value']; ?>" 
                                               min="0" max="100">
                                        <div class="setting-description">
                                            <?php echo $settings['level_3_percentage']['description']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Nível 4 (%)</label>
                                        <input type="number" class="form-control" name="settings[level_4_percentage]" 
                                               value="<?php echo $settings['level_4_percentage']['setting_value']; ?>" 
                                               min="0" max="100">
                                        <div class="setting-description">
                                            <?php echo $settings['level_4_percentage']['description']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Configurações de Depósito e Saque -->
                            <div class="setting-group warning-zone">
                                <h4 class="mb-3">
                                    <i class="bi bi-cash-stack"></i> Configurações de Depósito e Saque
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Valor Mínimo Depósito Global (R$)</label>
                                        <input type="number" class="form-control" name="settings[min_deposit_amount]" 
                                               value="<?php echo $settings['min_deposit_amount']['setting_value']; ?>" 
                                               step="0.01" min="1">
                                        <div class="setting-description">
                                            <?php echo $settings['min_deposit_amount']['description']; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Valor Mínimo Saque Global (R$)</label>
                                        <input type="number" class="form-control" name="settings[min_payout_amount]" 
                                               value="<?php echo $settings['min_payout_amount']['setting_value']; ?>" 
                                               step="0.01" min="1">
                                        <div class="setting-description">
                                            <?php echo $settings['min_payout_amount']['description']; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Valor Máximo Saque (R$)</label>
                                        <input type="number" class="form-control" name="settings[max_payout_amount]" 
                                               value="<?php echo $settings['max_payout_amount']['setting_value']; ?>" 
                                               step="0.01" min="1">
                                        <div class="setting-description">
                                            <?php echo $settings['max_payout_amount']['description']; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="settings[auto_approve_payouts]" 
                                           value="1" <?php echo $settings['auto_approve_payouts']['setting_value'] == '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        Aprovar saques automaticamente
                                    </label>
                                    <div class="setting-description">
                                        <?php echo $settings['auto_approve_payouts']['description']; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Configurações de Bônus -->
                            <div class="setting-group user-settings-zone">
                                <h4 class="mb-3">
                                    <i class="bi bi-gift"></i> Configurações de Bônus
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Valor do Bônus Inicial (R$)</label>
                                        <input type="number" class="form-control" name="settings[initial_bonus_amount]" 
                                               value="<?php echo $settings['initial_bonus_amount']['setting_value']; ?>" 
                                               step="0.01" min="0">
                                        <div class="setting-description">
                                            <?php echo $settings['initial_bonus_amount']['description']; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="settings[initial_bonus_enabled]" 
                                           value="1" <?php echo $settings['initial_bonus_enabled']['setting_value'] == '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        Bônus inicial ativo
                                    </label>
                                    <div class="setting-description">
                                        <?php echo $settings['initial_bonus_enabled']['description']; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Configurações do Sistema -->
                            <div class="setting-group danger-zone">
                                <h4 class="mb-3">
                                    <i class="bi bi-gear-wide-connected"></i> Configurações do Sistema
                                </h4>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="settings[affiliate_system_enabled]" 
                                           value="1" <?php echo $settings['affiliate_system_enabled']['setting_value'] == '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">
                                        Sistema de afiliados ativo
                                    </label>
                                    <div class="setting-description">
                                        <?php echo $settings['affiliate_system_enabled']['description']; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle"></i> Salvar Configurações Globais
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Informações de Ajuda -->
                        <div class="settings-card">
                            <h5 class="mb-3">
                                <i class="bi bi-info-circle"></i> Informações Importantes
                            </h5>
                            
                            <div class="alert alert-warning">
                                <strong>Atenção:</strong> Alterações nas configurações de comissão afetarão apenas novos afiliados ou novas comissões.
                            </div>
                            
                            <div class="alert alert-info">
                                <strong>Níveis de Afiliados:</strong> As porcentagens dos níveis 2, 3 e 4 são calculadas sobre a comissão do nível 1.
                            </div>
                            
                            <div class="alert alert-success">
                                <strong>Bônus Inicial:</strong> Quando ativo, novos usuários receberão automaticamente o valor configurado.
                            </div>
                            
                            <div class="alert alert-danger">
                                <strong>Zona de Perigo:</strong> Desativar o sistema de afiliados impedirá novos cadastros e comissões.
                            </div>
                        </div>

                        <!-- Estatísticas Rápidas -->
                        <div class="settings-card">
                            <h5 class="mb-3">
                                <i class="bi bi-graph-up"></i> Estatísticas Rápidas
                            </h5>
                            
                            <?php
                            $stats_query = "
                                SELECT 
                                    COUNT(DISTINCT a.id) as total_affiliates,
                                    COUNT(DISTINCT CASE WHEN a.is_active = 1 THEN a.id END) as active_affiliates,
                                    COALESCE(SUM(CASE WHEN c.status = 'approved' THEN c.amount ELSE 0 END), 0) as total_commissions,
                                    COALESCE(COUNT(CASE WHEN p.status = 'pending' THEN p.id END), 0) as pending_payouts
                                FROM affiliates a
                                LEFT JOIN commissions c ON a.id = c.affiliate_id
                                LEFT JOIN payouts p ON a.id = p.affiliate_id
                            ";
                            $stats_result = $conn->query($stats_query);
                            
                            // Adicionado tratamento de erro para a consulta
                            if ($stats_result === false) {
                                // Em caso de erro na consulta, inicializa $stats com valores padrão
                                $stats = [
                                    'total_affiliates' => 0,
                                    'active_affiliates' => 0,
                                    'total_commissions' => 0,
                                    'pending_payouts' => 0
                                ];
                                // Opcional: logar o erro para depuração
                                // error_log("Erro na consulta de estatísticas: " . $conn->error);
                            } else {
                                $stats = $stats_result->fetch_assoc();
                            }
                            ?>
                            
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="stats-card">
                                        <div class="stats-number text-primary"><?php echo number_format($stats['total_affiliates']); ?></div>
                                        <div class="stats-label">Total de Afiliados</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stats-card">
                                        <div class="stats-number text-success"><?php echo number_format($stats['active_affiliates']); ?></div>
                                        <div class="stats-label">Afiliados Ativos</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stats-card">
                                        <div class="stats-number text-warning">R$ <?php echo number_format($stats['total_commissions'], 2, ',', '.'); ?></div>
                                        <div class="stats-label">Total Comissões</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stats-card">
                                        <div class="stats-number text-info"><?php echo number_format($stats['pending_payouts']); ?></div>
                                        <div class="stats-label">Saques Pendentes</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Configurações por Usuário -->
            <div class="tab-pane fade" id="users" role="tabpanel">
                <div class="setting-group user-settings-zone">
                    <h4 class="mb-3">
                        <i class="bi bi-person-gear"></i> Configurações Individuais por Usuário
                    </h4>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Configurações Individuais:</strong> Estas configurações sobrescrevem as configurações globais para usuários específicos.
                        Deixe em branco para usar os valores globais.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-dark table-striped" id="usersTable">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Email</th>
                                    <th>Min. Depósito (R$)</th>
                                    <th>Min. Saque (R$)</th>
                                    <th>Modo Influência</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $users_result->data_seek(0); // Reset result pointer
                                while ($user_row = $users_result->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user_row['username']); ?></td>
                                    <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($user_row['email']); ?></td>
                                    <td>
                                        <?php if ($user_row['min_deposit_amount']): ?>
                                            R$ <?php echo number_format($user_row['min_deposit_amount'], 2, ',', '.'); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Global</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user_row['min_withdrawal_amount']): ?>
                                            R$ <?php echo number_format($user_row['min_withdrawal_amount'], 2, ',', '.'); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Global</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($user_row['influence_mode_enabled']): ?>
                                            <span class="badge bg-success">✅ Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">❌ Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="editUserSettings(<?php echo $user_row['id']; ?>, '<?php echo htmlspecialchars($user_row['username']); ?>', '<?php echo $user_row['min_deposit_amount']; ?>', '<?php echo $user_row['min_withdrawal_amount']; ?>', <?php echo $user_row['influence_mode_enabled'] ? 'true' : 'false'; ?>)">
                                            <i class="bi bi-pencil"></i> <span class="d-none d-md-inline">Editar</span>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab Relatório de Afiliados -->
            <div class="tab-pane fade" id="affiliates" role="tabpanel">
                <div class="setting-group">
                    <h4 class="mb-3">
                        <i class="bi bi-graph-up"></i> Relatório Completo de Afiliados
                    </h4>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Relatório Detalhado:</strong> Visualize o desempenho completo de todos os afiliados, incluindo ganhos, indicações e status de pagamentos.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-dark table-striped" id="affiliatesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuário</th>
                                    <th>Email</th>
                                    <th>Código</th>
                                    <th>Status</th>
                                    <th>Indicações</th>
                                    <th>Comissões</th>
                                    <th>Total Ganho</th>
                                    <th>Total Pago</th>
                                    <th>Saldo Pendente</th>
                                    <th>Cadastro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($affiliate = $affiliates_report->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $affiliate['id']; ?></td>
                                    <td><?php echo htmlspecialchars($affiliate['username']); ?></td>
                                    <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($affiliate['email']); ?></td>
                                    <td><code><?php echo htmlspecialchars($affiliate['affiliate_code']); ?></code></td>
                                    <td>
                                        <?php if ($affiliate['status'] == 1): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo number_format($affiliate['total_referrals']); ?></td>
                                    <td class="text-center"><?php echo number_format($affiliate['approved_commissions']); ?></td>
                                    <td class="text-success">R$ <?php echo number_format($affiliate['total_earnings'], 2, ',', '.'); ?></td>
                                    <td class="text-info">R$ <?php echo number_format($affiliate['total_paid'], 2, ',', '.'); ?></td>
                                    <td class="text-warning">R$ <?php echo number_format($affiliate['pending_balance'], 2, ',', '.'); ?></td>
                                    <td class="d-none d-xl-table-cell"><?php echo date('d/m/Y', strtotime($affiliate['created_at'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab Gerenciar Banners -->
            <div class="tab-pane fade" id="banners" role="tabpanel">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="setting-group">
                            <h4 class="mb-3">
                                <i class="bi bi-image"></i> Banners Cadastrados
                            </h4>
                            
                            <div class="table-responsive">
                                <table class="table table-dark table-striped" id="bannersTable">
                                    <thead>
                                        <tr>
                                            <th>Preview</th>
                                            <th>Nome</th>
                                            <th>Posição</th>
                                            <th>Dimensões</th>
                                            <th>Status</th>
                                            <th>Ordem</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($banner = $banners_result->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($banner['file_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($banner['name']); ?>" 
                                                     class="banner-preview">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($banner['name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($banner['description']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo ucfirst($banner['position']); ?></span>
                                            </td>
                                            <td class="d-none d-md-table-cell">
                                                <?php echo $banner['width']; ?>x<?php echo $banner['height']; ?>px
                                                <br><small class="text-muted"><?php echo number_format($banner['file_size']/1024, 1); ?> KB</small>
                                            </td>
                                            <td>
                                                <?php if ($banner['is_active']): ?>
                                                    <span class="badge bg-success">Ativo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?php echo $banner['sort_order']; ?></td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm">
                                                    <button type="button" class="btn btn-primary btn-sm mb-1" 
                                                            onclick="editBanner(<?php echo htmlspecialchars(json_encode($banner)); ?>)">
                                                        <i class="bi bi-pencil"></i> <span class="d-none d-lg-inline">Editar</span>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="deleteBanner(<?php echo $banner['id']; ?>, '<?php echo htmlspecialchars($banner['name']); ?>')">
                                                        <i class="bi bi-trash"></i> <span class="d-none d-lg-inline">Excluir</span>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="settings-card">
                            <h5 class="mb-3">
                                <i class="bi bi-plus-circle"></i> Adicionar Novo Banner
                            </h5>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_banner">
                                
                                <div class="mb-3">
                                    <label class="form-label">Nome do Banner</label>
                                    <input type="text" class="form-control" name="banner_name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Descrição</label>
                                    <textarea class="form-control" name="banner_description" rows="2"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Posição</label>
                                    <select class="form-select" name="banner_position" required>
                                        <option value="header">Header</option>
                                        <option value="sidebar">Sidebar</option>
                                        <option value="footer">Footer</option>
                                        <option value="content">Conteúdo</option>
                                        <option value="popup">Popup</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Ordem de Exibição</label>
                                    <input type="number" class="form-control" name="banner_order" value="0" min="0">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Arquivo da Imagem</label>
                                    <input type="file" class="form-control" name="banner_file" 
                                           accept="image/jpeg,image/png,image/gif,image/webp" required>
                                    <div class="form-text">
                                        Formatos aceitos: JPG, PNG, GIF, WebP<br>
                                        Tamanho máximo: 5MB
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="bi bi-upload"></i> Enviar Banner
                                </button>
                            </form>
                        </div>
                        
                        <div class="settings-card">
                            <h5 class="mb-3">
                                <i class="bi bi-info-circle"></i> Dicas para Banners
                            </h5>
                            
                            <div class="alert alert-info">
                                <strong>Dimensões Recomendadas:</strong>
                                <ul class="mb-0 mt-2">
                                    <li><strong>Header:</strong> 1200x300px</li>
                                    <li><strong>Sidebar:</strong> 300x600px</li>
                                    <li><strong>Footer:</strong> 1200x200px</li>
                                    <li><strong>Conteúdo:</strong> 800x400px</li>
                                    <li><strong>Popup:</strong> 600x400px</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning">
                                <strong>Otimização:</strong> Use imagens otimizadas para web para melhor performance.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Configurações do Usuário -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-gear"></i> Editar Configurações do Usuário
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_user_settings">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Usuário</label>
                            <input type="text" class="form-control" id="edit_username" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Valor Mínimo de Depósito (R$)</label>
                            <input type="number" class="form-control" name="min_deposit_amount" 
                                   id="edit_min_deposit" step="0.01" min="0" 
                                   placeholder="Deixe em branco para usar valor global">
                            <div class="form-text">
                                Valor global atual: R$ <?php echo number_format($settings['min_deposit_amount']['setting_value'], 2, ',', '.'); ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Valor Mínimo de Saque (R$)</label>
                            <input type="number" class="form-control" name="min_withdrawal_amount" 
                                   id="edit_min_withdrawal" step="0.01" min="0" 
                                   placeholder="Deixe em branco para usar valor global">
                            <div class="form-text">
                                Valor global atual: R$ <?php echo number_format($settings['min_payout_amount']['setting_value'], 2, ',', '.'); ?>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="influence_mode_enabled" 
                                   id="edit_influence_mode" value="1">
                            <label class="form-check-label" for="edit_influence_mode">
                                <strong>Ativar Modo Influência</strong>
                            </label>
                            <div class="form-text">
                                O modo influência altera as chances de ganhar nos jogos (ex: raspadinha)
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Banner -->
    <div class="modal fade" id="editBannerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-image"></i> Editar Banner
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_banner">
                    <input type="hidden" name="banner_id" id="edit_banner_id">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome do Banner</label>
                            <input type="text" class="form-control" name="banner_name" id="edit_banner_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" name="banner_description" id="edit_banner_description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Posição</label>
                            <select class="form-select" name="banner_position" id="edit_banner_position" required>
                                <option value="header">Header</option>
                                <option value="sidebar">Sidebar</option>
                                <option value="footer">Footer</option>
                                <option value="content">Conteúdo</option>
                                <option value="popup">Popup</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ordem de Exibição</label>
                            <input type="number" class="form-control" name="banner_order" id="edit_banner_order" min="0">
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_banner_active" value="1">
                            <label class="form-check-label" for="edit_banner_active">
                                Banner ativo
                            </label>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Confirmar Exclusão de Banner -->
    <div class="modal fade" id="deleteBannerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle"></i> Confirmar Exclusão
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o banner <strong id="delete_banner_name"></strong>?</p>
                    <p class="text-warning">Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_banner">
                        <input type="hidden" name="banner_id" id="delete_banner_id">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Excluir Banner
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Inicializar DataTables
        $(document).ready(function() {
            $('#usersTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
                },
                responsive: true,
                pageLength: 10,
                order: [[0, 'asc']]
            });
            
            $('#affiliatesTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
                },
                responsive: true,
                pageLength: 10,
                order: [[7, 'desc']], // Ordenar por Total Ganho
                columnDefs: [
                    { targets: [5, 6], className: 'text-center' },
                    { targets: [7, 8, 9], className: 'text-end' }
                ]
            });
            
            $('#bannersTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
                },
                responsive: true,
                pageLength: 10,
                order: [[2, 'asc'], [5, 'asc']], // Ordenar por posição e ordem
                columnDefs: [
                    { targets: [0], orderable: false },
                    { targets: [5], className: 'text-center' },
                    { targets: [6], orderable: false }
                ]
            });
        });
        
        function editUserSettings(userId, username, minDeposit, minWithdrawal, influenceMode) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_min_deposit').value = minDeposit || '';
            document.getElementById('edit_min_withdrawal').value = minWithdrawal || '';
            document.getElementById('edit_influence_mode').checked = influenceMode;
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        function editBanner(banner) {
            document.getElementById('edit_banner_id').value = banner.id;
            document.getElementById('edit_banner_name').value = banner.name;
            document.getElementById('edit_banner_description').value = banner.description || '';
            document.getElementById('edit_banner_position').value = banner.position;
            document.getElementById('edit_banner_order').value = banner.sort_order;
            document.getElementById('edit_banner_active').checked = banner.is_active == 1;
            
            new bootstrap.Modal(document.getElementById('editBannerModal')).show();
        }
        
        function deleteBanner(bannerId, bannerName) {
            document.getElementById('delete_banner_id').value = bannerId;
            document.getElementById('delete_banner_name').textContent = bannerName;
            
            new bootstrap.Modal(document.getElementById('deleteBannerModal')).show();
        }
    </script>
</body>
</html>
