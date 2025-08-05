<?php
session_start();
if (!isset($_SESSION["usuario_id"]) || $_SESSION["is_admin"] != 1) {
    header("Location: ../login.php");
    exit();
}

require_once '../includes/db.php';

$result = $conn->query("SELECT d.*, u.name as usuario_nome FROM deposits d LEFT JOIN users u ON d.user_id = u.id ORDER BY d.id DESC");

// Adiciona tratamento de erro para a consulta principal
if ($result === false) {
    // Log do erro para depuração (em um ambiente de produção, você pode querer logar em um arquivo)
    error_log("Erro na consulta SQL para deposits: " . $conn->error);
    // Define $result como um objeto vazio para evitar o erro de num_rows
    $result = new stdClass();
    $result->num_rows = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Depósitos - Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --light-bg: #f8fafc;
            --white: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --border-radius: 8px;
            --border-radius-lg: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .main-container {
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            z-index: 1;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-text {
            flex: 1;
            min-width: 0;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 400;
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
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            font-size: 0.9rem;
            min-height: 44px;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .btn-voltar:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.success { background-color: rgba(5, 150, 105, 0.1); color: var(--success-color); }
        .stat-icon.warning { background-color: rgba(217, 119, 6, 0.1); color: var(--warning-color); }
        .stat-icon.info { background-color: rgba(37, 99, 235, 0.1); color: var(--primary-color); }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .table-container {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(to right, #f8fafc, #f1f5f9);
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .table-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .custom-table {
            margin: 0;
            border: none;
        }

        .custom-table thead th {
            background-color: #f8fafc;
            border: none;
            border-bottom: 2px solid var(--border-color);
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .custom-table tbody td {
            padding: 1rem 1.5rem;
            border: none;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 0.875rem;
        }

        .custom-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .custom-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .status-badge.pendente {
            background-color: rgba(217, 119, 6, 0.1);
            color: var(--warning-color);
        }

        .status-badge.pago {
            background-color: rgba(5, 150, 105, 0.1);
            color: var(--success-color);
        }

        .status-badge.aprovado {
            background-color: rgba(5, 150, 105, 0.1);
            color: var(--success-color);
        }

        .status-badge.rejeitado {
            background-color: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.875rem;
        }

        .user-id {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .amount {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--success-color);
        }

        .date-info {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Responsividade para mobile */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .header-content {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .btn-voltar {
                align-self: flex-end;
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
                min-height: 40px;
            }

            .btn-voltar .btn-text {
                display: none;
            }

            .stats-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .custom-table {
                font-size: 0.8rem;
            }

            .custom-table thead th,
            .custom-table tbody td {
                padding: 0.75rem 0.5rem;
            }

            .user-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .table-header {
                padding: 1rem;
            }

            .table-title {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            .page-header {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.25rem;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .page-subtitle {
                font-size: 0.9rem;
            }

            .btn-voltar {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
                min-height: 36px;
            }

            .stats-container {
                gap: 0.75rem;
            }

            .stat-card {
                padding: 0.75rem;
            }

            .stat-value {
                font-size: 1.25rem;
            }

            .stat-label {
                font-size: 0.8rem;
            }

            .custom-table thead th,
            .custom-table tbody td {
                padding: 0.5rem 0.25rem;
                font-size: 0.75rem;
            }

            .user-avatar {
                width: 28px;
                height: 28px;
                font-size: 0.7rem;
            }

            .status-badge {
                font-size: 0.65rem;
                padding: 0.25rem 0.5rem;
            }
        }

        /* Melhorias específicas para touch devices */
        @media (hover: none) and (pointer: coarse) {
            .btn-voltar {
                min-height: 48px;
                padding: 0.75rem 1rem;
            }

            .stat-card:hover {
                transform: none;
            }

            .btn-voltar:hover {
                transform: none;
            }

            .custom-table tbody tr:hover {
                background-color: transparent;
            }
        }

        /* Correções para landscape em mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .page-header {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.25rem;
            }

            .stats-container {
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem;
            }

            .stat-card {
                padding: 0.75rem;
            }

            .stat-value {
                font-size: 1.25rem;
            }
        }

        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body>
    <div class="container-fluid main-container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="header-content">
                        <div class="header-text">
                            <h1 class="page-title">
                                <i class="fas fa-coins"></i>
                                Gestão de Depósitos
                            </h1>
                            <p class="page-subtitle">Monitore e gerencie todos os depósitos realizados na plataforma</p>
                        </div>
                        <div>
                            <a href="javascript:history.back()" class="btn-voltar" title="Voltar à página anterior">
                                <i class="fas fa-arrow-left"></i>
                                <span class="btn-text d-none d-md-inline">Voltar</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <?php
                $stats_query = $conn->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) as aprovados,
                        SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
                        SUM(CASE WHEN status = 'pago' THEN amount ELSE 0 END) as valor_total
                    FROM deposits
                ");
                
                // Adiciona tratamento de erro para a consulta de estatísticas
                if ($stats_query === false) {
                    error_log("Erro na consulta SQL para estatísticas: " . $conn->error);
                    $stats = [
                        'total' => 0,
                        'aprovados' => 0,
                        'pendentes' => 0,
                        'valor_total' => 0
                    ];
                } else {
                    $stats = $stats_query->fetch_assoc();
                }
                ?>

                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?= number_format($stats['valor_total'], 2, ',', '.') ?></div>
                        <div class="stat-label">Total Aprovado (R$)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-value"><?= $stats['total'] ?></div>
                        <div class="stat-label">Total de Depósitos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-value"><?= $stats['pendentes'] ?></div>
                        <div class="stat-label">Aguardando Aprovação</div>
                    </div>
                </div>

                <!-- Deposits Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h2 class="table-title">Lista de Depósitos</h2>
                        <p class="table-description">Histórico completo de todas as transações de depósito</p>
                    </div>
                    
                    <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table custom-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuário</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th class="d-none d-md-table-cell">Data</th>
                                    <th class="d-none d-lg-table-cell">Payment ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold">#<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?= strtoupper(substr($row['usuario_nome'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <div class="user-details">
                                                <div class="user-name"><?= htmlspecialchars($row['usuario_nome'] ?? 'Usuário não encontrado') ?></div>
                                                <div class="user-id">ID: <?= $row['user_id'] ?></div>
                                                <div class="d-md-none">
                                                    <small class="date-info">
                                                        <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="amount">R$ <?= number_format($row['amount'], 2, ',', '.') ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= strtolower($row['status']) ?>">
                                            <?php
                                            $status_icons = [
                                                'pendente' => 'fas fa-clock',
                                                'pago' => 'fas fa-check',
                                                'aprovado' => 'fas fa-check',
                                                'rejeitado' => 'fas fa-times'
                                            ];
                                            $icon = $status_icons[strtolower($row['status'])] ?? 'fas fa-question';
                                            
                                            $status_labels = [
                                                'pago' => 'Pago',
                                                'pendente' => 'Pendente',
                                                'aprovado' => 'Aprovado',
                                                'rejeitado' => 'Rejeitado'
                                            ];
                                            $status_label = $status_labels[strtolower($row['status'])] ?? ucfirst($row['status']);
                                            ?>
                                            <i class="<?= $icon ?>"></i>
                                            <?= $status_label ?>
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <div class="date-info">
                                            <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td class="d-none d-lg-table-cell">
                                        <div class="text-muted" style="font-size: 0.75rem; font-family: monospace;">
                                            <?= $row['payment_id'] ? htmlspecialchars(substr($row['payment_id'], 0, 20) . '...') : '-' ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Nenhum depósito encontrado</h3>
                        <p>Ainda não há depósitos registrados no sistema.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎯 Gestão de Depósitos carregada com sucesso!');
            
            // Atalho de teclado Alt + Backspace para voltar
            document.addEventListener('keydown', function(e) {
                if (e.altKey && e.key === 'Backspace') {
                    e.preventDefault();
                    history.back();
                }
            });

            // Animate stat cards on load
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add loading state simulation for better UX
            const tableRows = document.querySelectorAll('.custom-table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-10px)';
                setTimeout(() => {
                    row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, 300 + (index * 50));
            });
        });
    </script>
</body>
</html>
