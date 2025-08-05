<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';

// Verificar se é admin
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !$user['is_admin']) {
    header("Location: ../perfil.php");
    exit;
}

// Processar ações de administração
$mensagem = ''; // Inicializa a variável mensagem para evitar warnings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $saqueId = intval($_POST['saque_id']);
    $acao = $_POST['acao'];
    
    if ($acao === 'aprovar') {
        $stmt = $conn->prepare("UPDATE saques_pix SET status = 'concluido', data_processamento = NOW() WHERE id = ?");
        $stmt->bind_param("i", $saqueId);
        $stmt->execute();
        $mensagem = "Saque aprovado com sucesso!";
    } elseif ($acao === 'cancelar') {
        // Cancelar saque e devolver o valor ao saldo
        $conn->begin_transaction();
        try {
            // Buscar dados do saque
            $stmt = $conn->prepare("SELECT user_id, valor FROM saques_pix WHERE id = ? AND status = 'pendente'");
            $stmt->bind_param("i", $saqueId);
            $stmt->execute();
            $result = $stmt->get_result();
            $saque = $result->fetch_assoc();
            
            if ($saque) {
                // Devolver valor ao saldo
                $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt->bind_param("di", $saque['valor'], $saque['user_id']);
                $stmt->execute();
                
                // Atualizar status do saque
                $stmt = $conn->prepare("UPDATE saques_pix SET status = 'cancelado', data_processamento = NOW() WHERE id = ?");
                $stmt->bind_param("i", $saqueId);
                $stmt->execute();
                
                $conn->commit();
                $mensagem = "Saque cancelado e valor devolvido ao usuário!";
            } else {
                $mensagem = "Erro: Saque não encontrado ou não está pendente.";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $mensagem = "Erro ao cancelar saque: " . $e->getMessage();
        }
    }
}

// Buscar todos os saques
$query = "
    SELECT s.*, u.name as usuario_nome, u.email as usuario_email 
    FROM saques_pix s 
    JOIN users u ON s.user_id = u.id 
    ORDER BY s.data_solicitacao DESC
";
$result = $conn->query($query);
$saques = $result->fetch_all(MYSQLI_ASSOC);

// Estatísticas
$stats = [
    'total' => 0,
    'pendente' => 0, // Chave 'pendente' inicializada
    'concluido' => 0, // Chave 'concluido' inicializada
    'cancelado' => 0, // Chave 'cancelado' inicializada
    'processando' => 0, // Adicionado caso você tenha este status no BD
    'valor_total' => 0
];

foreach ($saques as $saque) {
    $stats['total']++;
    
    // Verifica se a chave de status existe antes de incrementar
    if (isset($stats[$saque['status']])) { // Linha 86 corrigida
        $stats[$saque['status']]++;
    } else {
        // Se um novo status aparecer, você pode inicializá-lo aqui (opcional)
        $stats[$saque['status']] = 1;
        error_log("Novo status de saque encontrado: " . $saque['status']); // Para depuração
    }

    if ($saque['status'] !== 'cancelado') {
        $stats['valor_total'] += $saque['valor'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Saques Pix - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .status-pendente { @apply bg-yellow-100 text-yellow-800; }
        .status-processando { @apply bg-blue-100 text-blue-800; }
        .status-concluido { @apply bg-green-100 text-green-800; }
        .status-cancelado { @apply bg-red-100 text-red-800; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-gray-900">Gerenciar Saques Pix</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="index.php" class="text-gray-600 hover:text-gray-900">Voltar ao Admin</a>
                        <a href="../perfil.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Meu Perfil</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if (isset($mensagem)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Total de Saques</div>
                    <div class="text-2xl font-bold text-gray-900"><?= $stats['total'] ?></div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Pendentes</div>
                    <div class="text-2xl font-bold text-yellow-600"><?= $stats['pendente'] ?></div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Concluídos</div>
                    <div class="text-2xl font-bold text-green-600"><?= $stats['concluido'] ?></div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Cancelados</div>
                    <div class="text-2xl font-bold text-red-600"><?= $stats['cancelado'] ?></div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="text-sm font-medium text-gray-500">Valor Total</div>
                    <div class="text-2xl font-bold text-blue-600">R$ <?= number_format($stats['valor_total'], 2, ',', '.') ?></div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Solicitações de Saque</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chave Pix</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($saques as $saque): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?= $saque['id'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($saque['usuario_nome']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($saque['usuario_email']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        R$ <?= number_format($saque['valor'], 2, ',', '.') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= ucfirst($saque['tipo_chave']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($saque['chave_pix']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full status-<?= $saque['status'] ?>">
                                            <?= ucfirst($saque['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d/m/Y H:i', strtotime($saque['data_solicitacao'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($saque['status'] === 'pendente'): ?>
                                            <div class="flex space-x-2">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="saque_id" value="<?= $saque['id'] ?>">
                                                    <input type="hidden" name="acao" value="aprovar">
                                                    <button type="submit" class="text-green-600 hover:text-green-900" onclick="return confirm('Aprovar este saque?')">
                                                        Aprovar
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="saque_id" value="<?= $saque['id'] ?>">
                                                    <input type="hidden" name="acao" value="cancelar">
                                                    <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Cancelar este saque? O valor será devolvido ao usuário.')">
                                                        Cancelar
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>