<?php
// Demonstração simplificada do sistema de depósito PIX
require_once 'includes/qr_generator.php';

// Simula dados de um depósito
$valor = isset($_POST['valor']) ? floatval($_POST['valor']) : 0;
$qr_code_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valor > 0) {
    // Simula um código PIX real
    $codigo_pix = "00020126580014br.gov.bcb.pix013636c7f4d9-9bb2-4e17-8364-5f7a3dc2b1f15204000053039865802BR5925DEMO PAGAMENTO PIX6009SAO PAULO62070503***6304A7B2";
    
    // Gera o QR Code
    $qr_image = QRGenerator::gerarQRCodePIX($codigo_pix);
    
    if ($qr_image) {
        $qr_code_data = [
            'external_id' => 'DEMO_' . time(),
            'valor' => $valor,
            'pix_code' => $codigo_pix,
            'qr_image' => $qr_image
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo - Depositar via PIX</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .qr-container {
            text-align: center;
            margin-top: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 15px;
            border: 2px solid #e9ecef;
        }
        
        .qr-container h3 {
            color: #28a745;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .qr-container img {
            max-width: 280px;
            height: auto;
            border: 3px solid #28a745;
            border-radius: 12px;
            margin: 15px 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .pix-info {
            background: white;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .pix-code {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            word-break: break-all;
            margin: 10px 0;
            max-height: 100px;
            overflow-y: auto;
        }
        
        .copy-btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin: 10px 5px;
        }
        
        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .copy-btn:active {
            transform: translateY(0);
        }
        
        .pix-instructions {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-size: 14px;
            color: #0066cc;
        }
        
        .payment-status {
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            color: #856404;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💳 Demo - Depositar via PIX</h1>
            <p>Demonstração do sistema de depósito melhorado</p>
        </div>
        
        <?php if (!$qr_code_data): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="valor">Valor do Depósito (R$)</label>
                    <input type="number" id="valor" name="valor" step="0.01" min="1" required placeholder="Ex: 50.00">
                </div>
                
                <button type="submit" class="btn">🎯 Gerar QR Code PIX</button>
            </form>
        <?php else: ?>
            <div class="qr-container">
                <h3>🎯 QR Code PIX Gerado com Sucesso!</h3>
                
                <div class="pix-info">
                    <p><strong>💰 Valor:</strong> R$ <?= number_format($qr_code_data['valor'], 2, ',', '.') ?></p>
                    <p><strong>🔢 ID da Transação:</strong> <?= htmlspecialchars($qr_code_data['external_id']) ?></p>
                </div>
                
                <?php if ($qr_code_data['qr_image']): ?>
                    <div>
                        <p style="margin-bottom: 10px; font-weight: bold; color: #28a745;">📱 Escaneie o QR Code:</p>
                        <img src="<?= $qr_code_data['qr_image'] ?>" alt="QR Code PIX" id="qrCodeImage">
                    </div>
                <?php else: ?>
                    <div style="padding: 20px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c33;">
                        ⚠️ Erro ao gerar QR Code visual. Use o código PIX abaixo.
                    </div>
                <?php endif; ?>
                
                <div class="pix-instructions">
                    <strong>📋 Como pagar:</strong><br>
                    1. Abra o app do seu banco<br>
                    2. Escolha PIX → Pagar com QR Code<br>
                    3. Escaneie o código acima OU copie o código abaixo
                </div>
                
                <div style="margin: 20px 0;">
                    <p style="font-weight: bold; margin-bottom: 10px; color: #28a745;">💳 Código PIX para Copiar:</p>
                    <div class="pix-code" id="pixCode"><?= htmlspecialchars($qr_code_data['pix_code']) ?></div>
                    
                    <button class="copy-btn" onclick="copyPixCode()">
                        📋 Copiar Código PIX
                    </button>
                    
                    <button class="copy-btn" onclick="sharePixCode()" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);">
                        📤 Compartilhar
                    </button>
                </div>
                
                <div class="payment-status">
                    <strong>⏳ Status:</strong> Aguardando pagamento...<br>
                    <small>O saldo será creditado automaticamente após a confirmação do pagamento.</small>
                </div>
                
                <div style="margin-top: 20px; font-size: 13px; color: #666; text-align: center;">
                    <p>🔒 Pagamento seguro via PIX</p>
                    <p>⚡ Processamento instantâneo</p>
                </div>
            </div>
            
            <a href="demo_deposito.php" class="back-link">🔄 Fazer Novo Depósito</a>
        <?php endif; ?>
    </div>
    
    <script>
        function copyPixCode() {
            const pixCode = document.getElementById('pixCode').textContent;
            
            // Tenta usar a API moderna do clipboard
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(pixCode).then(function() {
                    showCopySuccess('Código PIX copiado com sucesso! 📋✅');
                }, function(err) {
                    console.error('Erro ao copiar: ', err);
                    fallbackCopyTextToClipboard(pixCode);
                });
            } else {
                // Fallback para navegadores mais antigos
                fallbackCopyTextToClipboard(pixCode);
            }
        }
        
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopySuccess('Código PIX copiado com sucesso! 📋✅');
                } else {
                    showCopyError('Erro ao copiar. Tente selecionar e copiar manualmente.');
                }
            } catch (err) {
                console.error('Fallback: Erro ao copiar', err);
                showCopyError('Erro ao copiar. Tente selecionar e copiar manualmente.');
            }
            
            document.body.removeChild(textArea);
        }
        
        function sharePixCode() {
            const pixCode = document.getElementById('pixCode').textContent;
            const valor = '<?= isset($qr_code_data) ? number_format($qr_code_data['valor'], 2, ',', '.') : '' ?>';
            const shareText = `💳 Código PIX para pagamento de R$ ${valor}\n\n${pixCode}\n\n🔒 Pagamento seguro via PIX`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Código PIX para Pagamento',
                    text: shareText
                }).then(() => {
                    console.log('Compartilhamento realizado com sucesso');
                }).catch((error) => {
                    console.log('Erro ao compartilhar:', error);
                    fallbackShare(shareText);
                });
            } else {
                fallbackShare(shareText);
            }
        }
        
        function fallbackShare(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    showCopySuccess('Texto copiado! Agora você pode colar em qualquer app. 📤✅');
                });
            } else {
                fallbackCopyTextToClipboard(text);
            }
        }
        
        function showCopySuccess(message) {
            // Remove alertas anteriores
            const existingAlert = document.querySelector('.copy-alert');
            if (existingAlert) {
                existingAlert.remove();
            }
            
            // Cria novo alerta
            const alert = document.createElement('div');
            alert.className = 'copy-alert';
            alert.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000;
                font-weight: 500;
                animation: slideIn 0.3s ease-out;
            `;
            alert.textContent = message;
            
            // Adiciona animação CSS
            if (!document.querySelector('#copyAlertStyles')) {
                const style = document.createElement('style');
                style.id = 'copyAlertStyles';
                style.textContent = `
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            document.body.appendChild(alert);
            
            // Remove após 3 segundos
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 3000);
        }
        
        function showCopyError(message) {
            alert(message);
        }
        
        // Verifica se o QR Code carregou corretamente
        document.addEventListener('DOMContentLoaded', function() {
            const qrImage = document.getElementById('qrCodeImage');
            if (qrImage) {
                qrImage.onerror = function() {
                    console.error('Erro ao carregar QR Code');
                    this.style.display = 'none';
                    const errorDiv = document.createElement('div');
                    errorDiv.style.cssText = `
                        padding: 20px;
                        background: #fee;
                        border: 1px solid #fcc;
                        border-radius: 8px;
                        color: #c33;
                        margin: 15px 0;
                    `;
                    errorDiv.innerHTML = '⚠️ Erro ao carregar QR Code. Use o código PIX abaixo para realizar o pagamento.';
                    this.parentNode.appendChild(errorDiv);
                };
                
                qrImage.onload = function() {
                    console.log('QR Code carregado com sucesso');
                };
            }
        });
    </script>
</body>
</html>

