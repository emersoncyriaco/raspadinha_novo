<?php
session_start();
require_once '../includes/db.php';

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

// Busca estatísticas do banco
$stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$total_usuarios = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT SUM(valor) as total FROM depositos WHERE status = 'aprovado'");
$result = $stmt->fetch_assoc();
$total_depositos = $result['total'] ?? 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM raspadinha_jogadas WHERE DATE(criado_em) = CURDATE()");
$raspadinhas_hoje = $stmt->fetch_assoc()['total'];

$stmt = $conn->query("SELECT valor FROM configuracoes WHERE chave = 'rtp'");
$result = $stmt->fetch_assoc();
$rtp_atual = $result['valor'] ?? 85;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Dashboard</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --sidebar-width: 280px;
            --sidebar-width-mobile: 260px;
            --header-height: 70px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            z-index: 1001;
            padding: 0 1rem;
            align-items: center;
            justify-content: space-between;
        }

        .mobile-menu-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--dark-color);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .mobile-menu-btn:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        .mobile-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-color);
            text-decoration: none;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1e293b 0%, #334155 100%);
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-brand:hover {
            color: #60a5fa;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 1rem;
        }

        .nav-link {
            color: #cbd5e1;
            padding: 0.875rem 1.25rem;
            border-radius: 0.75rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(4px);
        }

        .nav-link.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .nav-link i {
            font-size: 1.125rem;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .top-bar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .welcome-text {
            color: var(--dark-color);
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .welcome-subtitle {
            color: var(--secondary-color);
            margin: 0;
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--dark-color);
            margin: 0;
            line-height: 1.2;
        }

        .stat-label {
            color: var(--secondary-color);
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            color: var(--primary-color);
        }

        .action-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            color: white;
            flex-shrink: 0;
        }

        .action-text {
            flex: 1;
            min-width: 0;
        }

        .action-text .fw-semibold {
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .action-text .text-muted {
            font-size: 0.8rem;
            line-height: 1.3;
        }

        .logout-btn {
            color: var(--danger-color) !important;
            border-color: rgba(220, 38, 38, 0.2) !important;
        }

        .logout-btn:hover {
            background: rgba(220, 38, 38, 0.1) !important;
            color: var(--danger-color) !important;
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
            
            .quick-actions {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 992px) {
            :root {
                --sidebar-width: 260px;
            }
            
            .top-bar {
                padding: 1.25rem 1.5rem;
            }
            
            .welcome-text {
                font-size: 1.5rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .chart-container {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .mobile-header {
                display: flex;
            }
            
            .sidebar {
                width: var(--sidebar-width-mobile);
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .sidebar-overlay.show {
                display: block;
                opacity: 1;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: calc(var(--header-height) + 1rem);
            }
            
            .top-bar {
                padding: 1rem 1.25rem;
                margin-bottom: 1.5rem;
            }
            
            .welcome-text {
                font-size: 1.25rem;
            }
            
            .welcome-subtitle {
                font-size: 0.9rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
            
            .stat-value {
                font-size: 1.75rem;
            }
            
            .chart-container {
                padding: 1.25rem;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .action-btn {
                padding: 1.25rem;
            }
            
            .action-text .fw-semibold {
                font-size: 0.9rem;
            }
            
            .action-text .text-muted {
                font-size: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 0.75rem;
                padding-top: calc(var(--header-height) + 0.75rem);
            }
            
            .top-bar {
                padding: 1rem;
                border-radius: 0.75rem;
                margin-bottom: 1rem;
            }
            
            .top-bar .d-flex {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }
            
            .welcome-text {
                font-size: 1.125rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .stat-label {
                font-size: 0.8rem;
            }
            
            .chart-container {
                padding: 1rem;
            }
            
            .chart-title {
                font-size: 1.125rem;
                margin-bottom: 1rem;
            }
            
            .action-btn {
                padding: 1rem;
                gap: 0.75rem;
            }
            
            .action-icon {
                width: 35px;
                height: 35px;
                font-size: 1rem;
            }
        }

        @media (max-width: 400px) {
            .stats-grid {
                gap: 0.75rem;
            }
            
            .stat-card {
                padding: 0.875rem;
            }
            
            .stat-value {
                font-size: 1.375rem;
            }
            
            .action-btn {
                padding: 0.875rem;
            }
        }

        /* Landscape orientation adjustments for tablets */
        @media (max-width: 1024px) and (orientation: landscape) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
            
            .quick-actions {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Print styles */
        @media print {
            .sidebar,
            .mobile-header {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 1rem !important;
            }
            
            .stat-card,
            .chart-container {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <header class="mobile-header">
        <button class="mobile-menu-btn" id="mobileMenuBtn">
            <i class="bi bi-list"></i>
        </button>
        <a href="#" class="mobile-brand">
            <i class="bi bi-speedometer2 me-2"></i>
            Painel Admin
        </a>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success">Online</span>
        </div>
    </header>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <i class="bi bi-speedometer2"></i>
                Painel Admin
            </a>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-item">
                <a href="#" class="nav-link active">
                    <i class="bi bi-house-door"></i>
                    Dashboard
                </a>
            </div>
            <div class="nav-item">
                <a href="usuarios.php" class="nav-link">
                    <i class="bi bi-people"></i>
                    Gerenciar Usuários
                </a>
            </div>
            <div class="nav-item">
                <a href="payout_management.php" class="nav-link">
                    <i class="bi bi-cash-stack"></i>
                    Gestão de Pagamentos
                </a>
            </div>
            <div class="nav-item">
                <a href="global_settings.php" class="nav-link">
                    <i class="bi bi-gear-wide-connected"></i>
                    Configurações Globais
                </a>
            </div>
            <div class="nav-item">
                <a href="affiliates.php" class="nav-link">
                    <i class="bi bi-share"></i>
                    Gestão de Afiliados
                </a>
            </div>
            <div class="nav-item">
                <a href="affiliate_levels.php" class="nav-link">
                    <i class="bi bi-diagram-3"></i>
                    Níveis de Afiliados
                </a>
            </div>
            
            <div class="nav-item">
                <a href="depositos.php" class="nav-link">
                    <i class="bi bi-wallet2"></i>
                    Ver Depósitos
                </a>
            </div>
            <div class="nav-item">
                <a href="controle_raspadinha.php" class="nav-link">
                    <i class="bi bi-dice-6"></i>
                    Controle de Raspadinha
                </a>
            </div>
            <div class="nav-item">
                <a href="saques_pix" class="nav-link">
                    <i class="bi bi-cash-stack"></i>
                    Saques
                </a>
            </div>
            <div class="nav-item">
                <a href="config.php" class="nav-link">
                    <i class="bi bi-gear"></i>
                    Configurações (RTP)
                </a>
            </div>
            <div class="nav-item">
                <a href="relatorio.php" class="nav-link">
                    <i class="bi bi-graph-up"></i>
                    Relatórios
                </a>
            </div>
            <hr style="border-color: rgba(255, 255, 255, 0.1); margin: 1rem;">
            <div class="nav-item">
                <a href="logout.php" class="nav-link logout-btn">
                    <i class="bi bi-box-arrow-right"></i>
                    Sair
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Bar -->
        <div class="top-bar fade-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="welcome-text">Bem-vindo de volta!</h1>
                    <p class="welcome-subtitle">Aqui está um resumo das atividades de hoje</p>
                    <div class="mt-2">
                        <span class="badge bg-primary me-2">Plataforma liberada por QICBUSINESS</span>
                        <a href="https://t.me/+QNv-hPVLFEAxNjAx" target="_blank" class="btn btn-sm" style="background: #ff6b35; border-color: #ff6b35; color: white;">
                            <i class="bi bi-telegram me-1"></i>Acesse nosso grupo
                        </a>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-success fs-6">Sistema Online</span>
                    <div class="text-end">
                        <div class="fw-semibold"><?php echo date('d/m/Y'); ?></div>
                        <div class="text-muted small" id="currentTime"><?php echo date('H:i'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <i class="bi bi-people"></i>
                </div>
                <h3 class="stat-value"><?php echo number_format($total_usuarios, 0, ',', '.'); ?></h3>
                <p class="stat-label">Total de Usuários</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <h3 class="stat-value">R$ <?php echo number_format($total_depositos, 2, ',', '.'); ?></h3>
                <p class="stat-label">Total em Depósitos</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                    <i class="bi bi-dice-6"></i>
                </div>
                <h3 class="stat-value"><?php echo number_format($raspadinhas_hoje, 0, ',', '.'); ?></h3>
                <p class="stat-label">Raspadinhas Hoje</p>
            </div>
            
           
                
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="chart-container fade-in">
            <h4 class="chart-title">Ações Rápidas</h4>
            <div class="quick-actions">
                <a href="usuarios.php" class="action-btn">
                    <div class="action-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div class="action-text">
                        <div class="fw-semibold">Gerenciar Usuários</div>
                        <div class="text-muted small">Visualizar e editar usuários</div>
                    </div>
                </a>
                
                <a href="depositos.php" class="action-btn">
                    <div class="action-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="action-text">
                        <div class="fw-semibold">Ver Depósitos</div>
                        <div class="text-muted small">Histórico de depósitos</div>
                    </div>
                </a>
                
                <a href="payout_management.php" class="action-btn">
                    <div class="action-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <div class="action-text">
                        <div class="fw-semibold">Gestão de Pagamentos</div>
                       
                    </div>
                </a>
                
                <a href="config.php" class="action-btn">
                    <div class="action-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                        <i class="bi bi-sliders"></i>
                    </div>
                    <div class="action-text">
                        <div class="fw-semibold">Ajustar RTP</div>
                        <div class="text-muted small">Configurar retorno</div>
                    </div>
                </a>
                
                <a href="relatorio.php" class="action-btn">
                    <div class="action-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="action-text">
                        <div class="fw-semibold">Relatórios</div>
                        <div class="text-muted small">Gerar relatório</div>
                    </div>
                </a>
                
                <a href="../raspadinhas" class="action-btn">
                    <div class="action-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                        <i class="bi bi-house"></i>
                    </div>
                    <div class="action-text">
                        <div class="fw-semibold">Ir para o Site</div>
                        <div class="text-muted small">Visualizar site público</div>
                    </div>
                </a>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mobile menu functionality
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
            
            // Update menu icon
            const icon = mobileMenuBtn.querySelector('i');
            if (sidebar.classList.contains('show')) {
                icon.className = 'bi bi-x';
            } else {
                icon.className = 'bi bi-list';
            }
        }

        function closeSidebar() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            const icon = mobileMenuBtn.querySelector('i');
            icon.className = 'bi bi-list';
        }

        mobileMenuBtn.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', closeSidebar);

        // Close sidebar when clicking on nav links in mobile
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });

        // Animação de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll(".fade-in");
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                }, index * 100);
            });
        });

        // Atualização em tempo real do relógio
        function updateTime() {
            const now = new Date();
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = now.toLocaleTimeString('pt-BR', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        }

        setInterval(updateTime, 1000);

        // Touch gestures for mobile
        let touchStartX = 0;
        let touchEndX = 0;

        document.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        });

        document.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleGesture();
        });

        function handleGesture() {
            const swipeThreshold = 50;
            const swipeDistance = touchEndX - touchStartX;
            
            if (window.innerWidth <= 768) {
                // Swipe right to open sidebar
                if (swipeDistance > swipeThreshold && touchStartX < 50) {
                    if (!sidebar.classList.contains('show')) {
                        toggleSidebar();
                    }
                }
                // Swipe left to close sidebar
                else if (swipeDistance < -swipeThreshold && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }
        }

        // Prevent zoom on double tap for better mobile experience
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
    </script>
</body>
</html>

