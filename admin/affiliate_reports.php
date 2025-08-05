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
$stmt = $conn->prepare("SELECT id, is_admin FROM users WHERE id = ?");
// Alteração aqui: de 'user_id' para 'usuario_id' para consistência
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !$user['is_admin']) {
    header("Location: ../login.php");
    exit();
}

// Processar exportação
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $report_type = $_GET['report_type'] ?? 'affiliates';
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    
    if ($export_type == 'csv') {
        exportToCSV($conn, $report_type, $date_from, $date_to);
    } elseif ($export_type == 'pdf') {
        exportToPDF($conn, $report_type, $date_from, $date_to);
    }
    exit();
}

// Filtros
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$affiliate_id = $_GET['affiliate_id'] ?? '';

// Relatório de Afiliados
$affiliates_query = "
    SELECT 
        a.id as affiliate_id,
        a.affiliate_code,
        u.name,
        u.email,
        u.affiliate_balance,
        (SELECT COUNT(*) FROM affiliate_clicks ac WHERE ac.affiliate_id = a.id AND DATE(ac.created_at) BETWEEN ? AND ?) as clicks,
        (SELECT COUNT(*) FROM affiliate_conversions conv WHERE conv.affiliate_id = a.id AND conv.conversion_type = 'signup' AND DATE(conv.created_at) BETWEEN ? AND ?) as signups,
        (SELECT COUNT(*) FROM affiliate_conversions conv WHERE conv.affiliate_id = a.id AND conv.conversion_type = 'deposit' AND DATE(conv.created_at) BETWEEN ? AND ?) as deposits,
        (SELECT COALESCE(SUM(amount), 0) FROM commissions c WHERE c.affiliate_id = a.id AND DATE(c.created_at) BETWEEN ? AND ?) as total_commissions,
        (SELECT COALESCE(SUM(amount), 0) FROM commissions c WHERE c.affiliate_id = a.id AND c.type = 'CPA' AND DATE(c.created_at) BETWEEN ? AND ?) as cpa_commissions,
        (SELECT COALESCE(SUM(amount), 0) FROM commissions c WHERE c.affiliate_id = a.id AND c.type = 'RevShare' AND DATE(c.created_at) BETWEEN ? AND ?) as revshare_commissions
    FROM affiliates a
    JOIN users u ON a.user_id = u.id
    WHERE a.is_active = 1
    " . ($affiliate_id ? "AND a.id = ?" : "") . "
    ORDER BY total_commissions DESC
";

$stmt = $conn->prepare($affiliates_query);

// Adiciona verificação para garantir que a preparação da query foi bem-sucedida
if ($stmt === false) {
    die("Erro na preparação da query de afiliados: " . $conn->error);
}

// Havia 6 pares de datas, totalizando 12 's'. A correção anterior estava correta.
// O problema pode estar na forma como os parâmetros são passados para call_user_func_array
// ou em algum outro detalhe da query.
// Vamos garantir que o número de 's' e 'i' está correto para todos os cenários.

$types = "ssssssssssss"; // 6 pares de datas (12 's')
$params = [
    $date_from, $date_to, 
    $date_from, $date_to, 
    $date_from, $date_to, 
    $date_from, $date_to, 
    $date_from, $date_to, 
    $date_from, $date_to
];

if ($affiliate_id) {
    $types .= "i";
    $params[] = $affiliate_id;
}

// A forma como call_user_func_array é usada está correta.
// O problema pode ser que a query está muito complexa para o prepare do MySQL
// ou há um erro de sintaxe sutil que não foi pego.
// Vamos simplificar a query para testar se o prepare funciona.
// Se funcionar, o problema é na complexidade da query ou em alguma subquery.

// Para depuração, vamos tentar uma query mais simples primeiro.
// $affiliates_query_simple = "SELECT id, affiliate_code FROM affiliates LIMIT 1";
// $stmt_simple = $conn->prepare($affiliates_query_simple);
// if ($stmt_simple === false) {
//     die("Erro na preparação da query simples: " . $conn->error);
// }
// $stmt_simple->execute();
// $result_simple = $stmt_simple->get_result();
// if ($result_simple) {
//     echo "Query simples funcionou!";
// } else {
//     echo "Query simples falhou!";
// }

// O problema pode ser a quantidade de subqueries correlacionadas.
// Vamos tentar reescrever a query para usar JOINs ou CTEs se possível, ou simplificar as subqueries.
// Por enquanto, vamos garantir que a string de tipos e parâmetros está 100% correta.

// A string de tipos e o array de parâmetros estão corretos para a query atual.
// O erro 

