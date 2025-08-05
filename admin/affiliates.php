<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/affiliate_functions.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

// Verifica se é admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !$user['is_admin']) {
    header("Location: ../login.php");
    exit();
}

$success_message = '';

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_commission':
                $affiliate_id = (int)$_POST["affiliate_id"];
                $revshare_rate = (float)$_POST["revshare_rate"];
                $revshare_rate_admin = (float)$_POST["revshare_rate_admin"];
                $allow_sub_earnings = isset($_POST["allow_sub_earnings"]) ? 1 : 0;
                
                $stmt = $conn->prepare("UPDATE affiliates SET revshare_commission_rate = ?, revshare_commission_rate_admin = ?, allow_sub_affiliate_earnings = ? WHERE id = ?");
                $stmt->bind_param("ddii", $revshare_rate, $revshare_rate_admin, $allow_sub_earnings, $affiliate_id);
                if ($stmt->execute()) {
                    $success_message = "Comissões atualizadas com sucesso!";
                } else {
                    $success_message = "Erro ao atualizar comissões.";
                }
                break;
                
            case 'toggle_status':
                $affiliate_id = (int)$_POST['affiliate_id'];
                $new_status = (int)$_POST['new_status'];
                
                $stmt = $conn->prepare("UPDATE affiliates SET is_active = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_status, $affiliate_id);
                if ($stmt->execute()) {
                    $success_message = "Status do afiliado atualizado!";
                } else {
                    $success_message = "Erro ao atualizar status do afiliado.";
                }
                break;
                
            case 'add_simulated_data':
                $affiliate_id = (int)$_POST['affiliate_id'];
                $simulated_clicks = (int)$_POST['simulated_clicks'];
                $simulated_conversions = (int)$_POST['simulated_conversions'];
                $report_date = $_POST['report_date'];
                
                $stmt = $conn->prepare("INSERT INTO admin_simulated_reports (affiliate_id, simulated_clicks, simulated_conversions, report_date) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE simulated_clicks = ?, simulated_conversions = ?");
                $stmt->bind_param("iiisii", $affiliate_id, $simulated_clicks, $simulated_conversions, $report_date, $simulated_clicks, $simulated_conversions);
                if ($stmt->execute()) {
                    $success_message = "Dados simulados adicionados!";
                } else {
                    $success_message = "Erro ao adicionar dados simulados.";
                }
                break;
        }
    }
}

// Buscar todos os afiliados com estatísticas
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
        a.is_active,
        a.created_at,
        u.name,
        u.email,
        u.affiliate_balance,
        (SELECT COUNT(*) FROM affiliate_clicks ac WHERE ac.affiliate_id = a.id) as total_clicks,
        (SELECT COUNT(*) FROM affiliate_conversions conv WHERE conv.affiliate_id = a.id AND conv.conversion_type = 'signup') as total_signups,
        (SELECT COUNT(*) FROM affiliate_conversions conv WHERE conv.affiliate_id = a.id AND conv.conversion_type = 'deposit') as total_deposits,
        (SELECT COALESCE(SUM(amount), 0) FROM commissions c WHERE c.affiliate_id = a.id AND c.type = 'CPA') as total_cpa,
        (SELECT COALESCE(SUM(amount), 0) FROM commissions c WHERE c.affiliate_id = a.id AND c.type = 'RevShare') as total_revshare,
        (SELECT COUNT(*) FROM referrals r WHERE r.referrer_id = a.user_id) as total_referrals
    FROM affiliates a
    JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
";

$affiliates_result = $conn->query($affiliates_query);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Afiliados - Admin</title>
    
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
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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

        .affiliate-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .affiliate-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .stat-item {
            text-align: center;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }

        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--secondary-color);
            text-transform: uppercase;
        }

        .commission-form {
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            color: white;
        }

        .table-responsive {
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background: #f8fafc;
            border: none;
            font-weight: 600;
            color: var(--dark-color);
        }

        .table td {
            border-color: #e2e8f0;
            vertical-align: middle;
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
                <a href="affiliates.php" class="nav-link active">
                    <i class="bi bi-share"></i>
                    Gestão de Afiliados
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
        <div class="content-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-1">Gestão de Afiliados</h2>
                    <p class="text-muted mb-0">Gerencie todos os afiliados, comissões e relatórios</p>
                    <div class="mt-2">
                        <span class="badge bg-primary me-2">Plataforma liberada por QICBUSINESS</span>
                        <a href="https://t.me/+QNv-hPVLFEAxNjAx" target="_blank" class="btn btn-sm" style="background: #ff6b35; border-color: #ff6b35; color: white;">
                            <i class="fab fa-telegram me-1"></i>Acesse nosso grupo
                        </a>
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkActionsModal">
                        <i class="bi bi-gear"></i> Ações em Lote
                    </button>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estatísticas Gerais -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $affiliates_result->num_rows; ?></div>
                        <div class="stat-label">Total Afiliados</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php 
                            $active_count = 0;
                            $temp_result = $conn->query("SELECT COUNT(*) as count FROM affiliates WHERE is_active = 1");
                            $active_count = $temp_result->fetch_assoc()['count'];
                            echo $active_count;
                            ?>
                        </div>
                        <div class="stat-label">Ativos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php 
                            $total_commissions = 0;
                            $temp_result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM commissions");
                            $total_commissions = $temp_result->fetch_assoc()['total'];
                            echo 'R$ ' . number_format($total_commissions, 2, ',', '.');
                            ?>
                        </div>
                        <div class="stat-label">Total Comissões</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php 
                            $total_referrals = 0;
                            $temp_result = $conn->query("SELECT COUNT(*) as count FROM referrals");
                            $total_referrals = $temp_result->fetch_assoc()['count'];
                            echo $total_referrals;
                            ?>
                        </div>
                        <div class="stat-label">Total Indicações</div>
                    </div>
                </div>
            </div>

            <!-- Lista de Afiliados -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Afiliado</th>
                            <th>Código</th>
                            <th>Status</th>
                            <th>Estatísticas</th>
                            <th>Comissões</th>
                            <th>Saldo</th>
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
                                    <small class="text-muted"><?php echo htmlspecialchars($affiliate['email']); ?></small>
                                </div>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($affiliate['affiliate_code']); ?></code>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $affiliate['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $affiliate['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td>
                                <small>
                                    <strong><?php echo $affiliate['total_clicks']; ?></strong> cliques<br>
                                    <strong><?php echo $affiliate['total_signups']; ?></strong> cadastros<br>
                                    <strong><?php echo $affiliate['total_deposits']; ?></strong> depósitos
                                </small>
                            </td>
                            <td>
                                <small>
                                    CPA: <strong>R$ <?php echo number_format($affiliate['total_cpa'], 2, ',', '.'); ?></strong><br>
                                    RevShare: <strong>R$ <?php echo number_format($affiliate['total_revshare'], 2, ',', '.'); ?></strong>
                                </small>
                            </td>
                            <td>
                                <strong class="text-success">R$ <?php echo number_format($affiliate['affiliate_balance'], 2, ',', '.'); ?></strong>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="editAffiliate(<?php echo $affiliate['affiliate_id']; ?>)" data-bs-toggle="modal" data-bs-target="#editAffiliateModal">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-info" onclick="viewDetails(<?php echo $affiliate['affiliate_id']; ?>)" data-bs-toggle="modal" data-bs-target="#detailsModal">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="affiliate_id" value="<?php echo $affiliate['affiliate_id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $affiliate['is_active'] ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-outline-<?php echo $affiliate['is_active'] ? 'danger' : 'success'; ?>" onclick="return confirm('Confirma a alteração de status?')">
                                            <i class="bi bi-<?php echo $affiliate['is_active'] ? 'pause' : 'play'; ?>"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal de Edição de Afiliado -->
    <div class="modal fade" id="editAffiliateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Afiliado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editAffiliateForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_commission">
                        <input type="hidden" name="affiliate_id" id="edit_affiliate_id">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Comissões Configuradas (Visível ao Afiliado)</h6>
                                <div class="mb-3">
                                    <label class="form-label">Taxa RevShare (%)</label>
                                    <input type="number" class="form-control" name="revshare_rate" id="edit_revshare_rate" step="0.01" min="0" max="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-danger">Comissões Reais (Admin)</h6>
                                <div class="mb-3">
                                    <label class="form-label">Taxa RevShare Real (%)</label>
                                    <input type="number" class="form-control" name="revshare_rate_admin" id="edit_revshare_rate_admin" step="0.01" min="0" max="100">
                                </div>
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="allow_sub_earnings" id="edit_allow_sub_earnings">
                                        <label class="form-check-label" for="edit_allow_sub_earnings">
                                            Permitir ganhos de subindicados
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes do Afiliado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Conteúdo carregado via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Ações em Lote -->
    <div class="modal fade" id="bulkActionsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Dados Simulados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_simulated_data">
                        
                        <div class="mb-3">
                            <label class="form-label">Afiliado</label>
                            <select class="form-select" name="affiliate_id" required>
                                <option value="">Selecione um afiliado</option>
                                <?php 
                                $affiliates_result->data_seek(0);
                                while ($affiliate = $affiliates_result->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $affiliate['affiliate_id']; ?>">
                                    <?php echo htmlspecialchars($affiliate['name']) . ' (' . $affiliate['affiliate_code'] . ')'; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Data do Relatório</label>
                            <input type="date" class="form-control" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Cliques Simulados</label>
                                    <input type="number" class="form-control" name="simulated_clicks" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Conversões Simuladas</label>
                                    <input type="number" class="form-control" name="simulated_conversions" min="0" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Adicionar</button>
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

        function editAffiliate(affiliateId) {
            const affiliate = affiliatesData.find(a => a.affiliate_id == affiliateId);
            if (affiliate) {
                document.getElementById('edit_affiliate_id').value = affiliate.affiliate_id;
                document.getElementById("edit_revshare_rate").value = affiliate.revshare_commission_rate;
                document.getElementById("edit_revshare_rate_admin").value = affiliate.revshare_commission_rate_admin;
                document.getElementById("edit_allow_sub_earnings").checked = affiliate.allow_sub_affiliate_earnings == 1;
            }
        }

        function viewDetails(affiliateId) {
            const affiliate = affiliatesData.find(a => a.affiliate_id == affiliateId);
            if (affiliate) {
                const content = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Informações Básicas</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Nome:</strong></td><td>${affiliate.name}</td></tr>
                                <tr><td><strong>Email:</strong></td><td>${affiliate.email}</td></tr>
                                <tr><td><strong>Código:</strong></td><td><code>${affiliate.affiliate_code}</code></td></tr>
                                <tr><td><strong>Status:</strong></td><td><span class="badge ${affiliate.is_active == 1 ? 'bg-success' : 'bg-danger'}">${affiliate.is_active == 1 ? 'Ativo' : 'Inativo'}</span></td></tr>
                                <tr><td><strong>Cadastrado em:</strong></td><td>${new Date(affiliate.created_at).toLocaleDateString('pt-BR')}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Estatísticas</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Total de Cliques:</strong></td><td>${affiliate.total_clicks}</td></tr>
                                <tr><td><strong>Total de Cadastros:</strong></td><td>${affiliate.total_signups}</td></tr>
                                <tr><td><strong>Total de Depósitos:</strong></td><td>${affiliate.total_deposits}</td></tr>
                                <tr><td><strong>Total de Indicações:</strong></td><td>${affiliate.total_referrals}</td></tr>
                                <tr><td><strong>Saldo Atual:</strong></td><td><strong class="text-success">R$ ${parseFloat(affiliate.affiliate_balance).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</strong></td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6>Comissões Configuradas (Visível ao Afiliado)</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Taxa RevShare:</strong></td><td>${affiliate.revshare_commission_rate}%</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Comissões Reais (Admin)</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Taxa RevShare Real:</strong></td><td>${affiliate.revshare_commission_rate_admin}%</td></tr>
                                <tr><td><strong>Ganhos de Subindicados:</strong></td><td><span class="badge ${affiliate.allow_sub_affiliate_earnings == 1 ? 'bg-success' : 'bg-danger'}">${affiliate.allow_sub_affiliate_earnings == 1 ? 'Permitido' : 'Bloqueado'}</span></td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Comissões Ganhas</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Total RevShare:</strong></td><td>R$ ${parseFloat(affiliate.total_revshare).toLocaleString("pt-BR", {minimumFractionDigits: 2})}</td></tr>
                                <tr><td><strong>Total Geral:</strong></td><td><strong>R$ ${parseFloat(affiliate.total_revshare).toLocaleString("pt-BR", {minimumFractionDigits: 2})}</strong></td></tr>
                            </table>
                        </div>
                    </div>
                `;
                document.getElementById('detailsContent').innerHTML = content;
            }
        }
    </script>
</body>
</html>


