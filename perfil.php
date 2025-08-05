<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';

// Verifica se o usuário está logado
$userId = $_SESSION['usuario_id'] ?? null;
if (!$userId) {
    header("Location: login.php");
    exit;
}

// Busca dados do usuário incluindo verificação de admin
$stmt = $conn->prepare("SELECT name, email, balance, is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

// Verifica se o usuário é admin
$isAdmin = isset($usuario['is_admin']) && $usuario['is_admin'] == 1;

// Processa alteração de senha
$mensagem = "";
$tipoMensagem = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nova_senha'])) {
    $novaSenha = $_POST['nova_senha'];
    $confirmarSenha = $_POST['confirmar_senha'];

    if ($novaSenha === $confirmarSenha && strlen($novaSenha) >= 6) {
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $senhaHash, $userId);
        $update->execute();
        $mensagem = "Senha alterada com sucesso!";
        $tipoMensagem = "sucesso";
    } else {
        $mensagem = "Erro: as senhas não coincidem ou são muito curtas (mínimo 6 caracteres).";
        $tipoMensagem = "erro";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Raspadinhas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        black: '#000000',
                        white: '#ffffff',
                        accent: '#22c55e',
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: black; color: white; }
        .gradient-bg { background: #22c55e; }
        .btn-primary { background: #22c55e; }
        .btn-primary:hover { background: #16a34a; }
        .bg-card { background-color: black; border: 1px solid #374151; }
        .input-field { background-color: #1f2937; border-color: #4b5563; color: white; }
        .input-field:focus { border-color: #22c55e; background-color: black; }
        .info-box { background-color: rgba(34, 197, 94, 0.1 ); border: 1px solid rgba(34, 197, 94, 0.3); }
        .balance-card { background: #22c55e; }
    </style>
</head>
<body class="font-sans">
    <!-- Header -->
    <div class="gradient-bg text-white py-8 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-bold">Meu Perfil</h1>
            <p class="text-lg mt-2">Gerencie suas informações e seu saldo.</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <div class="mb-8">
            <a href="raspadinhas" class="inline-flex items-center px-6 py-3 bg-gray-800 text-white rounded-xl hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Voltar para o Início
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Coluna Principal -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Card de Informações Pessoais -->
                <div class="bg-card rounded-2xl p-8">
                    <h2 class="text-2xl font-bold mb-6">Informações Pessoais</h2>
                    <div class="space-y-4">
                        <div class="info-box p-4 rounded-lg">
                            <p class="text-sm text-gray-400">Nome Completo</p>
                            <p class="text-lg font-semibold"><?= htmlspecialchars($usuario['name']) ?></p>
                        </div>
                        <div class="info-box p-4 rounded-lg">
                            <p class="text-sm text-gray-400">E-mail</p>
                            <p class="text-lg font-semibold"><?= htmlspecialchars($usuario['email']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Card de Alteração de Senha -->
                <div class="bg-card rounded-2xl p-8">
                    <h3 class="text-2xl font-bold mb-6">Alterar Senha</h3>
                    <?php if ($mensagem): ?>
                        <div class="mb-6 p-4 rounded-lg <?= $tipoMensagem === 'sucesso' ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' ?>">
                            <?= $mensagem ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Nova Senha</label>
                            <input type="password" name="nova_senha" class="w-full px-4 py-3 rounded-lg input-field" required minlength="6">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Confirmar Nova Senha</label>
                            <input type="password" name="confirmar_senha" class="w-full px-4 py-3 rounded-lg input-field" required minlength="6">
                        </div>
                        <button type="submit" class="w-full btn-primary text-white font-bold py-3 px-6 rounded-lg">Alterar Senha</button>
                    </form>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Card do Saldo -->
                <div class="bg-card rounded-2xl p-6 text-center">
                    <div class="balance-card text-white p-6 rounded-xl mb-4">
                        <p class="text-sm uppercase">Saldo Atual</p>
                        <p class="text-3xl font-bold">R$ <?= number_format($usuario['balance'], 2, ',', '.') ?></p>
                    </div>
                    <button id="openModalBtn" class="w-full btn-primary text-white font-bold py-3 px-6 rounded-lg">Sacar via Pix</button>
                </div>
                
                <!-- Card de Afiliados -->
                <div class="bg-card rounded-2xl p-6 text-center">
                     <h3 class="text-xl font-bold mb-4">Programa de Afiliados</h3>
                     <a href="affiliate_dashboard.php" class="w-full btn-primary text-white font-bold py-3 px-6 rounded-lg">Painel de Afiliado</a>
                </div>

                <?php if ($isAdmin): ?>
                <!-- Card de Painel Admin -->
                <div class="bg-card rounded-2xl p-6 text-center">
                    <h3 class="text-xl font-bold mb-4">Painel Administrativo</h3>
                    <a href="admin/index.php" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-6 rounded-lg">Acessar Admin</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Saque Pix -->
    <div id="modalSaquePix" class="hidden fixed inset-0 bg-black bg-opacity-70 z-50 flex items-center justify-center p-4">
        <div class="bg-card rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b border-gray-700">
                <h3 class="text-xl font-bold">Saque via Pix</h3>
                <button id="closeModalBtn" class="text-gray-400 hover:text-white">&times;</button>
            </div>
            <div class="p-6">
                <div class="info-box p-4 rounded-lg mb-6">
                    <span class="font-semibold">Saldo Disponível:</span>
                    <span class="font-bold text-lg">R$ <?= number_format($usuario['balance'], 2, ',', '.') ?></span>
                </div>
                <form id="formSaquePix" onsubmit="event.preventDefault(); processarSaque();" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Valor do Saque (R$)</label>
                        <input type="number" id="valorSaque" name="valor" step="0.01" min="10" max="<?= $usuario['balance'] ?>" class="w-full px-4 py-3 rounded-lg input-field" placeholder="0,00" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Tipo de Chave Pix</label>
                        <select id="tipoChave" name="tipo_chave" class="w-full px-4 py-3 rounded-lg input-field" required>
                            <option value="">Selecione o tipo</option>
                            <option value="cpf">CPF</option>
                            <option value="email">E-mail</option>
                            <option value="telefone">Telefone</option>
                            <option value="aleatoria">Chave Aleatória</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Chave Pix</label>
                        <input type="text" id="chavePix" name="chave_pix" class="w-full px-4 py-3 rounded-lg input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">Nome Completo</label>
                        <input type="text" id="nomeCompleto" name="nome_completo" class="w-full px-4 py-3 rounded-lg input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-2">CPF</label>
                        <input type="text" id="cpf" name="cpf" maxlength="14" oninput="mascaraCPF(this)" class="w-full px-4 py-3 rounded-lg input-field" placeholder="000.000.000-00" required>
                    </div>
                    <div id="mensagemErro" class="hidden bg-red-900 text-red-300 px-4 py-3 rounded-lg text-sm"></div>
                    <div class="flex space-x-3">
                        <button type="button" id="cancelModalBtn" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg">Cancelar</button>
                        <button type="submit" id="botaoSaque" class="flex-1 btn-primary text-white font-bold py-3 px-6 rounded-lg">Solicitar Saque</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalSaquePix');
        const openBtn = document.getElementById('openModalBtn');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelModalBtn');
        const form = document.getElementById('formSaquePix');
        const erroDiv = document.getElementById('mensagemErro');
        const botaoSaque = document.getElementById('botaoSaque');

        function abrirModal() {
            if (modal) modal.classList.remove('hidden');
        }

        function fecharModal() {
            if (modal) modal.classList.add('hidden');
            if (form) form.reset();
            if (erroDiv) erroDiv.classList.add('hidden');
        }

        if (openBtn) openBtn.addEventListener('click', abrirModal);
        if (closeBtn) closeBtn.addEventListener('click', fecharModal);
        if (cancelBtn) cancelBtn.addEventListener('click', fecharModal);
        if (modal) {
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    fecharModal();
                }
            });
        }
        
        window.mascaraCPF = function(input) {
            let valor = input.value.replace(/\D/g, '');
            valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
            valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
            valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            input.value = valor;
        }

        window.processarSaque = function() {
            const valor = parseFloat(document.getElementById('valorSaque').value);
            const saldoAtual = <?= $usuario['balance'] ?>;
            
            erroDiv.classList.add('hidden');

            if (valor < 10) {
                erroDiv.textContent = 'O valor mínimo para saque é R$ 10,00';
                erroDiv.classList.remove('hidden');
                return;
            }
            if (valor > saldoAtual) {
                erroDiv.textContent = 'Valor do saque não pode ser maior que o saldo disponível';
                erroDiv.classList.remove('hidden');
                return;
            }

            const formData = new FormData(form);
            const textoOriginal = botaoSaque.innerHTML;
            botaoSaque.innerHTML = 'Processando...';
            botaoSaque.disabled = true;

            fetch('processar_saque_pix.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Solicitação de saque enviada com sucesso!');
                    fecharModal();
                    location.reload();
                } else {
                    erroDiv.textContent = data.message || 'Erro ao processar saque.';
                    erroDiv.classList.remove('hidden');
                }
            })
            .catch(error => {
                erroDiv.textContent = 'Erro de conexão. Tente novamente.';
                erroDiv.classList.remove('hidden');
            })
            .finally(() => {
                botaoSaque.innerHTML = textoOriginal;
                botaoSaque.disabled = false;
            });
        }
    });
    </script>
</body>
</html>
