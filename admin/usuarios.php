<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/db.php';

// Verificar se é uma requisição AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Processar alterações de saldo ou permissão
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        if (isset($_POST['editar_saldo'])) {
            $id = intval($_POST['id']);
            $novoSaldo = floatval($_POST['saldo']);
            $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $stmt->bind_param("di", $novoSaldo, $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Saldo atualizado com sucesso!';
                $response['new_balance'] = number_format($novoSaldo, 2, ',', '.');
            } else {
                $response['message'] = 'Erro ao atualizar saldo.';
            }
        }

        if (isset($_POST['promover'])) {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Usuário promovido a administrador com sucesso!';
                $response['action'] = 'promote';
            } else {
                $response['message'] = 'Erro ao promover usuário.';
            }
        }

        if (isset($_POST['rebaixar'])) {
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("UPDATE users SET is_admin = 0 WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Usuário rebaixado com sucesso!';
                $response['action'] = 'demote';
            } else {
                $response['message'] = 'Erro ao rebaixar usuário.';
            }
        }

        if (isset($_POST['resetar_saldos'])) {
            if ($conn->query("UPDATE users SET balance = 0")) {
                $response['success'] = true;
                $response['message'] = 'Todos os saldos foram resetados com sucesso!';
                $response['action'] = 'reset_all';
            } else {
                $response['message'] = 'Erro ao resetar saldos.';
            }
        }

        if (isset($_POST['criar_usuario'])) {
            $nome = $_POST['nome'];
            $email = $_POST['email'];
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            
            // Verificar se o email já existe
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                $response['message'] = 'Este email já está cadastrado.';
            } else {
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, balance, is_admin) VALUES (?, ?, ?, 0, 0)");
                $stmt->bind_param("sss", $nome, $email, $senha);
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Usuário criado com sucesso!';
                    $response['action'] = 'create_user';
                } else {
                    $response['message'] = 'Erro ao criar usuário.';
                }
            }
        }
    } catch (Exception $e) {
        $response['message'] = 'Erro interno: ' . $e->getMessage();
    }

    // Se for uma requisição AJAX, retornar JSON
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // Se não for AJAX, redirecionar (comportamento original)
    if ($response['success']) {
        header("Location: painel.php?success=" . urlencode($response['message']));
    } else {
        header("Location: painel.php?error=" . urlencode($response['message']));
    }
    exit();
}

// Buscar todos os usuários
$result = $conn->query("SELECT id, name, email, balance, is_admin FROM users ORDER BY id ASC");

// Calcular estatísticas
$stats = $conn->query("SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN is_admin = 1 THEN 1 END) as total_admins,
    SUM(balance) as total_balance,
    AVG(balance) as avg_balance
    FROM users")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Painel de Administração</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 para mensagens bonitas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --border-color: #e5e7eb;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            margin: 1rem;
            overflow: hidden;
            width: calc(100% - 2rem);
            max-width: 1400px;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-text {
            flex: 1;
            min-width: 0;
        }

        .header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }

        .header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        /* Estilo específico para o botão voltar */
        .btn-voltar {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            font-size: 0.85rem;
            min-height: 44px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-voltar:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-voltar::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .btn-voltar:hover::before {
            left: 100%;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            padding: 1rem;
            background: var(--light-color);
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            min-height: auto;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 0.75rem;
            flex-shrink: 0;
        }

        .stat-icon.users { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; }
        .stat-icon.admins { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; }
        .stat-icon.balance { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .stat-icon.average { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
            line-height: 1.2;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.8rem;
            margin: 0;
        }

        .content-section {
            padding: 1rem;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .create-user-form {
            background: var(--light-color);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 0.75rem;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 0.9rem;
            min-height: 44px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .btn {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            min-height: 44px;
            white-space: nowrap;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover:not(:disabled) {
            background: #dc2626;
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover:not(:disabled) {
            background: #059669;
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-warning:hover:not(:disabled) {
            background: #d97706;
            color: white;
        }

        .btn-info {
            background: #06b6d4;
            color: white;
        }

        .btn-info:hover:not(:disabled) {
            background: #0891b2;
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
            min-height: 36px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            margin: 0;
            width: 100%;
            min-width: 600px;
        }

        .table thead th {
            background: var(--light-color);
            border: none;
            padding: 0.75rem 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.8rem;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 0.75rem 0.5rem;
            border-color: var(--border-color);
            vertical-align: middle;
            font-size: 0.8rem;
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 0.5rem;
            flex-shrink: 0;
            font-size: 0.75rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            min-width: 0;
        }

        .user-details {
            min-width: 0;
            flex: 1;
        }

        .user-details h6 {
            margin: 0;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.8rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-details small {
            color: #6b7280;
            font-size: 0.7rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }

        .admin-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .user-badge {
            background: #e5e7eb;
            color: #6b7280;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.65rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .balance-input {
            max-width: 80px;
            font-size: 0.75rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.25rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .back-link {
            background: #6b7280;
            color: white;
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            min-height: 44px;
        }

        .back-link:hover {
            background: #4b5563;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .reset-button {
            margin-bottom: 1.5rem;
        }

        .input-group {
            display: flex;
            width: 100%;
        }

        .input-group-text {
            background: var(--light-color);
            border: 1px solid var(--border-color);
            border-right: none;
            border-radius: 8px 0 0 8px;
            padding: 0.5rem;
            color: var(--dark-color);
            font-size: 0.75rem;
            min-height: 36px;
            display: flex;
            align-items: center;
        }

        .input-group .form-control {
            border-radius: 0;
            border-left: none;
            border-right: none;
            min-height: 36px;
        }

        .input-group .btn {
            border-radius: 0 8px 8px 0;
            min-height: 36px;
        }

        .loading-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Formulário responsivo */
        .form-row {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        /* Media Queries para responsividade */
        @media (min-width: 576px) {
            html {
                font-size: 16px;
            }

            .main-container {
                margin: 1.5rem auto;
                max-width: 1400px;
            }

            .header {
                padding: 2rem;
            }

            .header h1 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                padding: 1.5rem;
                gap: 1.5rem;
            }

            .content-section {
                padding: 1.5rem;
            }

            .form-row {
                flex-direction: row;
                align-items: end;
            }

            .form-group {
                flex: 1;
            }

            .form-group:last-child {
                flex: 0 0 auto;
            }
        }

        @media (min-width: 768px) {
            .header h1 {
                font-size: 2.2rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .table thead th,
            .table tbody td {
                padding: 1rem;
                font-size: 0.9rem;
            }

            .user-avatar {
                width: 40px;
                height: 40px;
                font-size: 0.9rem;
            }

            .user-details h6 {
                font-size: 0.9rem;
            }

            .user-details small {
                font-size: 0.8rem;
            }

            .balance-input {
                max-width: 100px;
            }
        }

        @media (min-width: 992px) {
            .main-container {
                margin: 2rem auto;
            }

            .header {
                padding: 2rem;
            }

            .header h1 {
                font-size: 2.5rem;
            }

            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
                padding: 2rem;
            }

            .content-section {
                padding: 2rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .balance-input {
                max-width: 120px;
            }
        }

        @media (min-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Melhorias específicas para touch devices */
        @media (hover: none) and (pointer: coarse) {
            .btn {
                min-height: 48px;
                padding: 0.875rem 1rem;
            }

            .form-control {
                min-height: 48px;
                padding: 0.875rem;
            }

            .stat-card:hover {
                transform: none;
            }

            .btn:hover {
                transform: none;
            }

            .back-link:hover {
                transform: none;
            }

            .btn-voltar:hover {
                transform: none;
            }
        }

        /* Correções específicas para mobile muito pequeno */
        @media (max-width: 360px) {
            .main-container {
                margin: 0.5rem;
                width: calc(100% - 1rem);
                border-radius: 12px;
            }

            .header {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .header p {
                font-size: 0.8rem;
            }

            .header-content {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .btn-voltar {
                align-self: flex-end;
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }

            .stats-grid {
                padding: 0.75rem;
                gap: 0.75rem;
            }

            .content-section {
                padding: 0.75rem;
            }

            .create-user-form {
                padding: 0.75rem;
            }

            .table {
                min-width: 500px;
            }

            .section-title {
                font-size: 1rem;
            }
        }

        /* Correções para landscape em mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .header {
                padding: 1rem;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
                padding: 1rem;
                gap: 1rem;
            }

            .stat-card {
                padding: 0.75rem;
            }

            .stat-value {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Header com botão voltar -->
        <div class="header">
            <div class="header-content">
                <div class="header-text">
                    <h1><i class="fas fa-cogs me-2"></i>Painel de Administração</h1>
                    <p>Gerencie usuários, saldos e permissões do sistema</p>
                    <div class="mt-2">
                        <span class="badge bg-primary me-2">Plataforma liberada por QICBUSINESS</span>
                        <a href="https://t.me/+QNv-hPVLFEAxNjAx" target="_blank" class="btn btn-sm" style="background: #ff6b35; border-color: #ff6b35; color: white;">
                            <i class="fab fa-telegram me-1"></i>Acesse nosso grupo
                        </a>
                    </div>
                </div>
                <div>
                    <a href="javascript:history.back()" class="btn-voltar" title="Voltar à página anterior">
                        <i class="fas fa-arrow-left"></i>
                        <span class="d-none d-md-inline">Voltar</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="stat-value" id="total-users"><?= number_format($stats['total_users']) ?></h3>
                <p class="stat-label">Total de Usuários</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon admins">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3 class="stat-value" id="total-admins"><?= number_format($stats['total_admins']) ?></h3>
                <p class="stat-label">Administradores</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon balance">
                    <i class="fas fa-wallet"></i>
                </div>
                <h3 class="stat-value" id="total-balance">R$ <?= number_format($stats['total_balance'], 2, ',', '.') ?></h3>
                <p class="stat-label">Saldo Total</p>
            </div>
            <div class="stat-card">
                <div class="stat-icon average">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="stat-value" id="avg-balance">R$ <?= number_format($stats['avg_balance'], 2, ',', '.') ?></h3>
                <p class="stat-label">Saldo Médio</p>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="content-section">
            <!-- Botão de Reset -->
            <div class="reset-button">
                <button id="reset-all-btn" class="btn btn-danger">
                    <i class="fas fa-trash-alt me-2"></i>Resetar Todos os Saldos
                </button>
            </div>

            <!-- Criar Novo Usuário -->
            <h4 class="section-title">
                <i class="fas fa-user-plus"></i>
                Criar Novo Usuário
            </h4>
            <div class="create-user-form">
                <form id="create-user-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nome</label>
                            <input name="nome" class="form-control" placeholder="Digite o nome" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input name="email" class="form-control" placeholder="Digite o email" type="email" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Senha</label>
                            <input name="senha" class="form-control" placeholder="Digite a senha" type="password" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Criar Usuário
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Lista de Usuários -->
            <h4 class="section-title">
                <i class="fas fa-list"></i>
                Lista de Usuários
            </h4>
            <div class="table-container">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Saldo</th>
                                <th>Tipo</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="users-table-body">
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr data-user-id="<?= $row['id'] ?>">
                                <td><strong>#<?= $row['id'] ?></strong></td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                        </div>
                                        <div class="user-details">
                                            <h6><?= htmlspecialchars($row['name']) ?></h6>
                                            <small><?= htmlspecialchars($row['email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <form class="balance-form d-flex align-items-center" data-user-id="<?= $row['id'] ?>">
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" step="0.01" min="0" name="saldo" value="<?= $row['balance'] ?>" class="form-control balance-input">
                                            <button class="btn btn-success btn-sm" type="submit">
                                                <i class="fas fa-save"></i>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                                <td class="user-type-cell">
                                    <?php if ($row['is_admin']): ?>
                                        <span class="admin-badge">
                                            <i class="fas fa-crown me-1"></i>Admin
                                        </span>
                                    <?php else: ?>
                                        <span class="user-badge">
                                            <i class="fas fa-user me-1"></i>Usuário
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($row['is_admin']): ?>
                                            <button class="btn btn-warning btn-sm demote-btn" data-user-id="<?= $row['id'] ?>" title="Rebaixar para usuário">
                                                <i class="fas fa-arrow-down"></i>
                                                <span class="d-none d-md-inline ms-1">Rebaixar</span>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-info btn-sm promote-btn" data-user-id="<?= $row['id'] ?>" title="Promover para admin">
                                                <i class="fas fa-arrow-up"></i>
                                                <span class="d-none d-md-inline ms-1">Promover</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Link de Voltar -->
            <div class="mt-3">
                <a href="../admin" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Menu
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuração do SweetAlert2
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Função para fazer requisições AJAX
        async function makeAjaxRequest(url, formData) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error('Erro na requisição');
                }
                
                return await response.json();
            } catch (error) {
                console.error('Erro:', error);
                throw error;
            }
        }

        // Função para mostrar mensagem de sucesso ou erro
        function showMessage(success, message) {
            if (success) {
                Toast.fire({
                    icon: 'success',
                    title: message
                });
            } else {
                Toast.fire({
                    icon: 'error',
                    title: message
                });
            }
        }

        // Função para atualizar estatísticas
        async function updateStats() {
            try {
                const response = await fetch(window.location.href);
                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                document.getElementById('total-users').textContent = doc.getElementById('total-users').textContent;
                document.getElementById('total-admins').textContent = doc.getElementById('total-admins').textContent;
                document.getElementById('total-balance').textContent = doc.getElementById('total-balance').textContent;
                document.getElementById('avg-balance').textContent = doc.getElementById('avg-balance').textContent;
            } catch (error) {
                console.error('Erro ao atualizar estatísticas:', error);
            }
        }

        // Função para atualizar tipo de usuário na tabela
        function updateUserType(userId, isAdmin) {
            const row = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (!row) return;

            const typeCell = row.querySelector('.user-type-cell');
            const actionCell = row.querySelector('.action-buttons');

            if (isAdmin) {
                typeCell.innerHTML = '<span class="admin-badge"><i class="fas fa-crown me-1"></i>Admin</span>';
                actionCell.innerHTML = `<button class="btn btn-warning btn-sm demote-btn" data-user-id="${userId}" title="Rebaixar para usuário"><i class="fas fa-arrow-down"></i><span class="d-none d-md-inline ms-1">Rebaixar</span></button>`;
            } else {
                typeCell.innerHTML = '<span class="user-badge"><i class="fas fa-user me-1"></i>Usuário</span>';
                actionCell.innerHTML = `<button class="btn btn-info btn-sm promote-btn" data-user-id="${userId}" title="Promover para admin"><i class="fas fa-arrow-up"></i><span class="d-none d-md-inline ms-1">Promover</span></button>`;
            }

            // Reativar event listeners
            attachActionButtonListeners();
        }

        // Função para anexar event listeners aos botões de ação
        function attachActionButtonListeners() {
            // Promover usuários
            document.querySelectorAll('.promote-btn').forEach(btn => {
                btn.replaceWith(btn.cloneNode(true)); // Remove listeners antigos
            });
            
            document.querySelectorAll('.promote-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const userId = this.dataset.userId;
                    const originalContent = this.innerHTML;
                    
                    try {
                        this.disabled = true;
                        this.innerHTML = '<span class="loading-spinner"></span>';
                        
                        const formData = new FormData();
                        formData.append('promover', '1');
                        formData.append('id', userId);
                        
                        const result = await makeAjaxRequest(window.location.href, formData);
                        showMessage(result.success, result.message);
                        
                        if (result.success) {
                            updateUserType(userId, true);
                            updateStats();
                        }
                    } catch (error) {
                        showMessage(false, 'Erro ao promover usuário');
                    } finally {
                        this.disabled = false;
                        this.innerHTML = originalContent;
                    }
                });
            });

            // Rebaixar usuários
            document.querySelectorAll('.demote-btn').forEach(btn => {
                btn.replaceWith(btn.cloneNode(true)); // Remove listeners antigos
            });
            
            document.querySelectorAll('.demote-btn').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const userId = this.dataset.userId;
                    const originalContent = this.innerHTML;
                    
                    try {
                        this.disabled = true;
                        this.innerHTML = '<span class="loading-spinner"></span>';
                        
                        const formData = new FormData();
                        formData.append('rebaixar', '1');
                        formData.append('id', userId);
                        
                        const result = await makeAjaxRequest(window.location.href, formData);
                        showMessage(result.success, result.message);
                        
                        if (result.success) {
                            updateUserType(userId, false);
                            updateStats();
                        }
                    } catch (error) {
                        showMessage(false, 'Erro ao rebaixar usuário');
                    } finally {
                        this.disabled = false;
                        this.innerHTML = originalContent;
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Atalho de teclado Alt + Backspace para voltar
            document.addEventListener('keydown', function(e) {
                if (e.altKey && e.key === 'Backspace') {
                    e.preventDefault();
                    history.back();
                }
            });

            // Animação de entrada para os cards de estatísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Formulário de criação de usuário
            document.getElementById('create-user-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalContent = submitBtn.innerHTML;
                
                try {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="loading-spinner"></span> Criando...';
                    
                    const formData = new FormData(this);
                    formData.append('criar_usuario', '1');
                    
                    const result = await makeAjaxRequest(window.location.href, formData);
                    showMessage(result.success, result.message);
                    
                    if (result.success) {
                        this.reset();
                        // Recarregar a página para mostrar o novo usuário
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } catch (error) {
                    showMessage(false, 'Erro ao criar usuário');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalContent;
                }
            });

            // Formulários de edição de saldo
            document.querySelectorAll('.balance-form').forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const userId = this.dataset.userId;
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalContent = submitBtn.innerHTML;
                    
                    try {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="loading-spinner"></span>';
                        
                        const formData = new FormData(this);
                        formData.append('editar_saldo', '1');
                        formData.append('id', userId);
                        
                        const result = await makeAjaxRequest(window.location.href, formData);
                        showMessage(result.success, result.message);
                        
                        if (result.success) {
                            updateStats();
                        }
                    } catch (error) {
                        showMessage(false, 'Erro ao atualizar saldo');
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalContent;
                    }
                });
            });

            // Botão de reset de saldos
            document.getElementById('reset-all-btn').addEventListener('click', async function() {
                const result = await Swal.fire({
                    title: 'Tem certeza?',
                    text: 'Esta ação irá zerar todos os saldos dos usuários!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sim, resetar!',
                    cancelButtonText: 'Cancelar'
                });

                if (result.isConfirmed) {
                    const originalContent = this.innerHTML;
                    
                    try {
                        this.disabled = true;
                        this.innerHTML = '<span class="loading-spinner"></span> Resetando...';
                        
                        const formData = new FormData();
                        formData.append('resetar_saldos', '1');
                        
                        const response = await makeAjaxRequest(window.location.href, formData);
                        showMessage(response.success, response.message);
                        
                        if (response.success) {
                            // Atualizar todos os campos de saldo para 0
                            document.querySelectorAll('input[name="saldo"]').forEach(input => {
                                input.value = '0.00';
                            });
                            updateStats();
                        }
                    } catch (error) {
                        showMessage(false, 'Erro ao resetar saldos');
                    } finally {
                        this.disabled = false;
                        this.innerHTML = originalContent;
                    }
                }
            });

            // Anexar event listeners aos botões de ação
            attachActionButtonListeners();

            // Verificar se há mensagens de URL (para compatibilidade com redirecionamentos)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                showMessage(true, urlParams.get('success'));
                // Limpar a URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            if (urlParams.has('error')) {
                showMessage(false, urlParams.get('error'));
                // Limpar a URL
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        // Função para voltar com confirmação se houver alterações não salvas
        function voltarComConfirmacao() {
            // Verificar se há alterações não salvas nos formulários
            const forms = document.querySelectorAll('form');
            let hasChanges = false;

            forms.forEach(form => {
                const formData = new FormData(form);
                for (let [key, value] of formData.entries()) {
                    if (value.trim() !== '') {
                        hasChanges = true;
                        break;
                    }
                }
            });

            if (hasChanges) {
                Swal.fire({
                    title: 'Alterações não salvas',
                    text: 'Você tem alterações não salvas. Deseja realmente sair?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sim, sair',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        history.back();
                    }
                });
            } else {
                history.back();
            }
        }
    </script>
</body>
</html>

