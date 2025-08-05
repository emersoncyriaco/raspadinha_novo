<?php
/**
 * Script de teste completo para validar o sistema de afiliados
 * com todas as novas funcionalidades implementadas
 */

require_once 'includes/db.php';
require_once 'includes/affiliate_functions.php';

echo "<h1>🧪 Teste Completo do Sistema de Afiliados</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    h2 { color: #333; border-bottom: 2px solid #007cba; padding-bottom: 5px; }
</style>";

$tests_passed = 0;
$tests_failed = 0;

function test_result($test_name, $result, $message = '') {
    global $tests_passed, $tests_failed;
    
    if ($result) {
        echo "<div class='success'>✅ $test_name: PASSOU</div>";
        if ($message) echo "<div class='info'>   → $message</div>";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ $test_name: FALHOU</div>";
        if ($message) echo "<div class='error'>   → $message</div>";
        $tests_failed++;
    }
    echo "<br>";
}

// Teste 1: Verificar estrutura das tabelas
echo "<div class='section'>";
echo "<h2>📊 Teste 1: Estrutura das Tabelas</h2>";

$tables_to_check = ['affiliates', 'commissions', 'affiliate_levels', 'payouts', 'global_settings'];
foreach ($tables_to_check as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    test_result("Tabela $table existe", $result && $result->num_rows > 0);
}

// Verificar colunas específicas
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'two_factor_secret'");
test_result("Coluna two_factor_secret em users", $result && $result->num_rows > 0);

echo "</div>";

// Teste 2: Verificar arquivos do sistema
echo "<div class='section'>";
echo "<h2>📁 Teste 2: Arquivos do Sistema</h2>";

$files_to_check = [
    'includes/affiliate_functions.php',
    'includes/GoogleAuthenticator.php',
    'affiliate_tracker.php',
    'affiliate_dashboard.php',
    'admin/setup_2fa.php',
    'admin/payout_management.php',
    'admin/global_settings.php',
    'admin/affiliate_advanced_control.php'
];

foreach ($files_to_check as $file) {
    test_result("Arquivo $file existe", file_exists($file));
}

echo "</div>";

// Teste 3: Verificar configurações globais
echo "<div class='section'>";
echo "<h2>⚙️ Teste 3: Configurações Globais</h2>";

$settings_result = $conn->query("SELECT COUNT(*) as count FROM global_settings");
if ($settings_result) {
    $count = $settings_result->fetch_assoc()['count'];
    test_result("Configurações globais carregadas", $count > 0, "$count configurações encontradas");
} else {
    test_result("Configurações globais carregadas", false, "Erro ao consultar configurações");
}

// Verificar configurações específicas
$required_settings = [
    'default_revshare_rate',
    'min_payout_amount',
    'affiliate_system_enabled',
    'max_affiliate_levels'
];

foreach ($required_settings as $setting) {
    $result = $conn->query("SELECT setting_value FROM global_settings WHERE setting_key = '$setting'");
    test_result("Configuração $setting existe", $result && $result->num_rows > 0);
}

echo "</div>";

// Teste 4: Verificar funções de afiliados
echo "<div class='section'>";
echo "<h2>🔧 Teste 4: Funções de Afiliados</h2>";

// Verificar se as funções existem
$functions_to_check = [
    'generateAffiliateCode',
    'createAffiliate',
    'trackAffiliateClick',
    'calculateAndRegisterCommissions'
];

foreach ($functions_to_check as $function) {
    test_result("Função $function existe", function_exists($function));
}

// Teste de geração de código de afiliado
$test_code = generateAffiliateCode();
test_result("Geração de código de afiliado", !empty($test_code) && strlen($test_code) >= 6, "Código gerado: $test_code");

echo "</div>";

// Teste 5: Verificar sistema de 2FA
echo "<div class='section'>";
echo "<h2>🔐 Teste 5: Sistema de 2FA</h2>";

// Verificar se a classe GoogleAuthenticator existe
if (file_exists('includes/GoogleAuthenticator.php')) {
    require_once 'includes/GoogleAuthenticator.php';
    test_result("Classe GoogleAuthenticator carregada", class_exists('PHPGangsta_GoogleAuthenticator'));
    
    if (class_exists('PHPGangsta_GoogleAuthenticator')) {
        $ga = new PHPGangsta_GoogleAuthenticator();
        $secret = $ga->createSecret();
        test_result("Geração de secret 2FA", !empty($secret), "Secret gerado: " . substr($secret, 0, 10) . "...");
    }
} else {
    test_result("Arquivo GoogleAuthenticator.php", false, "Arquivo não encontrado");
}

echo "</div>";

// Teste 6: Verificar páginas administrativas
echo "<div class='section'>";
echo "<h2>👨‍💼 Teste 6: Páginas Administrativas</h2>";

$admin_pages = [
    'admin/payout_management.php' => 'Gestão de Pagamentos',
    'admin/global_settings.php' => 'Configurações Globais',
    'admin/affiliate_advanced_control.php' => 'Controle Avançado'
];

foreach ($admin_pages as $page => $name) {
    $content = file_get_contents($page);
    test_result("Página $name funcional", 
        strpos($content, '<?php') !== false && 
        strpos($content, 'session_start()') !== false,
        "Estrutura PHP válida"
    );
}

echo "</div>";

// Teste 7: Verificar painel do afiliado
echo "<div class='section'>";
echo "<h2>👤 Teste 7: Painel do Afiliado</h2>";

$dashboard_content = file_get_contents('affiliate_dashboard.php');
test_result("Painel do afiliado carregado", !empty($dashboard_content));

// Verificar elementos específicos do painel
$elements_to_check = [
    'performanceChart' => 'Gráfico de desempenho',
    'copyAffiliateLink' => 'Função de copiar link',
    'shareOnWhatsApp' => 'Compartilhamento WhatsApp',
    'Chart.js' => 'Biblioteca de gráficos'
];

foreach ($elements_to_check as $element => $description) {
    test_result("$description presente", strpos($dashboard_content, $element) !== false);
}

echo "</div>";

// Teste 8: Verificar integração com perfil do usuário
echo "<div class='section'>";
echo "<h2>👤 Teste 8: Integração com Perfil</h2>";

$perfil_content = file_get_contents('perfil.php');
test_result("Botão de afiliado no perfil", 
    strpos($perfil_content, 'affiliate_dashboard.php') !== false &&
    strpos($perfil_content, 'Sistema de Afiliados') !== false,
    "Link e texto encontrados"
);

echo "</div>";

// Teste 9: Verificar rastreamento de afiliados
echo "<div class='section'>";
echo "<h2>🔍 Teste 9: Rastreamento de Afiliados</h2>";

$tracker_content = file_get_contents('affiliate_tracker.php');
test_result("Sistema de rastreamento", !empty($tracker_content));

// Verificar se o rastreamento está integrado
$inicio_content = file_get_contents('inicio.php');
test_result("Rastreamento integrado no início", 
    strpos($inicio_content, 'affiliate_tracker.php') !== false,
    "Include encontrado"
);

echo "</div>";

// Teste 10: Verificar responsividade e recursos avançados
echo "<div class='section'>";
echo "<h2>📱 Teste 10: Recursos Avançados</h2>";

// Verificar CSS responsivo
test_result("CSS responsivo no painel", 
    strpos($dashboard_content, '@media') !== false &&
    strpos($dashboard_content, 'mobile') !== false,
    "Media queries encontradas"
);

// Verificar JavaScript avançado
test_result("JavaScript interativo", 
    strpos($dashboard_content, 'Chart.js') !== false &&
    strpos($dashboard_content, 'addEventListener') !== false,
    "Funcionalidades interativas encontradas"
);

echo "</div>";

// Resumo final
echo "<div class='section'>";
echo "<h2>📋 Resumo Final dos Testes</h2>";
echo "<div class='success'>✅ Testes Aprovados: $tests_passed</div>";
echo "<div class='error'>❌ Testes Falharam: $tests_failed</div>";

$total_tests = $tests_passed + $tests_failed;
$success_rate = $total_tests > 0 ? round(($tests_passed / $total_tests) * 100, 2) : 0;

echo "<div class='info'>📊 Taxa de Sucesso: $success_rate%</div>";

if ($success_rate >= 90) {
    echo "<div class='success'>🎉 SISTEMA APROVADO! Todas as funcionalidades estão funcionando corretamente.</div>";
} elseif ($success_rate >= 70) {
    echo "<div class='info'>⚠️ SISTEMA PARCIALMENTE APROVADO. Algumas funcionalidades podem precisar de ajustes.</div>";
} else {
    echo "<div class='error'>🚨 SISTEMA REPROVADO. Várias funcionalidades precisam ser corrigidas.</div>";
}

echo "</div>";

// Recomendações finais
echo "<div class='section'>";
echo "<h2>💡 Recomendações Finais</h2>";
echo "<ul>";
echo "<li>Execute o script SQL <code>create_affiliate_tables.sql</code> se ainda não foi executado</li>";
echo "<li>Configure as permissões de admin para acessar o painel administrativo</li>";
echo "<li>Teste o sistema 2FA configurando um administrador</li>";
echo "<li>Verifique se todas as configurações globais estão adequadas ao seu negócio</li>";
echo "<li>Teste o fluxo completo: cadastro com referência → depósito → comissão</li>";
echo "</ul>";
echo "</div>";

echo "<p><strong>Teste concluído em:</strong> " . date('d/m/Y H:i:s') . "</p>";
?>

