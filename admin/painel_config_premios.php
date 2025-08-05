
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Configurar Prêmios</title>
  <style>
    body {
      font-family: sans-serif;
      background: #f0f0f0;
      padding: 30px;
    }
    #painel {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      width: 500px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h1 {
      margin-top: 0;
      color: #333;
    }
    .premio {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }
    .premio label {
      flex: 1;
    }
    .salvar {
      margin-top: 20px;
      padding: 10px 20px;
      font-size: 16px;
      background: #4caf50;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div id="painel">
    <h1>🎰 Controle de Prêmios Permitidos</h1>
    <form id="formPremios">
      <div class="premio">
        <label for="p50">Permitir prêmio 3x R$50</label>
        <input type="checkbox" id="p50" name="50" checked>
      </div>
      <div class="premio">
        <label for="p100">Permitir prêmio 3x R$100</label>
        <input type="checkbox" id="p100" name="100" checked>
      </div>
      <div class="premio">
        <label for="p200">Permitir prêmio 3x R$200</label>
        <input type="checkbox" id="p200" name="200" checked>
      </div>
      <div class="premio">
        <label for="p500">Permitir prêmio 3x R$500</label>
        <input type="checkbox" id="p500" name="500" checked>
      </div>
      <button type="button" class="salvar" onclick="salvarConfiguracoes()">💾 Salvar Configurações</button>
    </form>
  </div>

  <script>
    const configPath = 'premios_config.json';

    function salvarConfiguracoes() {
      const form = document.getElementById('formPremios');
      const config = {
        permitir: {
          '50': form['50'].checked,
          '100': form['100'].checked,
          '200': form['200'].checked,
          '500': form['500'].checked
        }
      };
      fetch('api.php?action=salvar_premios', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer admin_token_123'
        },
        body: JSON.stringify(config)
      })
      .then(res => res.json())
      .then(data => {
        alert(data.mensagem || 'Configurações salvas com sucesso!');
      })
      .catch(err => {
        alert('Erro ao salvar configurações');
        console.error(err);
      });
    }
  </script>
</body>
</html>
