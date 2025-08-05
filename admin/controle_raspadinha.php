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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Raspadinha - Painel Administrativo</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
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

        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .page-title {
            color: var(--dark-color);
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .page-subtitle {
            color: var(--secondary-color);
            margin: 0;
            font-size: 1rem;
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
            
            .page-header {
                padding: 1rem 1.25rem;
                margin-bottom: 1.5rem;
            }
            
            .page-title {
                font-size: 1.25rem;
            }
            
            .page-subtitle {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 0.75rem;
                padding-top: calc(var(--header-height) + 0.75rem);
            }
            
            .page-header {
                padding: 1rem;
                border-radius: 0.75rem;
                margin-bottom: 1rem;
            }
            
            .page-title {
                font-size: 1.125rem;
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
            <i class="bi bi-dice-6 me-2"></i>
            Controle de Raspadinha
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
            <a href="index.php" class="sidebar-brand">
                <i class="bi bi-speedometer2"></i>
                Painel Admin
            </a>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-item">
                <a href="index.php" class="nav-link">
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
                <a href="raspadinhas.php" class="nav-link active">
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
        <!-- Page Header -->
        <div class="page-header fade-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">🎮 Controle de Raspadinha</h1>
                    <p class="page-subtitle">Configure a chance de vitória dos jogos de raspadinha</p>
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

        <!-- Módulo de Controle de Chance de Vitória -->
        <div class="fade-in">
            <!-- MÓDULO: Controle de Chance de Vitória - Raspadinha -->
            <!-- Este código pode ser integrado diretamente no seu painel admin existente -->

            <div id="modulo-chance-vitoria" class="modulo-admin">
                <div class="header-modulo">
                    <h3>🎮 Controle de Chance de Vitória - Raspadinha</h3>
                    <p>Configure a probabilidade de vitória dos jogos de raspadinha</p>
                </div>

                <!-- Status Atual -->
                <div class="status-card-modulo">
                    <div class="status-item-modulo">
                        <span class="status-label-modulo">Chance Atual:</span>
                        <span class="status-value-modulo chance-display-modulo" id="chanceAtualModulo">0.0%</span>
                    </div>
                    <div class="status-item-modulo">
                        <span class="status-label-modulo">Última Atualização:</span>
                        <span class="status-value-modulo" id="ultimaAtualizacaoModulo">-</span>
                    </div>
                    <div class="status-item-modulo">
                        <span class="status-label-modulo">Status:</span>
                        <span class="status-value-modulo" style="color: #4CAF50;">🟢 Ativo</span>
                    </div>
                </div>

                <!-- Controles -->
                <div class="controles-modulo">
                    <label for="novaChanceModulo">Configurar Nova Chance:</label>
                    
                    <!-- Slider -->
                    <div class="range-container-modulo">
                        <input type="range" id="chanceRangeModulo" class="range-input-modulo" min="0" max="1" step="0.01" value="0" oninput="atualizarChanceDisplayModulo()">
                        <div class="range-labels-modulo">
                            <span>0% (Nunca paga)</span>
                            <span>50%</span>
                            <span>100% (Sempre paga)</span>
                        </div>
                    </div>
                    
                    <!-- Input numérico -->
                    <input type="number" id="novaChanceModulo" min="0" max="1" step="0.01" placeholder="0.00" oninput="atualizarChanceRangeModulo()">
                    <small style="color: #666; display: block; margin-top: 5px;">
                        💡 Dica: 0.0 = nunca paga, 0.3 = 30% de chance, 1.0 = sempre paga
                    </small>
                    
                    <!-- Botões -->
                    <div class="botoes-modulo">
                        <button class="btn-modulo btn-success-modulo" onclick="atualizarChanceModulo()">✅ Atualizar Chance</button>
                        <button class="btn-modulo btn-info-modulo" onclick="carregarStatusModulo()">🔄 Recarregar Status</button>
                    </div>
                </div>

                <!-- Alertas -->
                <div id="alertContainerModulo"></div>
            </div>

            <!-- CSS do Módulo -->
            <style>
            #modulo-chance-vitoria {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 1rem;
                padding: 2rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                margin: 20px 0;
                max-width: 800px;
            }

            .header-modulo h3 {
                color: #333;
                margin-bottom: 5px;
                font-size: 20px;
            }

            .header-modulo p {
                color: #666;
                margin-bottom: 20px;
                font-size: 14px;
            }

            .status-card-modulo {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 20px;
                border-left: 4px solid #667eea;
            }

            .status-item-modulo {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 8px;
            }

            .status-item-modulo:last-child {
                margin-bottom: 0;
            }

            .status-label-modulo {
                color: #666;
                font-weight: 600;
                font-size: 14px;
            }

            .status-value-modulo {
                color: #333;
                font-weight: bold;
                font-size: 14px;
            }

            .chance-display-modulo {
                font-size: 18px;
                color: #667eea;
            }

            .controles-modulo label {
                display: block;
                margin-bottom: 10px;
                color: #333;
                font-weight: 600;
                font-size: 14px;
            }

            .range-container-modulo {
                margin: 15px 0;
            }

            .range-input-modulo {
                width: 100%;
                height: 6px;
                border-radius: 3px;
                background: #e1e5e9;
                outline: none;
                -webkit-appearance: none;
            }

            .range-input-modulo::-webkit-slider-thumb {
                -webkit-appearance: none;
                appearance: none;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #667eea;
                cursor: pointer;
            }

            .range-input-modulo::-moz-range-thumb {
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: #667eea;
                cursor: pointer;
                border: none;
            }

            .range-labels-modulo {
                display: flex;
                justify-content: space-between;
                margin-top: 8px;
                font-size: 11px;
                color: #666;
            }

            #novaChanceModulo {
                width: 100%;
                padding: 10px 12px;
                border: 2px solid #e1e5e9;
                border-radius: 6px;
                font-size: 14px;
                transition: border-color 0.3s ease;
            }

            #novaChanceModulo:focus {
                outline: none;
                border-color: #667eea;
            }

            .botoes-modulo {
                margin-top: 15px;
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .btn-modulo {
                padding: 10px 16px;
                border: none;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                flex: 1;
                min-width: 120px;
            }

            .btn-success-modulo {
                background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
                color: white;
            }

            .btn-success-modulo:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
            }

            .btn-info-modulo {
                background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
                color: white;
            }

            .btn-info-modulo:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
            }

            .alert-modulo {
                padding: 10px 14px;
                border-radius: 6px;
                margin-top: 15px;
                font-weight: 600;
                font-size: 13px;
            }

            .alert-success-modulo {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }

            .alert-error-modulo {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

            .alert-info-modulo {
                background: #d1ecf1;
                color: #0c5460;
                border: 1px solid #bee5eb;
            }

            /* Responsividade */
            @media (max-width: 480px) {
                #modulo-chance-vitoria {
                    padding: 15px;
                    margin: 10px 0;
                }
                
                .botoes-modulo {
                    flex-direction: column;
                }
                
                .btn-modulo {
                    min-width: auto;
                }
            }
            </style>

            <!-- JavaScript do Módulo -->
            <script>
            // Configurações do módulo
           const MODULO_API_BASE = '/admin/api.php';


function atualizarChance(novaChance) {
  fetch(`${MODULO_API_BASE}?action=atualizar_chance`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': 'Bearer admin_token_123' // mesmo token que você definiu na API
    },
    body: JSON.stringify({ chance_vitoria: novaChance })
  })
  .then(res => res.json())
  .then(data => {
    if (data.sucesso) {
      alert('Chance atualizada com sucesso!');
    } else {
      alert('Erro: ' + data.erro);
    }
  })
  .catch(err => {
    console.error('Erro de conexão:', err);
  });
}
 

            // Inicialização do módulo
            document.addEventListener('DOMContentLoaded', function() {
                carregarStatusModulo();
            });

            // Função para carregar status atual
            async function carregarStatusModulo() {
                try {
                    const response = await fetch(`${MODULO_API_BASE}?action=config`);
                    const data = await response.json();

                    if (data.sucesso) {
                        document.getElementById('chanceAtualModulo').textContent = (data.chance_vitoria * 100).toFixed(1) + '%';
                        document.getElementById('ultimaAtualizacaoModulo').textContent = data.ultima_atualizacao;
                        document.getElementById('novaChanceModulo').value = data.chance_vitoria;
                        document.getElementById('chanceRangeModulo').value = data.chance_vitoria;
                    } else {
                        mostrarAlertaModulo('Erro ao carregar configurações: ' + (data.erro || 'Erro desconhecido'), 'error');
                    }
                } catch (error) {
                    mostrarAlertaModulo('Erro de conexão: ' + error.message, 'error');
                }
            }

            // Função para atualizar chance de vitória
            async function atualizarChanceModulo() {
                const novaChance = parseFloat(document.getElementById('novaChanceModulo').value);

                if (isNaN(novaChance) || novaChance < 0 || novaChance > 1) {
                    mostrarAlertaModulo('Chance deve ser um número entre 0 e 1', 'error');
                    return;
                }

                try {
                    // Aqui você deve usar o token de autenticação do seu painel admin existente
                    // Substitua 'SEU_TOKEN_AQUI' pelo token real do usuário logado
                    const authToken = localStorage.getItem('admin_token') || 'admin_token_123'; // Ajuste conforme seu sistema

                    const response = await fetch(`${MODULO_API_BASE}?action=atualizar_chance`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${authToken}`
                        },
                        body: JSON.stringify({ chance_vitoria: novaChance })
                    });

                    const data = await response.json();

                    if (data.sucesso) {
                        mostrarAlertaModulo('Chance atualizada com sucesso!', 'success');
                        carregarStatusModulo();
                    } else {
                        mostrarAlertaModulo(data.erro || 'Erro ao atualizar', 'error');
                    }
                } catch (error) {
                    mostrarAlertaModulo('Erro de conexão: ' + error.message, 'error');
                }
            }

            // Função para atualizar display da chance
            function atualizarChanceDisplayModulo() {
                const valor = document.getElementById('chanceRangeModulo').value;
                document.getElementById('novaChanceModulo').value = valor;
            }

            // Função para atualizar range da chance
            function atualizarChanceRangeModulo() {
                const valor = document.getElementById('novaChanceModulo').value;
                document.getElementById('chanceRangeModulo').value = valor;
            }

            // Função para mostrar alertas
            function mostrarAlertaModulo(mensagem, tipo) {
                const container = document.getElementById('alertContainerModulo');
                
                // Remove alertas anteriores
                container.innerHTML = '';
                
                const alerta = document.createElement('div');
                alerta.className = `alert-modulo alert-${tipo}-modulo`;
                alerta.textContent = mensagem;
                
                container.appendChild(alerta);
                
                setTimeout(() => {
                    alerta.remove();
                }, 5000);
            }

            // Event listeners para Enter
            document.getElementById('novaChanceModulo').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    atualizarChanceModulo();
                }
            });
            </script>
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

