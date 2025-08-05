<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['is_admin'] != 1) {
  die("Acesso negado");
}

// Parâmetros de filtro e paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$ganhou_filter = isset($_GET['ganhou']) ? $_GET['ganhou'] : '';

// Construir query com filtros
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "u.name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(j.criado_em) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(j.criado_em) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if ($ganhou_filter !== '') {
    $where_conditions[] = "j.ganhou = ?";
    $params[] = (int)$ganhou_filter;
    $types .= 'i';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query principal
$query = "
  SELECT j.*, u.name 
  FROM raspadinha_jogadas j 
  JOIN users u ON j.user_id = u.id 
  $where_clause
  ORDER BY j.criado_em DESC
  LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

// Contar total de registros
$count_query = "
  SELECT COUNT(*) as total
  FROM raspadinha_jogadas j 
  JOIN users u ON j.user_id = u.id 
  $where_clause
";

if (!empty($where_conditions)) {
    $count_stmt = $conn->prepare($count_query);
    $count_types = substr($types, 0, -2); // Remove os últimos 'ii' do limit/offset
    $count_params = array_slice($params, 0, -2); // Remove limit/offset
    if (!empty($count_params)) {
        $count_stmt->bind_param($count_types, ...$count_params);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_records = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $limit);

// Estatísticas
$stats_query = "
  SELECT 
    COUNT(*) as total_jogadas,
    SUM(aposta) as total_apostado,
    SUM(valor_premio) as total_premios,
    SUM(CASE WHEN ganhou = 1 THEN 1 ELSE 0 END) as total_vitorias
  FROM raspadinha_jogadas j 
  JOIN users u ON j.user_id = u.id 
  $where_clause
";

if (!empty($where_conditions)) {
    $stats_stmt = $conn->prepare($stats_query);
    if (!empty($count_params)) {
        $stats_stmt->bind_param($count_types, ...$count_params);
    }
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();
} else {
    $stats = $conn->query($stats_query)->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Jogadas - Admin Panel</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
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
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .page-title {
            color: var(--dark-color);
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-subtitle {
            color: var(--secondary-color);
            margin: 0.5rem 0 0 0;
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
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
            width: 50px;
            height: 50px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 1.75rem;
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

        .filters-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 2rem;
        }

        .table-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }

        .table-responsive {
            border-radius: 0.75rem;
            overflow: hidden;
        }

        .table {
            margin: 0;
            background: white;
        }

        .table thead th {
            background: var(--primary-color);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem 0.75rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 1rem 0.75rem;
            border-color: #e2e8f0;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: rgba(37, 99, 235, 0.05);
        }

        .badge-success {
            background: var(--success-color) !important;
        }

        .badge-danger {
            background: var(--danger-color) !important;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }

        .pagination .page-link {
            color: var(--primary-color);
            border-color: #e2e8f0;
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        /* Mobile Cards */
        .mobile-cards {
            display: none;
        }

        .game-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .game-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }

        .game-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .game-id {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .game-date {
            color: var(--secondary-color);
            font-size: 0.875rem;
        }

        .game-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            color: var(--secondary-color);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .info-value {
            color: var(--dark-color);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .symbols-display {
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 0.75rem;
            font-family: monospace;
            font-size: 1.1rem;
            text-align: center;
            margin-top: 1rem;
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
                width: 260px;
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
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
            
            .filters-card {
                padding: 1.25rem;
            }
            
            .table-card {
                padding: 1rem;
            }
            
            .table-responsive {
                display: none;
            }
            
            .mobile-cards {
                display: block;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 0.75rem;
                padding-top: calc(var(--header-height) + 0.75rem);
            }
            
            .page-header {
                padding: 1rem;
            }
            
            .page-title {
                font-size: 1.25rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .game-info {
                grid-template-columns: 1fr;
                gap: 0.75rem;
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
            <i class="bi bi-graph-up me-2"></i>
            Relatórios
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
            <a href="dashboard.php" class="sidebar-brand">
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
            
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header fade-in">
            <h1 class="page-title">
                <i class="bi bi-graph-up"></i>
                Relatório de Jogadas
            </h1>
            <p class="page-subtitle">Análise detalhada de todas as jogadas realizadas no sistema</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <i class="bi bi-dice-6"></i>
                </div>
                <h3 class="stat-value"><?= number_format($stats['total_jogadas'], 0, ',', '.') ?></h3>
                <p class="stat-label">Total de Jogadas</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <h3 class="stat-value">R$ <?= number_format($stats['total_apostado'], 2, ',', '.') ?></h3>
                <p class="stat-label">Total Apostado</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                    <i class="bi bi-trophy"></i>
                </div>
                <h3 class="stat-value">R$ <?= number_format($stats['total_premios'], 2, ',', '.') ?></h3>
                <p class="stat-label">Total em Prêmios</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                    <i class="bi bi-percent"></i>
                </div>
                <h3 class="stat-value"><?= $stats['total_jogadas'] > 0 ? number_format(($stats['total_vitorias'] / $stats['total_jogadas']) * 100, 1) : 0 ?>%</h3>
                <p class="stat-label">Taxa de Vitória</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card fade-in">
            <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filtros</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Buscar Usuário</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nome do usuário">
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Data Inicial</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Data Final</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-2">
                    <label for="ganhou" class="form-label">Resultado</label>
                    <select class="form-select" id="ganhou" name="ganhou">
                        <option value="">Todos</option>
                        <option value="1" <?= $ganhou_filter === '1' ? 'selected' : '' ?>>Ganhou</option>
                        <option value="0" <?= $ganhou_filter === '0' ? 'selected' : '' ?>>Perdeu</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i>Filtrar
                    </button>
                    <a href="?" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise me-1"></i>Limpar
                    </a>
                </div>
            </form>
        </div>

        <!-- Table for Desktop -->
        <div class="table-card fade-in">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-table me-2"></i>Jogadas (<?= number_format($total_records, 0, ',', '.') ?> registros)</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" onclick="exportData()">
                        <i class="bi bi-download me-1"></i>Exportar
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" id="jogadasTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Saldo Antes</th>
                            <th>Saldo Depois</th>
                            <th>Aposta</th>
                            <th>Resultado</th>
                            <th>Prêmio</th>
                            <th>Data/Hora</th>
                            <th>Símbolos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $row['id'] ?></strong></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td>R$ <?= number_format($row['saldo_antes'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($row['saldo_depois'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($row['aposta'], 2, ',', '.') ?></td>
                                <td>
                                    <?php if($row['ganhou']): ?>
                                        <span class="badge badge-success">
                                            <i class="bi bi-check-circle me-1"></i>Ganhou
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">
                                            <i class="bi bi-x-circle me-1"></i>Perdeu
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>R$ <?= number_format($row['valor_premio'], 2, ',', '.') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['criado_em'])) ?></td>
                                <td><code><?= htmlspecialchars($row['simbolos']) ?></code></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards -->
            <div class="mobile-cards">
                <?php 
                // Reset result for mobile cards
                $stmt->execute();
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()): 
                ?>
                    <div class="game-card">
                        <div class="game-card-header">
                            <div class="game-id">#<?= $row['id'] ?></div>
                            <div class="game-date"><?= date('d/m/Y H:i', strtotime($row['criado_em'])) ?></div>
                        </div>
                        
                        <div class="game-info">
                            <div class="info-item">
                                <div class="info-label">Usuário</div>
                                <div class="info-value"><?= htmlspecialchars($row['name']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Resultado</div>
                                <div class="info-value">
                                    <?php if($row['ganhou']): ?>
                                        <span class="badge badge-success">
                                            <i class="bi bi-check-circle me-1"></i>Ganhou
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">
                                            <i class="bi bi-x-circle me-1"></i>Perdeu
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Aposta</div>
                                <div class="info-value">R$ <?= number_format($row['aposta'], 2, ',', '.') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Prêmio</div>
                                <div class="info-value">R$ <?= number_format($row['valor_premio'], 2, ',', '.') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Saldo Antes</div>
                                <div class="info-value">R$ <?= number_format($row['saldo_antes'], 2, ',', '.') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Saldo Depois</div>
                                <div class="info-value">R$ <?= number_format($row['saldo_depois'], 2, ',', '.') ?></div>
                            </div>
                        </div>
                        
                        <div class="symbols-display">
                            <strong>Símbolos:</strong> <?= htmlspecialchars($row['simbolos']) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <nav aria-label="Navegação de páginas" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page-1 ?>&<?= http_build_query($_GET) ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($_GET) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page+1 ?>&<?= http_build_query($_GET) ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
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

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });

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

        // Export functionality
        function exportData() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = '?' + params.toString();
        }

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
                if (swipeDistance > swipeThreshold && touchStartX < 50) {
                    if (!sidebar.classList.contains('show')) {
                        toggleSidebar();
                    }
                }
                else if (swipeDistance < -swipeThreshold && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }
        }
    </script>
</body>
</html>

