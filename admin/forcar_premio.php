<?php
/**
 * Sistema de Configuração de Prêmios - Raspadinha
 * Versão refatorada com melhorias de segurança e estrutura
 * Versão completa integrada com todos os recursos
 */

session_start();
require '../includes/db.php';

// Classe para gerenciar configurações de prêmios
class PremioManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Verifica se o usuário tem permissão de administrador
     */
    public function verificarPermissaoAdmin() {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['is_admin'] != 1) {
            throw new Exception("Acesso negado. Você precisa ser administrador para acessar esta página.");
        }
    }
    
    /**
     * Valida os dados de entrada
     */
    private function validarDados($valor, $max) {
        $erros = [];
        
        if (!is_numeric($valor) || $valor < 0) {
            $erros[] = "O valor do prêmio deve ser um número positivo.";
        }
        
        if (!is_numeric($max) || $max < 0 || $max > 999999) {
            $erros[] = "O máximo de prêmios deve ser um número entre 0 e 999.999.";
        }
        
        return $erros;
    }
    
    /**
     * Obtém a configuração atual
     */
    public function obterConfiguracao() {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM raspadinha_config LIMIT 1");
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return $result->fetch_assoc();
            } else {
                return [
                    'ativo' => 0,
                    'valor_premio' => 0.00,
                    'max_premios' => 0,
                    'premios_pagos' => 0
                ];
            }
        } catch (Exception $e) {
            error_log("Erro ao obter configuração: " . $e->getMessage());
            throw new Exception("Erro ao carregar configurações.");
        }
    }
    
    /**
     * Atualiza ou insere configuração
     */
    public function atualizarConfiguracao($ativo, $valor, $max) {
        // Validar dados
        $erros = $this->validarDados($valor, $max);
        if (!empty($erros)) {
            throw new Exception(implode(" ", $erros));
        }
        
        try {
            // Verificar se já existe configuração
            $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM raspadinha_config");
            $stmt->execute();
            $result = $stmt->get_result();
            $total = $result->fetch_assoc()['total'];
            
            if ($total == 0) {
                // Inserir nova configuração
                $stmt = $this->conn->prepare("INSERT INTO raspadinha_config (ativo, valor_premio, max_premios, premios_pagos) VALUES (?, ?, ?, 0)");
                $stmt->bind_param("idi", $ativo, $valor, $max);
            } else {
                // Atualizar configuração existente
                $stmt = $this->conn->prepare("UPDATE raspadinha_config SET ativo = ?, valor_premio = ?, max_premios = ? WHERE id = (SELECT id FROM (SELECT id FROM raspadinha_config LIMIT 1) as temp)");
                $stmt->bind_param("idi", $ativo, $valor, $max);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao salvar configuração.");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erro ao atualizar configuração: " . $e->getMessage());
            throw new Exception("Erro ao salvar configuração. Tente novamente.");
        }
    }
    
    /**
     * Zera os prêmios pagos
     */
    public function zerarPremiosPagos() {
        try {
            $stmt = $this->conn->prepare("UPDATE raspadinha_config SET premios_pagos = 0");
            if (!$stmt->execute()) {
                throw new Exception("Erro ao zerar prêmios pagos.");
            }
            return true;
        } catch (Exception $e) {
            error_log("Erro ao zerar prêmios: " . $e->getMessage());
            throw new Exception("Erro ao zerar prêmios pagos. Tente novamente.");
        }
    }
}

// Inicializar gerenciador de prêmios
$premioManager = new PremioManager($conn);
$mensagem = '';
$tipoMensagem = '';

try {
    // Verificar permissões
    $premioManager->verificarPermissaoAdmin();
    
    // Processar ações
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['zerar_premios'])) {
            // Zerar prêmios pagos
            if ($premioManager->zerarPremiosPagos()) {
                $mensagem = "Prêmios pagos zerados com sucesso!";
                $tipoMensagem = "success";
            }
        } elseif (isset($_POST['salvar_config'])) {
            // Atualizar configuração
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            $valor = isset($_POST['valor']) ? floatval($_POST['valor']) : 0;
            $max = isset($_POST['max']) ? intval($_POST['max']) : 0;
            
            if ($premioManager->atualizarConfiguracao($ativo, $valor, $max)) {
                $mensagem = "Configuração salva com sucesso!";
                $tipoMensagem = "success";
            }
        }
    }
    
    // Obter configuração atual
    $config = $premioManager->obterConfiguracao();
    
} catch (Exception $e) {
    $mensagem = $e->getMessage();
    $tipoMensagem = "error";
    
    // Se for erro de permissão, redirecionar ou mostrar página de erro
    if (strpos($mensagem, "Acesso negado") !== false) {
        http_response_code(403);
        die("
        <!DOCTYPE html>
        <html lang='pt-br'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Acesso Negado</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background-color: #f8f9fa; }
                .error-container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .error-icon { font-size: 48px; color: #dc3545; margin-bottom: 20px; }
                h1 { color: #dc3545; margin-bottom: 20px; }
                p { color: #6c757d; margin-bottom: 30px; }
                .btn { padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='error-container'>
                <div class='error-icon'>🚫</div>
                <h1>Acesso Negado</h1>
                <p>Você não tem permissão para acessar esta página. É necessário ser administrador.</p>
                <a href='../index.php' class='btn'>Voltar ao Início</a>
            </div>
        </body>
        </html>
        ");
    }
    
    // Para outros erros, definir configuração padrão
    $config = [
        'ativo' => 0,
        'valor_premio' => 0.00,
        'max_premios' => 0,
        'premios_pagos' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuração de Prêmios - Raspadinha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Estilos principais integrados */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            padding: 2rem 0;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            animation: fadeInUp 0.6s ease-out;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
        
        .card-header h2, .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-check-input {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            transform: translateY(-2px);
        }
        
        .btn {
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border: none;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
            color: white;
        }
        
        .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            animation: fadeInUp 0.5s ease-out;
            border-left: 4px solid;
        }
        
        .alert-success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        }
        
        .alert-danger {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        }
        
        .alert-warning {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            animation: slideInRight 0.8s ease-out;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px) scale(1.02);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            animation: pulse 2s infinite;
        }
        
        .form-check {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-check:hover {
            background: #e3f2fd;
            transform: translateX(5px);
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px 0 0 8px;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        
        .status-active {
            background-color: #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
        }
        
        .status-inactive {
            background-color: #dc3545;
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
        }
        
        /* Estilo específico para o botão voltar */
        .btn-voltar {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }
        
        .btn-voltar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
            color: white;
            text-decoration: none;
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
        
        /* Animações */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .card-header h2 {
                font-size: 1.5rem;
            }
            
            .stats-number {
                font-size: 2rem;
            }
            
            .btn-lg {
                padding: 0.5rem 1rem;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .stats-card {
                margin-bottom: 1rem;
            }
            
            .card-header h2 {
                font-size: 1.25rem;
            }
        }
        
        /* Acessibilidade */
        .btn:focus,
        .form-control:focus,
        .form-check-input:focus {
            outline: 3px solid rgba(102, 126, 234, 0.3);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <!-- Cabeçalho com botão voltar -->
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-center flex-grow-1">
                                <h2><i class="bi bi-trophy-fill me-2"></i>Configuração de Prêmios</h2>
                                <p class="mb-0 opacity-75">Gerencie as configurações do sistema de raspadinha</p>
                            </div>
                            <div class="ms-3">
                                <a href="javascript:history.back()" class="btn-voltar" title="Voltar à página anterior">
                                    <i class="bi bi-arrow-left"></i>
                                    <span class="d-none d-md-inline">Voltar</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mensagens de feedback -->
                <?php if (!empty($mensagem)): ?>
                    <div class="alert alert-<?= $tipoMensagem === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?= $tipoMensagem === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
                        <?= htmlspecialchars($mensagem) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="stats-card">
                            <div class="stats-number"><?= number_format($config['premios_pagos']) ?></div>
                            <div>Prêmios Pagos</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stats-card">
                            <div class="stats-number valor-preview">R$ <?= number_format($config['valor_premio'], 2, ',', '.') ?></div>
                            <div>Valor do Prêmio</div>
                        </div>
                    </div>
                </div>

                <!-- Formulário de Configuração -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Configurações Gerais</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="configForm">
                            <input type="hidden" name="salvar_config" value="1">
                            
                            <!-- Status Ativo -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" 
                                       <?= $config['ativo'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ativo">
                                    <span class="status-indicator <?= $config['ativo'] ? 'status-active' : 'status-inactive' ?>"></span>
                                    <strong>Sistema Ativo</strong>
                                    <small class="d-block text-muted">Marque para ativar o sistema de prêmios</small>
                                </label>
                            </div>

                            <!-- Valor do Prêmio -->
                            <div class="mb-3">
                                <label for="valor" class="form-label">
                                    <i class="bi bi-currency-dollar me-1"></i>Valor do Prêmio
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control" name="valor" id="valor" 
                                           step="0.01" min="0" max="999999.99" 
                                           value="<?= number_format($config['valor_premio'], 2, '.', '') ?>" 
                                           required aria-describedby="valor-help">
                                </div>
                                <div class="form-text" id="valor-help">Valor em reais que será pago como prêmio</div>
                            </div>

                            <!-- Máximo de Prêmios -->
                            <div class="mb-4">
                                <label for="max" class="form-label">
                                    <i class="bi bi-hash me-1"></i>Máximo de Prêmios
                                </label>
                                <input type="number" class="form-control" name="max" id="max" 
                                       min="0" max="999999" value="<?= $config['max_premios'] ?>" 
                                       required aria-describedby="max-help">
                                <div class="form-text" id="max-help">Número máximo de prêmios que podem ser pagos</div>
                            </div>

                            <!-- Botão Salvar -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save me-2"></i>Salvar Configuração
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ações Administrativas -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Ações Administrativas</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Atenção:</strong> Esta ação irá zerar o contador de prêmios pagos. Use com cuidado!
                        </div>
                        
                        <form method="POST" id="zerarForm">
                            <input type="hidden" name="zerar_premios" value="1">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-lg" 
                                        onclick="return confirmarZerarPremios()">
                                    <i class="bi bi-arrow-clockwise me-2"></i>Zerar Prêmios Pagos
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Botão Voltar adicional no final da página -->
                <div class="text-center mb-4">
                    <a href="javascript:history.back()" class="btn btn-secondary btn-lg">
                        <i class="bi bi-arrow-left me-2"></i>Voltar à Página Anterior
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // Elementos do formulário
            const valorInput = document.getElementById('valor');
            const maxInput = document.getElementById('max');
            const ativoCheckbox = document.getElementById('ativo');
            const statusIndicator = document.querySelector('.status-indicator');
            const valorPreview = document.querySelector('.valor-preview');

            // Validação em tempo real
            function validarCampo(input, min, max, tipo) {
                const valor = parseFloat(input.value) || 0;
                const isValid = valor >= min && valor <= max;
                
                input.classList.toggle('is-valid', isValid && input.value !== '');
                input.classList.toggle('is-invalid', !isValid && input.value !== '');
                
                // Feedback visual
                let feedback = input.parentNode.querySelector('.invalid-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    input.parentNode.appendChild(feedback);
                }
                
                if (!isValid && input.value !== '') {
                    feedback.textContent = `${tipo} deve estar entre ${min.toLocaleString('pt-BR')} e ${max.toLocaleString('pt-BR')}`;
                }
                
                return isValid;
            }

            // Preview das configurações
            function atualizarPreview() {
                const valor = parseFloat(valorInput.value) || 0;
                const ativo = ativoCheckbox.checked;
                
                // Atualizar preview do valor
                if (valorPreview) {
                    valorPreview.textContent = `R$ ${valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
                }
                
                // Atualizar indicador de status
                if (statusIndicator) {
                    statusIndicator.className = `status-indicator ${ativo ? 'status-active' : 'status-inactive'}`;
                }
            }

            // Event listeners
            valorInput.addEventListener('input', function() {
                validarCampo(this, 0, 999999.99, 'Valor');
                atualizarPreview();
            });

            maxInput.addEventListener('input', function() {
                validarCampo(this, 0, 999999, 'Máximo de prêmios');
            });

            ativoCheckbox.addEventListener('change', atualizarPreview);

            // Formatação automática de moeda
            valorInput.addEventListener('blur', function() {
                const valor = parseFloat(this.value) || 0;
                this.value = valor.toFixed(2);
                atualizarPreview();
            });

            // Validação do formulário
            document.getElementById('configForm').addEventListener('submit', function(e) {
                const valor = parseFloat(valorInput.value);
                const max = parseInt(maxInput.value);
                
                if (valor < 0 || valor > 999999.99) {
                    e.preventDefault();
                    alert('❌ O valor do prêmio deve estar entre R$ 0,00 e R$ 999.999,99');
                    return false;
                }
                
                if (max < 0 || max > 999999) {
                    e.preventDefault();
                    alert('❌ O máximo de prêmios deve estar entre 0 e 999.999');
                    return false;
                }
                
                // Adicionar loading ao botão
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Salvando...';
                submitBtn.disabled = true;
                
                return true;
            });

            // Atalhos de teclado
            document.addEventListener('keydown', function(e) {
                // Ctrl + S para salvar
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    document.getElementById('configForm').dispatchEvent(new Event('submit'));
                }
                
                // Esc para fechar alertas
                if (e.key === 'Escape') {
                    const alerts = document.querySelectorAll('.alert .btn-close');
                    alerts.forEach(btn => btn.click());
                }

                // Alt + Backspace para voltar
                if (e.altKey && e.key === 'Backspace') {
                    e.preventDefault();
                    history.back();
                }
            });

            // Auto-hide alerts após 5 segundos
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);

            // Salvar/carregar rascunho
            function salvarRascunho() {
                const config = {
                    ativo: ativoCheckbox.checked,
                    valor: valorInput.value,
                    max: maxInput.value,
                    timestamp: Date.now()
                };
                localStorage.setItem('premio_config_draft', JSON.stringify(config));
            }

            function carregarRascunho() {
                const draft = localStorage.getItem('premio_config_draft');
                if (draft) {
                    const config = JSON.parse(draft);
                    // Só carregar se for recente (menos de 1 hora)
                    if (Date.now() - config.timestamp < 3600000) {
                        if (confirm('Encontramos um rascunho salvo. Deseja carregá-lo?')) {
                            ativoCheckbox.checked = config.ativo;
                            valorInput.value = config.valor;
                            maxInput.value = config.max;
                            atualizarPreview();
                        }
                    }
                }
            }

            // Auto-salvar rascunho
            [valorInput, maxInput, ativoCheckbox].forEach(input => {
                input.addEventListener('change', salvarRascunho);
            });

            // Limpar rascunho ao enviar formulário
            document.getElementById('configForm').addEventListener('submit', function() {
                localStorage.removeItem('premio_config_draft');
            });

            // Inicializar
            carregarRascunho();
            atualizarPreview();

            console.log('🎯 Sistema de Prêmios carregado com sucesso!');
        });

        // Confirmação para zerar prêmios
        function confirmarZerarPremios() {
            return confirm('⚠️ Tem certeza que deseja zerar os prêmios pagos?\n\nEsta ação não pode ser desfeita!');
        }

        // Função para voltar com confirmação se houver alterações não salvas
        function voltarComConfirmacao() {
            const form = document.getElementById('configForm');
            const formData = new FormData(form);
            let hasChanges = false;

            // Verificar se há alterações não salvas
            const originalValues = {
                ativo: <?= $config['ativo'] ? 'true' : 'false' ?>,
                valor: '<?= number_format($config['valor_premio'], 2, '.', '') ?>',
                max: '<?= $config['max_premios'] ?>'
            };

            if (document.getElementById('ativo').checked !== originalValues.ativo ||
                document.getElementById('valor').value !== originalValues.valor ||
                document.getElementById('max').value !== originalValues.max) {
                hasChanges = true;
            }

            if (hasChanges) {
                if (confirm('⚠️ Você tem alterações não salvas. Deseja realmente sair sem salvar?')) {
                    history.back();
                }
            } else {
                history.back();
            }
        }
    </script>
</body>
</html>

