
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Estatísticas da Raspadinha</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f9f9f9;
      padding: 30px;
    }
    #painel {
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      width: 480px;
      box-shadow: 0 0 12px rgba(0,0,0,0.1);
    }
    h1, h2 {
      color: #333;
    }
    .stat {
      font-size: 18px;
      margin: 10px 0;
    }
    .resultado {
      font-size: 16px;
      background: #eee;
      padding: 5px;
      border-radius: 4px;
      margin: 2px 0;
    }
    #grafico-container {
      margin-top: 20px;
    }
    button {
      margin: 10px 5px 0 0;
      padding: 10px 15px;
      font-size: 15px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    .testar { background: #4caf50; color: white; }
    .exportar { background: #2196f3; color: white; }
  </style>
</head>
<body>

<div id="painel">
  <h1>📊 Estatísticas da Raspadinha</h1>
  <div class="stat">Total de tentativas: <span id="total">0</span></div>
  <div class="stat">Vitórias: <span id="vitorias">0</span></div>
  <div class="stat">Derrotas: <span id="derrotas">0</span></div>

  <div id="grafico-container">
    <canvas id="graficoPizza" width="400" height="400"></canvas>
  </div>

  <h2>Últimos resultados</h2>
  <div id="resultados"></div>

  <button class="testar" onclick="testarRodada()">🎰 Testar Jogada</button>
  <button class="exportar" onclick="exportarCSV()">⬇️ Exportar CSV</button>
</div>

<script>
  let total = 0;
  let vitorias = 0;
  let derrotas = 0;
  let ultimos = [];
  let historicoCSV = [];

  const ctx = document.getElementById('graficoPizza').getContext('2d');
  const chart = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: ['Vitórias', 'Derrotas'],
      datasets: [{
        label: 'Resultados',
        data: [0, 0],
        backgroundColor: ['#4caf50', '#f44336']
      }]
    },
    options: {
      responsive: true
    }
  });

  function testarRodada() {
    fetch('api.php')
      .then(res => res.json())
      .then(data => {
        const venceu = data.win === true;
        total++;
        if (venceu) vitorias++; else derrotas++;

        ultimos.unshift(venceu ? '✅ Vitória' : '❌ Derrota');
        if (ultimos.length > 10) ultimos.pop();

        historicoCSV.push([new Date().toLocaleString(), venceu ? 'Vitória' : 'Derrota']);

        atualizarEstatisticas();
      })
      .catch(err => {
        alert("Erro ao consultar API");
        console.error(err);
      });
  }

  function atualizarEstatisticas() {
    document.getElementById('total').innerText = total;
    document.getElementById('vitorias').innerText = vitorias;
    document.getElementById('derrotas').innerText = derrotas;
    document.getElementById('resultados').innerHTML =
      ultimos.map(r => `<div class="resultado">${r}</div>`).join('');

    chart.data.datasets[0].data = [vitorias, derrotas];
    chart.update();
  }

  function exportarCSV() {
    const header = 'Data,Resultado\n';
    const linhas = historicoCSV.map(l => l.join(',')).join('\n');
    const blob = new Blob([header + linhas], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'historico_raspadinha.csv';
    link.click();
  }
</script>

</body>
</html>
