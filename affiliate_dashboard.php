<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/affiliate_functions.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['usuario_id'];

// Buscar informações do usuário
$stmt = $conn->prepare("SELECT name, email, affiliate_status, affiliate_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: login.php');
    exit();
}

// Criar afiliado se não existir
$affiliate_code = createAffiliateIfNotExists($conn, $user_id, $user['name']);

// Buscar estatísticas do afiliado
$stats = getAffiliateStats($conn, $user_id);

// Buscar informações detalhadas do afiliado
$stmt = $conn->prepare("SELECT * FROM affiliates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$affiliate_info = $stmt->get_result()->fetch_assoc();

// Processar solicitação de saque
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'request_payout') {
    $amount = (float)$_POST['amount'];
    
    if ($amount > 0 && $amount <= $user['affiliate_balance']) {
        $stmt = $conn->prepare("INSERT INTO payouts (affiliate_id, amount) VALUES (?, ?)");
        $stmt->bind_param("id", $affiliate_info['id'], $amount);
        
        if ($stmt->execute()) {
            $success_message = "Solicitação de saque enviada com sucesso!";
        } else {
            $error_message = "Erro ao processar solicitação de saque.";
        }
    } else {
        $error_message = "Valor inválido para saque.";
    }
}

// Buscar histórico de comissões
$stmt = $conn->prepare("
    SELECT c.* 
    FROM commissions c 
    WHERE c.affiliate_id = ? 
    ORDER BY c.created_at DESC 
    LIMIT 20
");
$stmt->bind_param("i", $affiliate_info['id']);
$stmt->execute();
$commissions_history = $stmt->get_result();

// Buscar solicitações de saque
$stmt = $conn->prepare("SELECT * FROM payouts WHERE affiliate_id = ? ORDER BY request_date DESC LIMIT 10");
$stmt->bind_param("i", $affiliate_info['id']);
$stmt->execute();
$payouts_history = $stmt->get_result();

// Buscar indicações diretas
$stmt = $conn->prepare("
    SELECT u.name, u.email, r.created_at 
    FROM referrals r 
    JOIN users u ON r.referred_id = u.id 
    WHERE r.referrer_id = ? AND r.level = 1 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$direct_referrals = $stmt->get_result();

// Gerar link de afiliado
$affiliate_link = "http://" . $_SERVER['HTTP_HOST'] . "/inicio.php?ref=" . $affiliate_code;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Afiliado - Raspa Sorte</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #7257b4;
            --secondary-color: #6876df;
            --success-color: #10b981;
            --warning-color: #fbbf24;
            --danger-color: #ef4444;
            --dark-color: #202c3e;
            --light-color: #f8fafc;
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

        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .main-container {
            padding: 2rem 0;
        }

        .dashboard-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: rgba(114, 87, 180, 0.3);
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            color: white;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(114, 87, 180, 0.4);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .affiliate-link-card {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border-radius: 1rem;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
        }

        .link-input {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.5rem;
            color: white;
            padding: 0.75rem;
            width: 100%;
            margin: 1rem 0;
        }

        .link-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .copy-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .table-dark {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .table-dark th {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-weight: 600;
        }

        .table-dark td {
            border-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(114, 87, 180, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #f59e0b);
            border: none;
            color: #1f2937;
        }

        .alert {
            border-radius: 0.75rem;
            border: none;
        }

        .modal-content {
            background: var(--dark-color);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
        }

        .modal-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 0.5rem;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 0 0 0.2rem rgba(114, 87, 180, 0.25);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-weight: 600;
        }

        .commission-card {
            background: linear-gradient(135deg, var(--warning-color), #f59e0b);
            border-radius: 1rem;
            padding: 1.5rem;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .level-indicator {
            display: inline-block;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            text-align: center;
            line-height: 2rem;
            font-weight: 700;
            margin-right: 0.5rem;
        }

        .performance-stats {
            padding: 1rem;
        }

        .stat-item {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            text-align: center;
        }

        .stat-item h6 {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .notification-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--success-color);
        }

        .notification-item.warning {
            border-left-color: var(--warning-color);
        }

        .notification-item.info {
            border-left-color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .dashboard-card {
                padding: 1.5rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
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
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="inicio.php">
                <i class="bi bi-coins"></i> Raspa Sorte - Afiliados
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white" href="perfil.php">
                    <i class="bi bi-person"></i> Perfil
                </a>
                <a class="nav-link text-white" href="raspadinhas">
                    <i class="bi bi-box-arrow-right"></i> Inicio 
                </a>
            </div>
        </div>
    </nav>

    <div class="container main-container">
        <!-- Mensagens -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Boas-vindas -->
        <div class="dashboard-card fade-in">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">Bem-vindo, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                    <p class="mb-0 opacity-75">Gerencie suas indicações e acompanhe seus ganhos como afiliado</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="badge bg-success fs-6">
                        <i class="bi bi-check-circle"></i> Afiliado Ativo
                    </div>
                </div>
            </div>
        </div>

        <!-- Link de Afiliado -->
        <div class="affiliate-link-card fade-in">
            <h4 class="mb-3">
                <i class="bi bi-link-45deg"></i> Seu Link de Afiliado
            </h4>
            <p class="mb-3">Compartilhe este link para ganhar comissões por cada pessoa que se cadastrar:</p>
            <div class="row align-items-center">
                <div class="col-md-9">
                    <input type="text" class="link-input" id="affiliateLink" value="<?php echo $affiliate_link; ?>" readonly>
                </div>
                <div class="col-md-3">
                    <button class="copy-btn w-100" onclick="copyAffiliateLink()">
                        <i class="bi bi-clipboard"></i> Copiar
                    </button>
                </div>
            </div>
            <small class="opacity-75">
                <i class="bi bi-info-circle"></i> 
                Seu código único: <strong><?php echo $affiliate_code; ?></strong>
            </small>
        </div>

        <!-- Estatísticas -->
        <div class="row fade-in">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['clicks']); ?></div>
                    <div class="stat-label">Cliques</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['signups']); ?></div>
                    <div class="stat-label">Cadastros</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($stats['deposits']); ?></div>
                    <div class="stat-label">Depósitos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-value">R$ <?php echo number_format($stats['balance'], 2, ',', '.'); ?></div>
                    <div class="stat-label">Saldo Disponível</div>
                </div>
            </div>
        </div>

        <!-- Comissões por Tipo -->
        <div class="row fade-in">
            <div class="col-md-12">
                <div class="commission-card">
                    <h5 class="mb-2">
                        <i class="bi bi-arrow-repeat"></i> Comissão RevShare
                    </h5>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="h4 mb-0">R$ <?php echo number_format($stats["revshare_commission"], 2, ",", "."); ?></div>
                            <small>Por depósitos realizados</small>
                        </div>
                        <div class="text-end">
                            <div class="h6 mb-0"><?php echo number_format($affiliate_info["revshare_commission_rate"], 1); ?>%</div>
                            <small>Taxa atual</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="dashboard-card fade-in">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="mb-3">Ações Rápidas</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#payoutModal">
                                <i class="bi bi-cash-coin"></i> Solicitar Saque
                            </button>
                        </div>
                        <div class="col-md-6 mb-3">
                            <button class="btn btn-success w-100" onclick="shareOnWhatsApp()">
                                <i class="bi bi-whatsapp"></i> Compartilhar no WhatsApp
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h5>Total de Comissões</h5>
                        <div class="h2 text-success">R$ <?php echo number_format($stats["revshare_commission"], 2, ",", "."); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Desempenho -->
        <div class="dashboard-card fade-in">
            <h4 class="mb-3">
                <i class="bi bi-graph-up"></i> Desempenho dos Últimos 30 Dias
            </h4>
            <div class="row">
                <div class="col-md-8">
                    <canvas id="performanceChart" height="100"></canvas>
                </div>
                <div class="col-md-4">
                    <div class="performance-stats">
                        <div class="stat-item">
                            <h6>Melhor Dia</h6>
                            <div class="h5 text-success" id="bestDay">-</div>
                        </div>
                        <div class="stat-item">
                            <h6>Média Diária</h6>
                            <div class="h5 text-info" id="dailyAverage">-</div>
                        </div>
                        <div class="stat-item">
                            <h6>Tendência</h6>
                            <div class="h5" id="trend">
                                <i class="bi bi-arrow-up text-success"></i> +12%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Histórico de Comissões -->
        <div class="dashboard-card fade-in">
            <h4 class="mb-3">
                <i class="bi bi-clock-history"></i> Histórico de Comissões
            </h4>
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Nível</th>
                            <th>Valor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($commissions_history->num_rows > 0): ?>
                            <?php while ($commission = $commissions_history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($commission['created_at'])); ?></td>
                                <td>
                                    <span class="badge <?php echo $commission['type'] == 'CPA' ? 'bg-primary' : 'bg-warning'; ?>">
                                        <?php echo $commission['type']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($commission['referred_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="level-indicator"><?php echo $commission['level']; ?></span>
                                </td>
                                <td class="text-success">R$ <?php echo number_format($commission['amount'], 2, ',', '.'); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo $commission['status'] == 'approved' ? 'bg-success' : 
                                             ($commission['status'] == 'pending' ? 'bg-warning' : 'bg-danger'); 
                                    ?>">
                                        <?php 
                                        echo $commission['status'] == 'approved' ? 'Aprovada' : 
                                             ($commission['status'] == 'pending' ? 'Pendente' : 'Cancelada'); 
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="bi bi-inbox"></i> Nenhuma comissão encontrada
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Histórico de Saques -->
        <div class="dashboard-card fade-in">
            <h4 class="mb-3">
                <i class="bi bi-cash-stack"></i> Histórico de Saques
            </h4>
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead>
                        <tr>
                            <th>Data da Solicitação</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Data do Pagamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payouts_history->num_rows > 0): ?>
                            <?php while ($payout = $payouts_history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($payout['request_date'])); ?></td>
                                <td class="text-success">R$ <?php echo number_format($payout['amount'], 2, ',', '.'); ?></td>
                                <td>
                                    <span class="badge <?php 
                                        echo $payout['status'] == 'paid' ? 'bg-success' : 
                                             ($payout['status'] == 'pending' ? 'bg-warning' : 'bg-danger'); 
                                    ?>">
                                        <?php 
                                        echo $payout['status'] == 'paid' ? 'Pago' : 
                                             ($payout['status'] == 'pending' ? 'Pendente' : 'Cancelado'); 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    echo $payout['paid_date'] ? date('d/m/Y H:i', strtotime($payout['paid_date'])) : '-'; 
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="bi bi-inbox"></i> Nenhum saque solicitado ainda
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Indicações Diretas -->
        <div class="dashboard-card fade-in">
            <h4 class="mb-3">
                <i class="bi bi-people"></i> Suas Indicações Diretas
            </h4>
            <div class="table-responsive">
                <table class="table table-dark">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Data do Cadastro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($direct_referrals->num_rows > 0): ?>
                            <?php while ($referral = $direct_referrals->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($referral['name']); ?></td>
                                <td><?php echo htmlspecialchars($referral['email']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($referral['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">
                                    <i class="bi bi-person-plus"></i> Nenhuma indicação ainda
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Saque -->
    <div class="modal fade" id="payoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white">
                        <i class="bi bi-cash-coin"></i> Solicitar Saque
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="request_payout">
                        
                        <div class="mb-3">
                            <label class="form-label text-white">Saldo Disponível</label>
                            <div class="h4 text-success">R$ <?php echo number_format($stats['balance'], 2, ',', '.'); ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-white">Valor do Saque</label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="10" max="<?php echo $stats['balance']; ?>" placeholder="Valor mínimo: R$ 10,00" required>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Informações importantes:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Valor mínimo para saque: R$ 10,00</li>
                                <li>Processamento em até 2 dias úteis</li>
                                <li>Pagamento via PIX</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Solicitar Saque</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function copyAffiliateLink() {
            const linkInput = document.getElementById('affiliateLink');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            document.execCommand('copy');
            
            // Feedback visual
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="bi bi-check"></i> Copiado!';
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
            }, 2000);
        }

        function shareOnWhatsApp() {
            const link = document.getElementById('affiliateLink').value;
            const message = `🎯 Venha jogar raspadinha online e ganhar dinheiro de verdade!\n\n💰 Cadastre-se pelo meu link e comece a ganhar:\n${link}\n\n🎮 Raspadinhas virtuais com prêmios em PIX!\n✅ Pagamento instantâneo\n🔒 100% seguro`;
            const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
            window.open(whatsappUrl, '_blank');
        }

        // Gráfico de Desempenho
        function initPerformanceChart() {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            
            // Dados simulados dos últimos 30 dias
            const labels = [];
            const data = [];
            const today = new Date();
            
            for (let i = 29; i >= 0; i--) {
                const date = new Date(today);
                date.setDate(date.getDate() - i);
                labels.push(date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
                data.push(Math.floor(Math.random() * 50) + 10); // Dados simulados
            }
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Comissões (R$)',
                        data: data,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: 'white'
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.8)'
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        },
                        y: {
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.8)',
                                callback: function(value) {
                                    return 'R$ ' + value;
                                }
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            }
                        }
                    }
                }
            });
            
            // Atualizar estatísticas
            const maxValue = Math.max(...data);
            const avgValue = data.reduce((a, b) => a + b, 0) / data.length;
            
            document.getElementById('bestDay').textContent = 'R$ ' + maxValue.toFixed(2);
            document.getElementById('dailyAverage').textContent = 'R$ ' + avgValue.toFixed(2);
        }

        // Animação de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                }, index * 100);
            });
            
            // Inicializar gráfico
            initPerformanceChart();
        });
    </script>
</body>
</html>

