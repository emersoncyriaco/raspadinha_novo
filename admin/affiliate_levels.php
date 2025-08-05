<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/affiliate_functions.php';

// Alteração aqui: de 'user_id' para 'usuario_id' para consistência
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

// Verifica se é admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
// Alteração aqui: de 'user_id' para 'usuario_id' para consistência
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !$user['is_admin']) {
    header("Location: ../login.php");
    exit();
}

// Buscar estrutura de níveis
$levels_query = "
    SELECT 
        r.level,
        COUNT(*) as total_referrals,
        COUNT(DISTINCT r.referrer_id) as unique_referrers,
        COALESCE(SUM(c.amount), 0) as total_commissions
    FROM referrals r
    LEFT JOIN affiliates a ON r.referrer_id = a.user_id
    LEFT JOIN commissions c ON a.id = c.affiliate_id AND c.level = r.level
    GROUP BY r.level
    ORDER BY r.level
";

$levels_result = $conn->query($levels_query);

// Buscar top afiliados por nível
$top_affiliates_query = "
    SELECT 
        r.level,
        u.name,
        u.email,
        a.affiliate_code,
        COUNT(r.referred_id) as referrals_count,
        COALESCE(SUM(c.amount), 0) as level_commissions
    FROM referrals r
    JOIN users u ON r.referrer_id = u.id
    JOIN affiliates a ON r.referrer_id = a.user_id
    LEFT JOIN commissions c ON a.id = c.affiliate_id AND c.level = r.level
    GROUP BY r.level, r.referrer_id
    HAVING referrals_count > 0
    ORDER BY r.level, level_commissions DESC
";

$top_affiliates_result = $conn->query($top_affiliates_query);

// Organizar dados por nível
$top_by_level = [];
while ($row = $top_affiliates_result->fetch_assoc()) {
    $top_by_level[$row['level']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Níveis de Afiliados - Admin</title>
    
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

        .level-card {
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            background: white;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .level-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .level-card.level-1::before {
            background: linear-gradient(90deg, #10b981, #059669);
        }

        .level-card.level-2::before {
            background: linear-gradient(90deg, #3b82f6, #2563eb);
        }

        .level-card.level-3::before {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .level-card.level-4::before {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .level-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .level-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .level-number {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }

        .level-1 .level-number {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .level-2 .level-number {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .level-3 .level-number {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .level-4 .level-number {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .level-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.75rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--secondary-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .top-affiliates {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.5rem;
        }

        .affiliate-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .affiliate-info {
            flex: 1;
        }

        .affiliate-name {
            font-weight: 600;
            color: var(--dark-color);
        }

        .affiliate-code {
            font-size: 0.875rem;
            color: var(--secondary-color);
            font-family: monospace;
        }

        .affiliate-stats {
            text-align: right;
        }

        .commission-amount {
            font-weight: 700;
            color: var(--success-color);
        }

        .referrals-count {
            font-size: 0.875rem;
            color: var(--secondary-color);
        }

        .chart-container {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            margin-top: 2rem;
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
                <a href="affiliate_levels.php" class="nav-link active">
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
                    <h2 class="mb-1">Estrutura de Níveis de Afiliados</h2>
                    <p class="text-muted mb-0">Visualize a hierarquia de indicações e comissões por nível</p>
                </div>
                <div>
                    <span class="badge bg-info fs-6">Sistema de 4 Níveis</span>
                </div>
            </div>

            <!-- Resumo Geral -->
            <div class="row mb-4">
                <?php 
                $total_referrals = 0;
                $total_commissions = 0;
                // Verifica se $levels_result é um objeto mysqli_result antes de chamar data_seek
                if ($levels_result instanceof mysqli_result) {
                    $levels_result->data_seek(0);
                    while ($level = $levels_result->fetch_assoc()) {
                        $total_referrals += $level['total_referrals'];
                        $total_commissions += $level['total_commissions'];
                    }
                    // Volta o ponteiro para o início para o próximo loop
                    $levels_result->data_seek(0);
                }
                ?>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($total_referrals); ?></div>
                        <div class="stat-label">Total de Indicações</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-value">R$ <?php echo number_format($total_commissions, 2, ',', '.'); ?></div>
                        <div class="stat-label">Total de Comissões</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-value">4</div>
                        <div class="stat-label">Níveis Ativos</div>
                    </div>
                </div>
            </div>

            <!-- Níveis Detalhados -->
            <?php 
            // Verifica novamente se $levels_result é um objeto mysqli_result antes de iterar
            if ($levels_result instanceof mysqli_result) {
                while ($level = $levels_result->fetch_assoc()): 
                    $level_num = $level['level'];
                    $level_class = "level-" . $level_num;
            ?>
            <div class="level-card <?php echo $level_class; ?>">
                <div class="level-header">
                    <div class="level-number"><?php echo $level_num; ?></div>
                    <div>
                        <h4 class="mb-1">Nível <?php echo $level_num; ?></h4>
                        <p class="text-muted mb-0">
                            <?php 
                            switch($level_num) {
                                case 1: echo "Indicações diretas"; break;
                                case 2: echo "Indicações de 2º nível"; break;
                                case 3: echo "Indicações de 3º nível"; break;
                                case 4: echo "Indicações de 4º nível"; break;
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <div class="level-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($level['total_referrals']); ?></div>
                        <div class="stat-label">Indicações</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($level['unique_referrers']); ?></div>
                        <div class="stat-label">Afiliados Ativos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">R$ <?php echo number_format($level['total_commissions'], 2, ',', '.'); ?></div>
                        <div class="stat-label">Comissões Pagas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">
                            <?php 
                            $avg_commission = $level['unique_referrers'] > 0 ? $level['total_commissions'] / $level['unique_referrers'] : 0;
                            echo 'R$ ' . number_format($avg_commission, 2, ',', '.'); 
                            ?>
                        </div>
                        <div class="stat-label">Média por Afiliado</div>
                    </div>
                </div>

                <?php if (isset($top_by_level[$level_num]) && count($top_by_level[$level_num]) > 0): ?>
                <div class="top-affiliates">
                    <h6 class="mb-3">Top Afiliados do Nível <?php echo $level_num; ?></h6>
                    <?php foreach (array_slice($top_by_level[$level_num], 0, 5) as $affiliate): ?>
                    <div class="affiliate-item">
                        <div class="affiliate-info">
                            <div class="affiliate-name"><?php echo htmlspecialchars($affiliate['name']); ?></div>
                            <div class="affiliate-code"><?php echo htmlspecialchars($affiliate['affiliate_code']); ?></div>
                        </div>
                        <div class="affiliate-stats">
                            <div class="commission-amount">R$ <?php echo number_format($affiliate['level_commissions'], 2, ',', '.'); ?></div>
                            <div class="referrals-count"><?php echo $affiliate['referrals_count']; ?> indicações</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endwhile; 
            }
            ?>

            <!-- Gráfico de Distribuição -->
            <div class="chart-container">
                <h4 class="mb-3">Distribuição de Comissões por Nível</h4>
                <canvas id="levelsChart" width="400" height="200"></canvas>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Dados para o gráfico
        const levelsData = <?php 
        // Verifica se $levels_result é um objeto mysqli_result antes de chamar data_seek
        if ($levels_result instanceof mysqli_result) {
            $levels_result->data_seek(0);
            $chart_data = [];
            while ($level = $levels_result->fetch_assoc()) {
                $chart_data[] = [
                    'level' => 'Nível ' . $level['level'],
                    'commissions' => floatval($level['total_commissions']),
                    'referrals' => intval($level['total_referrals'])
                ];
            }
            echo json_encode($chart_data);
        } else {
            echo '[]'; // Retorna um array vazio se a consulta falhar
        }
        ?>;

        // Configuração do gráfico
        const ctx = document.getElementById('levelsChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: levelsData.map(item => item.level),
                datasets: [{
                    label: 'Comissões (R$)',
                    data: levelsData.map(item => item.commissions),
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgba(16, 185, 129, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                elements: {
                    bar: {
                        borderSkipped: false,
                    }
                }
            }
        });
    </script>
</body>
</html>


