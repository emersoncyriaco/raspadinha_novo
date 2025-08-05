
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$configFile = __DIR__ . '/config.json';
$premiosFile = __DIR__ . '/premios_config.json';
$adminPassword = 'admin123';

function lerConfig($configFile) {
    if (!file_exists($configFile)) {
        return [
            'chance_vitoria' => 0.0,
            'ultima_atualizacao' => date('Y-m-d H:i:s'),
            'admin_logado' => false
        ];
    }
    $content = file_get_contents($configFile);
    return json_decode($content, true) ?: [
        'chance_vitoria' => 0.0,
        'ultima_atualizacao' => date('Y-m-d H:i:s'),
        'admin_logado' => false
    ];
}

function salvarConfig($configFile, $config) {
    $config['ultima_atualizacao'] = date('Y-m-d H:i:s');
    return file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
}

function verificarAuth() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    if (strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        return $token === 'admin_token_123';
    }
    return false;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

if ($path === '') {
    $config = lerConfig($configFile);
    $chance = isset($config['chance_vitoria']) ? floatval($config['chance_vitoria']) : 0.0;
    $chance = max(0, min(1, $chance));
    
    $shouldWin = mt_rand(1, 100) <= ($chance * 100);
    $premiosPermitidos = file_exists($premiosFile) ? json_decode(file_get_contents($premiosFile), true)['permitir'] ?? [] : [];

    // Simula uma premiação aleatória para ilustrar (pode ser trocado por lógica real do jogo)
    $possiveisPremios = array_keys($premiosPermitidos);
    shuffle($possiveisPremios);
    $premioSorteado = $possiveisPremios[0] ?? '50';

    if ($shouldWin && isset($premiosPermitidos[$premioSorteado]) && !$premiosPermitidos[$premioSorteado]) {
        // Prêmio sorteado está bloqueado
        $shouldWin = false;
        $premioSorteado = null;
    }

    echo json_encode(['win' => $shouldWin, 'premio' => $shouldWin ? $premioSorteado : null]);
    exit();
    
    echo json_encode(['win' => $shouldWin]);
    exit();
}

try {
    $authRequiredActions = ['atualizar_chance', 'salvar_premios'];
    if (in_array($path, $authRequiredActions) && !verificarAuth()) {
        http_response_code(401);
        echo json_encode(['sucesso' => false, 'erro' => 'Não autorizado']);
        exit();
    }

    switch ($method) {
        case 'GET':
            if ($path === 'config') {
                $config = lerConfig($configFile);
                echo json_encode([
                    'sucesso' => true,
                    'chance_vitoria' => $config['chance_vitoria'],
                    'ultima_atualizacao' => $config['ultima_atualizacao']
                ]);
            } elseif ($path === 'status') {
                $config = lerConfig($configFile);
                echo json_encode([
                    'sucesso' => true,
                    'sistema_ativo' => true,
                    'chance_atual' => $config['chance_vitoria'],
                    'ultima_atualizacao' => $config['ultima_atualizacao']
                ]);
            } else {
                throw new Exception('Endpoint não encontrado');
            }
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if ($path === 'login') {
                $senha = $input['senha'] ?? '';
                if ($senha === $adminPassword) {
                    $config = lerConfig($configFile);
                    $config['admin_logado'] = true;
                    salvarConfig($configFile, $config);
                    echo json_encode(['sucesso' => true, 'token' => 'admin_token_123']);
                } else {
                    http_response_code(401);
                    echo json_encode(['sucesso' => false, 'erro' => 'Senha incorreta']);
                }
            } elseif ($path === 'atualizar_chance') {
                $novaChance = $input['chance_vitoria'] ?? null;
                if ($novaChance === null || !is_numeric($novaChance)) {
                    http_response_code(400);
                    echo json_encode(['sucesso' => false, 'erro' => 'Chance inválida']);
                    break;
                }
                $novaChance = floatval($novaChance);
                if ($novaChance < 0 || $novaChance > 1) {
                    http_response_code(400);
                    echo json_encode(['sucesso' => false, 'erro' => 'Chance fora do intervalo']);
                    break;
                }
                $config = lerConfig($configFile);
                $config['chance_vitoria'] = $novaChance;
                if (salvarConfig($configFile, $config)) {
                    echo json_encode(['sucesso' => true, 'mensagem' => 'Chance atualizada']);
                } else {
                    http_response_code(500);
                    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar']);
                }
            } elseif ($path === 'salvar_premios') {
                $permitir = $input['permitir'] ?? null;
                if (!is_array($permitir)) {
                    http_response_code(400);
                    echo json_encode(['sucesso' => false, 'erro' => 'Formato inválido']);
                    break;
                }
                $dados = ['permitir' => array_map('boolval', $permitir)];
                if (file_put_contents($premiosFile, json_encode($dados, JSON_PRETTY_PRINT))) {
                    echo json_encode(['sucesso' => true, 'mensagem' => 'Configurações de prêmios salvas']);
                } else {
                    http_response_code(500);
                    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar prêmios']);
                }
            } else {
                throw new Exception('Ação POST não encontrada');
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>
