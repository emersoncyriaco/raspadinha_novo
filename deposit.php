<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Depositar - Raspa Sorte</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Lexend', sans-serif;
      background: linear-gradient(135deg, #202c3e 0%, #1e293b 100%);
      color: white;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .container {
      background-color: #2a3b50;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
      max-width: 500px;
      width: 100%;
      text-align: center;
    }
    .form-input {
      background-color: #3b4a60;
      border: 1px solid #5a6a80;
      color: white;
      padding: 10px 15px;
      border-radius: 8px;
      width: 100%;
      box-sizing: border-box;
    }
    .btn-primary {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      font-weight: bold;
      padding: 10px 20px;
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    .qr-code-container {
      margin-top: 20px;
      padding: 20px;
      background-color: #1e293b;
      border-radius: 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px;
    }
    .copy-button {
      background-color: #6876df;
      color: white;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .copy-button:hover {
      background-color: #5a6ac0;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2 class="text-3xl font-bold mb-6">Depositar via Pix</h2>
    
    <form id="depositForm" class="space-y-4">
      <div>
        <label for="amount" class="block text-left text-sm font-semibold mb-1">Valor do Depósito (R$)</label>
        <input type="number" id="amount" name="amount" placeholder="Ex: 50.00" step="0.01" min="10" required class="form-input">
      </div>
      <div>
        <label for="cpf" class="block text-left text-sm font-semibold mb-1">Seu CPF</label>
        <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" required class="form-input">
      </div>
      <button type="submit" class="btn-primary w-full">
        Gerar QR Code Pix
      </button>
    </form>

    <div id="qrCodeResult" class="qr-code-container hidden">
      <h3 class="text-xl font-bold">Escaneie para Pagar</h3>
      <img id="qrCodeImage" src="" alt="QR Code Pix" class="w-48 h-48 object-contain">
      <p class="text-sm text-gray-400">Ou copie e cole a chave Pix:</p>
      <div class="relative w-full">
        <input type="text" id="pixCopyPaste" readonly class="form-input pr-10">
        <button onclick="copyPixKey()" class="copy-button absolute right-2 top-1/2 -translate-y-1/2">
          <i class="fas fa-copy"></i>
        </button>
      </div>
      <p id="copyMessage" class="text-green-400 text-sm hidden">Copiado!</p>
    </div>

    <p id="errorMessage" class="text-red-500 mt-4 hidden"></p>
  </div>

  <script>
    document.getElementById("depositForm").addEventListener("submit", async function(event) {
      event.preventDefault();
      const amount = document.getElementById("amount").value;
      const cpf = document.getElementById("cpf").value;
      const errorMessage = document.getElementById("errorMessage");
      const qrCodeResult = document.getElementById("qrCodeResult");
      const qrCodeImage = document.getElementById("qrCodeImage");
      const pixCopyPaste = document.getElementById("pixCopyPaste");

      errorMessage.classList.add("hidden");
      qrCodeResult.classList.add("hidden");

      try {
        const response = await fetch("bspay_api.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
          },
          body: `action=generate_qrcode&amount=${amount}&cpf=${cpf}`,
        });

        const data = await response.json();

        if (data.status) {
          qrCodeImage.src = `data:image/png;base64,${data.qrcode}`;
          pixCopyPaste.value = data.pix_copy_paste;
          qrCodeResult.classList.remove("hidden");
        } else {
          errorMessage.textContent = data.message || "Erro ao gerar QR Code.";
          errorMessage.classList.remove("hidden");
        }
      } catch (error) {
        errorMessage.textContent = "Erro de conexão. Tente novamente.";
        errorMessage.classList.remove("hidden");
        console.error("Erro:", error);
      }
    });

    function copyPixKey() {
      const pixCopyPaste = document.getElementById("pixCopyPaste");
      pixCopyPaste.select();
      pixCopyPaste.setSelectionRange(0, 99999); // For mobile devices
      document.execCommand("copy");
      
      const copyMessage = document.getElementById("copyMessage");
      copyMessage.classList.remove("hidden");
      setTimeout(() => {
        copyMessage.classList.add("hidden");
      }, 2000);
    }
  </script>
</body>
</html>

