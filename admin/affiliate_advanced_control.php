<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/affiliate_functions.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Verifica se é admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !$user['is_admin']) {
    header("Location: ../login.php");
    exit();
}

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_advanced_settings':
                $affiliate_id = (int)$_POST['affiliate_id'];
                $cpa_rate_admin = (float)$_POST['cpa_rate_admin'];
                $revshare_rate_admin = (float)$_POST['revshare_rate_admin'];
                $fixed_commission = (float)$_POST['fixed_commission'];
                $allow_sub_earnings = isset($_POST['allow_sub_earnings']) ? 1 : 0;
                $force_fake_stats = isset($_POST['force_fake_stats']) ? 1 : 0;
                
                $stmt = $conn->prepare("UPDATE affiliates SET cpa_commission_rate_admin = ?, revshare_commission_rate_admin = ?, fixed_commission_per_signup = ?, allow_sub_affiliate_earnings = ?, force_fake_stats = ? WHERE id = ?");
                $stmt->bind_param("ddddii", $cpa_rate_admin, $revshare_rate_admin, $fixed_commission, $allow_sub_earnings, $force_fake_stats, $affiliate_id);
                $stmt->execute();
                
                $success_message = "Configurações avançadas atualizadas!";
                break;
                
            case 'add_fake_stats':
                $affiliate_id = (int)$_POST['affiliate_id'];
                $fake_clicks = (int)$_POST['fake_clicks'];
                $fake_conversions = (int)$_POST['fake_conversions'];
                $fake_commissions = (float)$_POST['fake_commissions'];
                $report_date = $_POST['report_date'];
                
                // Verificar se já existe registro para esta data
                $stmt = $conn->prepare("SELECT id FROM admin_simulated_reports WHERE affiliate_id = ? AND report_date = ?");
                $stmt->bind_param("is", $affiliate_id, $report_date);
                $stmt->execute();
                $existing = $stmt->get_result()->fetch_assoc();
                
                if ($existing) {
                    // Atualizar registro existente
                    $stmt = $conn->prepare("UPDATE admin_simulated_reports SET simulated_clicks = ?, simulated_conversions = ?, simulated_commissions = ? WHERE id = ?");
                    $stmt->bind_param("iidi", $fake_clicks, $fake_conversions, $fake_commissions, $existing['id']);
                } else {
                    // Criar novo registro
                    $stmt = $conn->prepare("INSERT INTO admin_simulated_reports (affiliate_id, simulated_clicks, simulated_conversions, simulated_commissions, report_date) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iidis", $affiliate_id, $fake_clicks, $fake_conversions, $fake_commissions, $report_date);
                }
                $stmt->execute();
                
                $success_message = "Estatísticas falsas adicionadas!";
                break;
                
            case 'force_commission':
                $affiliate_id = (int)$_POST['affiliate_id'];
                $commission_amount = (float)$_POST['commission_amount'];
                $commission_type = $_POST['commission_type'];
                $description = $_POST['description'];
                
                // Adicionar comissão forçada
                $stmt = $conn->prepare("INSERT INTO commissions (affiliate_id, type, amount, description, status, created_at) VALUES (?, ?, ?, ?, 'approved', NOW())");
                $stmt->bind_param("isds", $affiliate_id, $commission_type, $commission_amount, $description);
                $stmt->execute();
                
                // Atualizar saldo do afiliado
                $stmt = $conn->prepare("SELECT user_id FROM affiliates WHERE id = ?");
                $stmt->bind_param("i", $affiliate_id);
                $stmt->execute();
                $affiliate_data = $stmt->get_result()->fetch_assoc();
                
                if ($affiliate_data) {
                    $stmt = $conn->prepare("UPDATE users SET affiliate_balance = affiliate_balance + ? WHERE id = ?");
                    $stmt->bind_param("di", $commission_amount, $affiliate_data['user_id']);
                    $stmt->execute();
                }
                
                $success_message = "Comissão forçada adicionada!";
                break;
                
            case 'bulk_update_rates':
                $new_cpa_rate = (float)$_POST['bulk_cpa_rate'];
                $new_revshare_rate = (float)$_POST['bulk_revshare_rate'];
                $apply_to_all = isset($_POST['apply_to_all']);
                
                if ($apply_to_all) {
                    $stmt = $conn->prepare("UPDATE affiliates SET cpa_commission_rate_admin = ?, revshare_commission_rate_admin = ? WHERE is_active = 1");
                    $stmt->bind_param("dd", $new_cpa_rate, $new_revshare_rate);
                    $stmt->execute();
                    
                    $success_message = "Taxas atualizadas para todos os afiliados ativos!";
                }
                break;
        }
    }
}

// Buscar todos os afiliados
$affiliates_query = "
    SELECT 
        a.id as affiliate_id,
        a.user_id,
        a.affiliate_code,
        a.cpa_commission_rate,
        a.revshare_commission_rate,
        a.cpa_commission_rate_admin,
        a.revshare_commission_rate_admin,
        a.fixed_commission_per_signup,
        a.allow_sub_affiliate_earnings,
        a.force_fake_stats,
        a.is_active,
        u.name,
        u.email,
        u.affiliate_balance
    FROM affiliates a
    JOIN users u ON a.user_id = u.id
    ORDER BY u.name
";

$affiliates_result = $conn->query($affiliates_query);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle Avançado de Afiliados - Admin</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #dc2626;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --sidebar-width: 280px;
        }

        body {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

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
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(4px);
        }

        .nav-link.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }

        .content-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }

        .warning-banner {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .warning-banner h4 {
            margin-bottom: 0.5rem;
        }

        .control-card {
            border: 2px solid #fee2e2;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            background: #fef2f2;
            position: relative;
        }

        .control-card::before {
            content: '⚠️';
            position: absolute;
            top: -10px;
            right: 20px;
            background: #dc2626;
            color: white;
            padding: 0.5rem;
            border-radius: 50%;
            font-size: 1.2rem;
        }

        .control-header {
            border-bottom: 2px solid #fecaca;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .control-header h5 {
            color: #dc2626;
            font-weight: 700;
            margin: 0;
        }

        .form-control, .form-select {
            border: 2px solid #fecaca;
            border-radius: 0.5rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            border: none;
            font-weight: 600;
        }

        .btn-warning {
            background: linear-gradient(135deg, #d97706, #b45309);
            border: none;
            font-weight: 600;
        }

        .table-danger {
            background: #fef2f2;
        }

        .table-danger th {
            background: #fee2e2;
            color: #dc2626;
            font-weight: 700;
        }

        .badge-danger {
            background: #dc2626;
        }

        .badge-warning {
            background: #d97706;
        }

        .modal-header.bg-danger {
            background: linear-gradient(135deg, #dc2626, #991b1b) !important;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <i class="bi bi-speedometer2"></i>
                Admin Panel
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
                <a href="affiliate_reports.php" class="nav-link">
                    <i class="bi bi-file-earmark-text"></i>
                    Relatórios de Afiliados
                </a>
            </div>
            <div class="nav-item">
                <a href="affiliate_advanced_control.php" class="nav-link active">
                    <i class="bi bi-shield-exclamation"></i>
                    Controle Avançado
                </a>
            </div>
            <div class="nav-item">
                <a href="depositos.php" class="nav-link">
                    <i class="bi bi-wallet2"></i>
                    Ver Depósitos
                </a>
            </div>
            <div class="nav-item">
                <a href="raspadinhas.php" class="nav-link">
                    <i class="bi bi-dice-6"></i>
                    Histórico de Raspadinhas
                </a>
            </div>
            <div class="nav-item">
                <a href="configuracoes.php" class="nav-link">
                    <i class="bi bi-credit-card"></i>
                    BSPay & Imagens
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
                <a href="logout.php" class="nav-link">
                    <i class="bi bi-box-arrow-right"></i>
                    Sair
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Warning Banner -->
        <div class="warning-banner">
            <h4><i class="bi bi-exclamation-triangle"></i> ÁREA DE CONTROLE AVANÇADO</h4>
            <p class="mb-0">Esta área permite manipular dados de afiliados sem que eles saibam. Use com extrema cautela!</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Ações em Lote -->
        <div class="control-card">
            <div class="control-header">
                <h5><i class="bi bi-gear-wide-connected"></i> Ações em Lote</h5>
                <small class="text-muted">Aplicar configurações para múltiplos afiliados</small>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="bulk_update_rates">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Nova Taxa CPA Real (%)</label>
                        <input type="number" class="form-control" name="bulk_cpa_rate" step="0.01" min="0" max="100" placeholder="Ex: 5.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nova Taxa RevShare Real (%)</label>
                        <input type="number" class="form-control" name="bulk_revshare_rate" step="0.01" min="0" max="100" placeholder="Ex: 2.50">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="apply_to_all" id="apply_to_all">
                            <label class="form-check-label" for="apply_to_all">
                                Aplicar a todos os afiliados ativos
                            </label>
                        </div>
                        <button type="submit" class="btn btn-danger mt-2" onclick="return confirm('Confirma a atualização em lote?')">
                            <i class="bi bi-lightning"></i> Aplicar em Lote
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Lista de Afiliados -->
        <div class="content-card">
            <h4 class="mb-4">
                <i class="bi bi-people"></i> Controle Individual de Afiliados
            </h4>
            
            <div class="table-responsive">
                <table class="table table-danger table-hover">
                    <thead>
                        <tr>
                            <th>Afiliado</th>
                            <th>Taxas Visíveis</th>
                            <th>Taxas Reais</th>
                            <th>Configurações</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($affiliate = $affiliates_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($affiliate['name']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($affiliate['affiliate_code']); ?></small>
                                </div>
                            </td>
                            <td>
                                <small>
                                    CPA: <span class="badge bg-info"><?php echo $affiliate['cpa_commission_rate']; ?>%</span><br>
                                    RevShare: <span class="badge bg-info"><?php echo $affiliate['revshare_commission_rate']; ?>%</span>
                                </small>
                            </td>
                            <td>
                                <small>
                                    CPA: <span class="badge badge-danger"><?php echo $affiliate['cpa_commission_rate_admin'] ?: 'Não definido'; ?>%</span><br>
                                    RevShare: <span class="badge badge-danger"><?php echo $affiliate['revshare_commission_rate_admin'] ?: 'Não definido'; ?>%</span>
                                </small>
                            </td>
                            <td>
                                <small>
                                    Sub-afiliados: <span class="badge <?php echo $affiliate['allow_sub_affiliate_earnings'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $affiliate['allow_sub_affiliate_earnings'] ? 'Permitido' : 'Bloqueado'; ?>
                                    </span><br>
                                    Stats Falsas: <span class="badge <?php echo $affiliate['force_fake_stats'] ? 'badge-warning' : 'bg-secondary'; ?>">
                                        <?php echo $affiliate['force_fake_stats'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-danger" onclick="editAdvanced(<?php echo $affiliate['affiliate_id']; ?>)" data-bs-toggle="modal" data-bs-target="#advancedModal">
                                        <i class="bi bi-gear"></i>
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="addFakeStats(<?php echo $affiliate['affiliate_id']; ?>)" data-bs-toggle="modal" data-bs-target="#fakeStatsModal">
                                        <i class="bi bi-graph-up"></i>
                                    </button>
                                    <button class="btn btn-outline-success" onclick="forceCommission(<?php echo $affiliate['affiliate_id']; ?>)" data-bs-toggle="modal" data-bs-target="#forceCommissionModal">
                                        <i class="bi bi-cash-coin"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal de Configurações Avançadas -->
    <div class="modal fade" id="advancedModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-gear"></i> Configurações Avançadas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="advancedForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_advanced_settings">
                        <input type="hidden" name="affiliate_id" id="advanced_affiliate_id">
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Atenção:</strong> Estas configurações não são visíveis ao afiliado.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Taxa CPA Real (%)</label>
                                    <input type="number" class="form-control" name="cpa_rate_admin" id="advanced_cpa_rate" step="0.01" min="0" max="100">
                                    <small class="text-muted">Taxa real paga (diferente da exibida)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Taxa RevShare Real (%)</label>
                                    <input type="number" class="form-control" name="revshare_rate_admin" id="advanced_revshare_rate" step="0.01" min="0" max="100">
                                    <small class="text-muted">Taxa real paga (diferente da exibida)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Comissão Fixa por Cadastro (R$)</label>
                            <input type="number" class="form-control" name="fixed_commission" id="advanced_fixed_commission" step="0.01" min="0">
                            <small class="text-muted">Ignora performance real e paga valor fixo</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allow_sub_earnings" id="advanced_allow_sub">
                                    <label class="form-check-label" for="advanced_allow_sub">
                                        Permitir ganhos de subindicados
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="force_fake_stats" id="advanced_force_fake">
                                    <label class="form-check-label" for="advanced_force_fake">
                                        Forçar estatísticas falsas
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Salvar Configurações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Estatísticas Falsas -->
    <div class="modal fade" id="fakeStatsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-graph-up"></i> Adicionar Estatísticas Falsas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_fake_stats">
                        <input type="hidden" name="affiliate_id" id="fake_affiliate_id">
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle"></i>
                            <strong>Info:</strong> Estas estatísticas serão exibidas apenas no painel do afiliado.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Data do Relatório</label>
                            <input type="date" class="form-control" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Cliques Falsos</label>
                                    <input type="number" class="form-control" name="fake_clicks" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Conversões Falsas</label>
                                    <input type="number" class="form-control" name="fake_conversions" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Comissões Falsas (R$)</label>
                                    <input type="number" class="form-control" name="fake_commissions" step="0.01" min="0" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Adicionar Estatísticas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Comissão Forçada -->
    <div class="modal fade" id="forceCommissionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-coin"></i> Forçar Comissão
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="force_commission">
                        <input type="hidden" name="affiliate_id" id="commission_affiliate_id">
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Info:</strong> Esta comissão será adicionada diretamente ao saldo do afiliado.
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Comissão</label>
                                    <select class="form-select" name="commission_type" required>
                                        <option value="CPA">CPA (Cadastro)</option>
                                        <option value="RevShare">RevShare (Depósito)</option>
                                        <option value="Bonus">Bônus</option>
                                        <option value="Adjustment">Ajuste</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Valor (R$)</label>
                                    <input type="number" class="form-control" name="commission_amount" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" class="form-control" name="description" placeholder="Ex: Bônus especial, Ajuste manual..." required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Adicionar Comissão</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Dados dos afiliados para JavaScript
        const affiliatesData = <?php 
        $affiliates_result->data_seek(0);
        $affiliates_array = [];
        while ($affiliate = $affiliates_result->fetch_assoc()) {
            $affiliates_array[] = $affiliate;
        }
        echo json_encode($affiliates_array);
        ?>;

        function editAdvanced(affiliateId) {
            const affiliate = affiliatesData.find(a => a.affiliate_id == affiliateId);
            if (affiliate) {
                document.getElementById('advanced_affiliate_id').value = affiliate.affiliate_id;
                document.getElementById('advanced_cpa_rate').value = affiliate.cpa_commission_rate_admin || '';
                document.getElementById('advanced_revshare_rate').value = affiliate.revshare_commission_rate_admin || '';
                document.getElementById('advanced_fixed_commission').value = affiliate.fixed_commission_per_signup || '';
                document.getElementById('advanced_allow_sub').checked = affiliate.allow_sub_affiliate_earnings == 1;
                document.getElementById('advanced_force_fake').checked = affiliate.force_fake_stats == 1;
            }
        }

        function addFakeStats(affiliateId) {
            document.getElementById('fake_affiliate_id').value = affiliateId;
        }

        function forceCommission(affiliateId) {
            document.getElementById('commission_affiliate_id').value = affiliateId;
        }
    </script>
</body>
</html>

