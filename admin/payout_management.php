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

$message = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_payout':
                $payout_id = (int)$_POST['payout_id'];
                $stmt = $conn->prepare("UPDATE payouts SET status = 'paid', paid_date = NOW() WHERE id = ?");
                $stmt->bind_param("i", $payout_id);
                if ($stmt->execute()) {
                    $message = "Pagamento aprovado com sucesso!";
                } else {
                    $message = "Erro ao aprovar pagamento.";
                }
                break;
                
            case 'reject_payout':
                $payout_id = (int)$_POST['payout_id'];
                $stmt = $conn->prepare("UPDATE payouts SET status = 'rejected' WHERE id = ?");
                $stmt->bind_param("i", $payout_id);
                if ($stmt->execute()) {
                    $message = "Pagamento rejeitado.";
                } else {
                    $message = "Erro ao rejeitar pagamento.";
                }
                break;
                
            case 'bulk_approve':
                if (isset($_POST['selected_payouts']) && is_array($_POST['selected_payouts'])) {
                    $ids = implode(',', array_map('intval', $_POST['selected_payouts']));
                    $stmt = $conn->prepare("UPDATE payouts SET status = 'paid', paid_date = NOW() WHERE id IN ($ids)");
                    if ($stmt->execute()) {
                        $count = count($_POST['selected_payouts']);
                        $message = "$count pagamentos aprovados em lote!";
                    }
                }
                break;
        }
    }
}

// Buscar estatísticas de pagamentos
$stats_query = "
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_requests,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount
    FROM payouts
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Buscar solicitações de pagamento
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = '';
if ($filter == 'pending') {
    $where_clause = "WHERE p.status = 'pending'";
} elseif ($filter == 'paid') {
    $where_clause = "WHERE p.status = 'paid'";
} elseif ($filter == 'rejected') {
    $where_clause = "WHERE p.status = 'rejected'";
}

$payouts_query = "
    SELECT p.*, u.name as affiliate_name, u.email as affiliate_email, a.affiliate_code
    FROM payouts p
    JOIN affiliates a ON p.affiliate_id = a.id
    JOIN users u ON a.user_id = u.id
    $where_clause
    ORDER BY p.request_date DESC
    LIMIT 50
";
$payouts_result = $conn->query($payouts_query);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pagamentos - Admin</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
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

        /* Estilo específico para o botão voltar */
        .btn-voltar {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            font-size: 0.9rem;
            min-height: 40px;
            white-space: nowrap;
        }

        .btn-voltar:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
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

        .main-container {
            padding: 1rem;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .table-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .table-dark {
            background: transparent;
        }

        .table-dark th {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-size: 0.9rem;
            padding: 0.75rem 0.5rem;
        }

        .table-dark td {
            border-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.85rem;
            padding: 0.75rem 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            border: none;
        }

        .filter-tabs {
            margin-bottom: 1.5rem;
        }

        .filter-tabs .nav-link {
            color: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            margin-right: 0.5rem;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }

        .filter-tabs .nav-link.active {
            color: white;
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .bulk-actions {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        /* Responsividade para mobile */
        @media (max-width: 768px) {
            .main-container {
                padding: 0.5rem;
            }

            .stats-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .stats-value {
                font-size: 1.5rem;
            }

            .stats-label {
                font-size: 0.8rem;
            }

            .table-container {
                padding: 1rem;
            }

            .table-responsive {
                font-size: 0.8rem;
            }

            .btn-voltar {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
                min-height: 36px;
            }

            .btn-voltar .btn-text {
                display: none;
            }

            .filter-tabs .nav-link {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
                margin-right: 0.25rem;
                margin-bottom: 0.25rem;
            }

            .bulk-actions {
                padding: 0.75rem;
            }

            .bulk-actions .d-flex {
                flex-direction: column;
                gap: 0.5rem !important;
            }

            .bulk-actions .btn {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }

            .table-dark th,
            .table-dark td {
                font-size: 0.75rem;
                padding: 0.5rem 0.25rem;
            }

            .btn-group .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            .badge {
                font-size: 0.7rem;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 0.9rem;
            }

            .stats-value {
                font-size: 1.3rem;
            }

            .stats-label {
                font-size: 0.75rem;
            }

            .table-container h4 {
                font-size: 1.1rem;
            }

            .btn-voltar {
                padding: 0.3rem 0.6rem;
                font-size: 0.75rem;
                min-height: 32px;
            }
        }

        /* Melhorias específicas para touch devices */
        @media (hover: none) and (pointer: coarse) {
            .btn {
                min-height: 44px;
                padding: 0.75rem 1rem;
            }

            .btn-sm {
                min-height: 36px;
                padding: 0.5rem 0.75rem;
            }

            .stats-card:hover {
                transform: none;
            }

            .btn-voltar:hover {
                transform: none;
            }

            .table-dark th,
            .table-dark td {
                padding: 0.75rem 0.5rem;
            }
        }

        /* Correções para landscape em mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .main-container {
                padding: 0.5rem;
            }

            .stats-card {
                padding: 0.75rem;
                margin-bottom: 0.75rem;
            }

            .stats-value {
                font-size: 1.2rem;
            }

            .table-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="index.php">
                <i class="bi bi-shield-lock"></i> 
                <span class="d-none d-md-inline"></span>
            </a>
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center gap-2">
                <a href="javascript:history.back()" class="btn-voltar" title="Voltar à página anterior">
                    <i class="bi bi-arrow-left"></i>
                    <span class="btn-text d-none d-md-inline">Voltar</span>
               
                   
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid main-container">
        <!-- Mensagens -->
        <?php if ($message): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-value text-info"><?php echo number_format($stats['total_requests']); ?></div>
                    <div class="stats-label">Total de Solicitações</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-value text-warning"><?php echo number_format($stats['pending_requests']); ?></div>
                    <div class="stats-label">Pendentes</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-value text-success"><?php echo number_format($stats['paid_requests']); ?></div>
                    <div class="stats-label">Pagos</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card">
                    <div class="stats-value text-warning">R$ <?php echo number_format($stats['pending_amount'], 2, ',', '.'); ?></div>
                    <div class="stats-label">Valor Pendente</div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filter-tabs">
            <ul class="nav nav-pills flex-wrap">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter == 'all' ? 'active' : ''; ?>" href="?filter=all">
                        <i class="bi bi-list"></i> <span class="d-none d-sm-inline">Todos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter == 'pending' ? 'active' : ''; ?>" href="?filter=pending">
                        <i class="bi bi-clock"></i> <span class="d-none d-sm-inline">Pendentes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter == 'paid' ? 'active' : ''; ?>" href="?filter=paid">
                        <i class="bi bi-check-circle"></i> <span class="d-none d-sm-inline">Pagos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter == 'rejected' ? 'active' : ''; ?>" href="?filter=rejected">
                        <i class="bi bi-x-circle"></i> <span class="d-none d-sm-inline">Rejeitados</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Ações em Lote -->
        <div class="bulk-actions">
            <form method="POST" id="bulkForm">
                <input type="hidden" name="action" value="bulk_approve">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="selectAll()">
                        <i class="bi bi-check-all"></i> <span class="d-none d-sm-inline">Selecionar Todos</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="deselectAll()">
                        <i class="bi bi-x"></i> <span class="d-none d-sm-inline">Desmarcar Todos</span>
                    </button>
                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Aprovar pagamentos selecionados?')">
                        <i class="bi bi-check-circle"></i> <span class="d-none d-sm-inline">Aprovar Selecionados</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabela de Pagamentos -->
        <div class="table-container">
            <h4 class="mb-3">
                <i class="bi bi-cash-stack"></i> Solicitações de Pagamento
            </h4>
            
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll()">
                            </th>
                            <th>Afiliado</th>
                            <th class="d-none d-md-table-cell">Código</th>
                            <th>Valor</th>
                            <th class="d-none d-lg-table-cell">Data Solicitação</th>
                            <th>Status</th>
                            <th class="d-none d-lg-table-cell">Data Pagamento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payouts_result->num_rows > 0): ?>
                            <?php while ($payout = $payouts_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if ($payout['status'] == 'pending'): ?>
                                        <input type="checkbox" name="selected_payouts[]" value="<?php echo $payout['id']; ?>" form="bulkForm">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($payout['affiliate_name']); ?></strong>
                                        <br>
                                        <small class="text-muted d-none d-md-block"><?php echo htmlspecialchars($payout['affiliate_email']); ?></small>
                                        <small class="text-muted d-md-none">
                                            <?php echo htmlspecialchars($payout['affiliate_code']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <code><?php echo htmlspecialchars($payout['affiliate_code']); ?></code>
                                </td>
                                <td>
                                    <strong class="text-success">R$ <?php echo number_format($payout['amount'], 2, ',', '.'); ?></strong>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php echo date('d/m/Y H:i', strtotime($payout['request_date'])); ?>
                                </td>
                                <td>
                                    <span class="badge <?php 
                                        echo $payout['status'] == 'paid' ? 'bg-success' : 
                                             ($payout['status'] == 'pending' ? 'bg-warning' : 'bg-danger'); 
                                    ?>">
                                        <?php 
                                        echo $payout['status'] == 'paid' ? 'Pago' : 
                                             ($payout['status'] == 'pending' ? 'Pendente' : 'Rejeitado'); 
                                        ?>
                                    </span>
                                </td>
                                <td class="d-none d-lg-table-cell">
                                    <?php 
                                    echo isset($payout['paid_date']) && $payout['paid_date'] ? date('d/m/Y H:i', strtotime($payout['paid_date'])) : '-'; 
                                    ?>
                                </td>
                                <td>
                                    <?php if ($payout['status'] == 'pending'): ?>
                                        <div class="btn-group" role="group">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="approve_payout">
                                                <input type="hidden" name="payout_id" value="<?php echo $payout['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" 
                                                        onclick="return confirm('Aprovar este pagamento?')"
                                                        title="Aprovar">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="reject_payout">
                                                <input type="hidden" name="payout_id" value="<?php echo $payout['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Rejeitar este pagamento?')"
                                                        title="Rejeitar">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-inbox"></i> Nenhuma solicitação encontrada
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Atalho de teclado Alt + Backspace para voltar
            document.addEventListener('keydown', function(e) {
                if (e.altKey && e.key === 'Backspace') {
                    e.preventDefault();
                    history.back();
                }
            });

            console.log('🎯 Gestão de Pagamentos carregada com sucesso!');
        });

        function selectAll() {
            const checkboxes = document.querySelectorAll('input[name="selected_payouts[]"]');
            checkboxes.forEach(cb => cb.checked = true);
            document.getElementById('selectAllCheckbox').checked = true;
        }

        function deselectAll() {
            const checkboxes = document.querySelectorAll('input[name="selected_payouts[]"]');
            checkboxes.forEach(cb => cb.checked = false);
            document.getElementById('selectAllCheckbox').checked = false;
        }

        function toggleAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkboxes = document.querySelectorAll('input[name="selected_payouts[]"]');
            checkboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
        }
    </script>
</body>
</html>

