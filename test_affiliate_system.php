<?php
/**
 * Script de teste para o sistema de afiliados
 * Execute este script para verificar se todas as funcionalidades estão funcionando
 */

require_once 'includes/db.php';
require_once 'includes/affiliate_functions.php';

echo "<h1>Teste do Sistema de Afiliados</h1>\n";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>\n";

$tests_passed = 0;
$tests_failed = 0;

function test($description, $condition) {
    global $tests_passed, $tests_failed;
    
    if ($condition) {
        echo "<p class='success'>✓ $description</p>\n";
        $tests_passed++;
    } else {
        echo "<p class='error'>✗ $description</p>\n";
        $tests_failed++;
    }
}

function info($message) {
    echo "<p class='info'>ℹ $message</p>\n";
}

echo "<h2>1. Teste de Conexão com Banco de Dados</h2>\n";

// Teste 1: Conexão com banco
test("Conexão com banco de dados", $conn && !$conn->connect_error);

echo "<h2>2. Teste de Estrutura das Tabelas</h2>\n";

// Teste 2: Verificar se as tabelas existem
$tables = ['affiliates', 'affiliate_clicks', 'affiliate_conversions', 'commissions', 'referrals', 'payouts', 'admin_simulated_reports'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    test("Tabela '$table' existe", $result && $result->num_rows > 0);
}

echo "<h2>3. Teste de Funções Auxiliares</h2>\n";

// Teste 3: Função de geração de código de afiliado
$code1 = generateAffiliateCode("teste", 123);
$code2 = generateAffiliateCode("user test", 456);
test("Geração de código de afiliado", !empty($code1) && !empty($code2));
info("Códigos gerados: $code1, $code2");

echo "<h2>4. Teste de Criação de Afiliado</h2>\n";

// Teste 4: Criar um usuário de teste
$test_user_name = "Usuario Teste " . time();
$test_user_email = "teste" . time() . "@example.com";
$test_password = password_hash("123456", PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password, balance) VALUES (?, ?, ?, 0.00)");
$stmt->bind_param("sss", $test_user_name, $test_user_email, $test_password);
$user_created = $stmt->execute();
$test_user_id = $conn->insert_id;

test("Criação de usuário de teste", $user_created && $test_user_id > 0);

if ($test_user_id > 0) {
    // Teste 5: Criar afiliado
    $affiliate_code = createAffiliateIfNotExists($conn, $test_user_id, $test_user_name);
    test("Criação de afiliado", !empty($affiliate_code));
    info("Código do afiliado criado: $affiliate_code");
    
    // Verificar se foi inserido na tabela
    $stmt = $conn->prepare("SELECT id FROM affiliates WHERE user_id = ?");
    $stmt->bind_param("i", $test_user_id);
    $stmt->execute();
    $affiliate_exists = $stmt->get_result()->num_rows > 0;
    test("Afiliado inserido na tabela", $affiliate_exists);
}

echo "<h2>5. Teste de Rastreamento de Cliques</h2>\n";

if (!empty($affiliate_code)) {
    // Teste 6: Registrar clique
    $click_registered = registerAffiliateClick($conn, $affiliate_code);
    test("Registro de clique de afiliado", $click_registered);
    
    // Verificar se o clique foi registrado
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM affiliate_clicks ac JOIN affiliates a ON ac.affiliate_id = a.id WHERE a.affiliate_code = ?");
    $stmt->bind_param("s", $affiliate_code);
    $stmt->execute();
    $click_count = $stmt->get_result()->fetch_assoc()['count'];
    test("Clique registrado na tabela", $click_count > 0);
    info("Total de cliques registrados: $click_count");
}

echo "<h2>6. Teste de Sistema de Indicações</h2>\n";

if ($test_user_id > 0) {
    // Criar um segundo usuário para testar indicação
    $test_user2_name = "Usuario Indicado " . time();
    $test_user2_email = "indicado" . time() . "@example.com";
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, balance, referrer_id) VALUES (?, ?, ?, 0.00, ?)");
    $stmt->bind_param("sssi", $test_user2_name, $test_user2_email, $test_password, $test_user_id);
    $user2_created = $stmt->execute();
    $test_user2_id = $conn->insert_id;
    
    test("Criação de usuário indicado", $user2_created && $test_user2_id > 0);
    
    if ($test_user2_id > 0) {
        // Teste 7: Registrar cadeia de indicações
        $referral_registered = registerReferralChain($conn, $test_user2_id, $test_user_id);
        test("Registro de cadeia de indicações", $referral_registered);
        
        // Verificar se a indicação foi registrada
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM referrals WHERE referrer_id = ? AND referred_id = ?");
        $stmt->bind_param("ii", $test_user_id, $test_user2_id);
        $stmt->execute();
        $referral_count = $stmt->get_result()->fetch_assoc()['count'];
        test("Indicação registrada na tabela", $referral_count > 0);
    }
}

echo "<h2>7. Teste de Comissões</h2>\n";

if ($test_user_id > 0 && $test_user2_id > 0) {
    // Teste 8: Calcular comissões CPA
    calculateAndRegisterCommissions($conn, $test_user2_id, 'signup', 0, $test_user_id, 1);
    
    // Verificar se a comissão foi registrada
    $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(amount) as total FROM commissions c JOIN affiliates a ON c.affiliate_id = a.id WHERE a.user_id = ?");
    $stmt->bind_param("i", $test_user_id);
    $stmt->execute();
    $commission_data = $stmt->get_result()->fetch_assoc();
    
    test("Comissão CPA registrada", $commission_data['count'] > 0);
    info("Total de comissões: R$ " . number_format($commission_data['total'], 2, ',', '.'));
    
    // Teste 9: Calcular comissões RevShare
    calculateAndRegisterCommissions($conn, $test_user2_id, 'deposit', 100.00, $test_user_id, 1);
    
    // Verificar comissões RevShare
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM commissions c JOIN affiliates a ON c.affiliate_id = a.id WHERE a.user_id = ? AND c.type = 'RevShare'");
    $stmt->bind_param("i", $test_user_id);
    $stmt->execute();
    $revshare_count = $stmt->get_result()->fetch_assoc()['count'];
    
    test("Comissão RevShare registrada", $revshare_count > 0);
}

echo "<h2>8. Teste de Estatísticas</h2>\n";

if ($test_user_id > 0) {
    // Teste 10: Obter estatísticas do afiliado
    $stats = getAffiliateStats($conn, $test_user_id);
    test("Obtenção de estatísticas", is_array($stats) && isset($stats['clicks']));
    
    if (is_array($stats)) {
        info("Estatísticas obtidas:");
        info("- Cliques: " . $stats['clicks']);
        info("- Cadastros: " . $stats['signups']);
        info("- Depósitos: " . $stats['deposits']);
        info("- Comissão CPA: R$ " . number_format($stats['cpa_commission'], 2, ',', '.'));
        info("- Comissão RevShare: R$ " . number_format($stats['revshare_commission'], 2, ',', '.'));
        info("- Saldo: R$ " . number_format($stats['balance'], 2, ',', '.'));
    }
}

echo "<h2>9. Teste de Arquivos do Sistema</h2>\n";

// Teste 11: Verificar se os arquivos principais existem
$files = [
    'includes/affiliate_functions.php',
    'affiliate_tracker.php',
    'affiliate_dashboard.php',
    'admin/affiliates.php',
    'admin/affiliate_levels.php',
    'admin/affiliate_reports.php',
    'admin/affiliate_advanced_control.php',
    'process_commission.php'
];

foreach ($files as $file) {
    test("Arquivo '$file' existe", file_exists($file));
}

echo "<h2>10. Limpeza dos Dados de Teste</h2>\n";

// Limpar dados de teste
if ($test_user_id > 0) {
    // Remover comissões
    $conn->query("DELETE FROM commissions WHERE affiliate_id IN (SELECT id FROM affiliates WHERE user_id IN ($test_user_id, $test_user2_id))");
    
    // Remover indicações
    $conn->query("DELETE FROM referrals WHERE referrer_id = $test_user_id OR referred_id IN ($test_user_id, $test_user2_id)");
    
    // Remover cliques
    $conn->query("DELETE FROM affiliate_clicks WHERE affiliate_id IN (SELECT id FROM affiliates WHERE user_id IN ($test_user_id, $test_user2_id))");
    
    // Remover afiliados
    $conn->query("DELETE FROM affiliates WHERE user_id IN ($test_user_id, $test_user2_id)");
    
    // Remover usuários
    if ($test_user2_id > 0) {
        $conn->query("DELETE FROM users WHERE id = $test_user2_id");
    }
    $conn->query("DELETE FROM users WHERE id = $test_user_id");
    
    info("Dados de teste removidos com sucesso");
}

echo "<h2>Resumo dos Testes</h2>\n";
echo "<p><strong>Testes executados:</strong> " . ($tests_passed + $tests_failed) . "</p>\n";
echo "<p class='success'><strong>Testes aprovados:</strong> $tests_passed</p>\n";
echo "<p class='error'><strong>Testes falharam:</strong> $tests_failed</p>\n";

if ($tests_failed == 0) {
    echo "<p class='success'><strong>🎉 Todos os testes passaram! O sistema de afiliados está funcionando corretamente.</strong></p>\n";
} else {
    echo "<p class='error'><strong>⚠️ Alguns testes falharam. Verifique os erros acima.</strong></p>\n";
}

echo "<hr>\n";
echo "<p><em>Teste executado em: " . date('d/m/Y H:i:s') . "</em></p>\n";
?>

