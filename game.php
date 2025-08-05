<?php
require 'includes/db.php';
require 'includes/auth.php';

$userId = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$saldo = $usuario['balance'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Raspadinha Premium - Jogue e Ganhe</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            'inter': ['Inter', 'sans-serif'],
          },
          animation: {
            'fade-in': 'fadeIn 0.5s ease-in-out',
            'slide-up': 'slideUp 0.6s ease-out',
            'bounce-gentle': 'bounceGentle 2s infinite',
            'pulse-glow': 'pulseGlow 2s ease-in-out infinite alternate',
            'shake': 'shake 0.5s ease-in-out',
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: '0', transform: 'translateY(10px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' }
            },
            slideUp: {
              '0%': { opacity: '0', transform: 'translateY(30px)' },
              '100%': { opacity: '1', transform: 'translateY(0)' }
            },
            bounceGentle: {
              '0%, 100%': { transform: 'translateY(0)' },
              '50%': { transform: 'translateY(-5px)' }
            },
            pulseGlow: {
              '0%': { boxShadow: '0 0 20px rgba(234, 179, 8, 0.5)' },
              '100%': { boxShadow: '0 0 40px rgba(234, 179, 8, 0.8)' }
            },
            shake: {
              '0%, 100%': { transform: 'translateX(0)' },
              '25%': { transform: 'translateX(-5px)' },
              '75%': { transform: 'translateX(5px)' }
            }
          },
          backgroundImage: {
            'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
            'gradient-conic': 'conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))',
          }
        }
      }
    }
  </script>
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
    
    .scratch-canvas {
      cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>'), auto;
      touch-action: none;
      user-select: none;
      background-color: transparent;
      width: 100%;
      height: 100%;
      display: block;
      border-radius: 12px;
    }
    
    .scratch-canvas:active {
      cursor: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="red" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12h8"/><path d="M12 8v8"/></svg>'), auto;
    }
    
    /* Classe para borrar os símbolos - agora com transição mais suave */
    .symbol-image {
      transition: filter 0.1s ease-out;
    }
    
    .glass-effect {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .card-hover {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    
    .btn-primary::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }
    
    .btn-primary:hover::before {
      left: 100%;
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(245, 158, 11, 0.4);
    }
    
    .btn-secondary {
      background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
      transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(220, 38, 38, 0.4);
    }
    
    .scratch-area {
      position: relative;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
      transition: all 0.3s ease;
    }
    
    .scratch-area:hover {
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
    }
    
    .loading-spinner {
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top: 3px solid #f59e0b;
      width: 24px;
      height: 24px;
      animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .sr-only {
      position: absolute !important;
      width: 1px !important;
      height: 1px !important;
      padding: 0 !important;
      margin: -1px !important;
      overflow: hidden !important;
      clip: rect(0,0,0,0) !important;
      border: 0 !important;
    }
    
    @media (max-width: 640px) {
      .scratch-canvas {
        cursor: pointer;
      }
    }
  </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 text-white font-inter">
  <!-- Background Pattern -->
  <div class="fixed inset-0 opacity-10">
    <div class="absolute inset-0 bg-gradient-conic from-purple-500 via-blue-500 to-purple-500 animate-spin" style="animation-duration: 20s;"></div>
  </div>
  
  <!-- Main Container -->
  <div class="relative z-10 min-h-screen flex flex-col items-center justify-center p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <header class="text-center mb-8 animate-fade-in">
      <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold bg-gradient-to-r from-yellow-400 via-orange-500 to-red-500 bg-clip-text text-transparent mb-2">
       
    </header>

    <!-- Main Game Card -->
    <main class="w-full max-w-2xl">
      <div class="glass-effect rounded-3xl p-6 sm:p-8 lg:p-10 shadow-2xl card-hover animate-slide-up">
        <!-- User Info -->
        <section class="text-center mb-8">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-xl font-bold">
                <?= strtoupper(substr($usuario['name'], 0, 1)) ?>
              </div>
              <div class="text-left">
                <p class="text-sm text-gray-400">Bem-vindo de volta</p>
                <p class="font-semibold text-lg"><?= htmlspecialchars($usuario['name']) ?></p>
              </div>
            </div>
            
            <div class="text-center sm:text-right">
              <p class="text-sm text-gray-400 mb-1">Saldo Disponível</p>
              <p id="saldo" class="text-2xl sm:text-3xl font-bold text-green-400 animate-pulse-glow">
                R$ <?= number_format($saldo, 2, ',', '.') ?>
              </p>
            </div>
          </div>
        </section>

        <!-- Game Controls -->
        <section class="text-center mb-8">
          <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <button id="jogar" class="btn-primary text-black font-bold py-4 px-8 rounded-2xl shadow-lg text-lg w-full sm:w-auto min-w-[200px] relative overflow-hidden">
              <span class="relative z-10 flex items-center justify-center gap-2">
                <span class="text-2xl">🎲</span>
                Jogar R$ 1,00
              </span>
              <div class="absolute bottom-0 left-0 right-0 text-xs py-1 bg-black bg-opacity-20 text-white">
                
              </div>
            </button>

            <button id="revelar" class="btn-secondary text-white font-semibold py-3 px-6 rounded-xl shadow-md opacity-80 hover:opacity-100 w-full sm:w-auto" 
                    title="Revelar resultado direto" disabled>
              <span class="flex items-center justify-center gap-2">
                <span class="text-lg">👁️</span>
                Revelar Resultado
              </span>
            </button>
          </div>
        </section>

        <!-- Game Area -->
        <section id="raspadinha" class="mb-8 min-h-[200px] flex items-center justify-center">
          <div class="text-center text-gray-400">
            <div class="text-6xl mb-4 animate-bounce-gentle">🎯</div>
            <p class="text-lg">Clique em "Jogar" para começar!</p>
            <p class="text-sm mt-2 opacity-75">Raspe as cartas para revelar os símbolos gradualmente</p>
          </div>
        </section>

        <!-- Result Message -->
        <section id="mensagem" class="text-center mb-6 hidden">
          <div class="p-6 rounded-2xl glass-effect">
            <p class="text-2xl sm:text-3xl font-bold"></p>
          </div>
        </section>

        <!-- Game Instructions -->
       

        <!-- Footer -->
        <footer class="text-center pt-6 border-t border-gray-700">
          <a href="logout.php" class="text-blue-400 hover:text-blue-300 transition-colors underline-offset-4 hover:underline">
            🚪 Sair da Conta
          </a>
        </footer>
      </div>
    </main>
  </div>

  <!-- Audio Elements -->
  <audio id="somRaspar" src="assets/audio/raspar.mp3" preload="auto"></audio>
  <audio id="somGanhou" src="assets/audio/ganhou.mp3" preload="auto"></audio>
  <audio id="somPerdeu" src="assets/audio/perdeu.mp3" preload="auto"></audio>

  <script>
    // DOM Elements
    const btnJogar = document.getElementById("jogar");
    const btnRevelar = document.getElementById("revelar");
    const raspadinhaContainer = document.getElementById("raspadinha");
    const mensagem = document.getElementById("mensagem");
    const saldoHTML = document.getElementById("saldo");
    const somRaspar = document.getElementById("somRaspar");
    const somGanhou = document.getElementById("somGanhou");
    const somPerdeu = document.getElementById("somPerdeu");

    // Game State
    let dataJogoAtual = null;
    let isGameActive = false;

    // Utility Functions
    function showLoading() {
      raspadinhaContainer.innerHTML = `
        <div class="text-center animate-fade-in">
          <div class="loading-spinner mx-auto mb-4"></div>
          <p class="text-yellow-400 font-semibold text-lg">Preparando sua raspadinha...</p>
          <p class="text-gray-400 text-sm mt-2">Aguarde um momento</p>
        </div>
      `;
    }

    function showError(message) {
      raspadinhaContainer.innerHTML = `
        <div class="text-center animate-shake">
          <div class="text-6xl mb-4">❌</div>
          <p class="text-red-400 font-bold text-lg">${message}</p>
        </div>
      `;
    }

    function updateBalance(newBalance) {
      saldoHTML.textContent = `R$ ${newBalance}`;
      saldoHTML.classList.add('animate-pulse');
      setTimeout(() => saldoHTML.classList.remove('animate-pulse'), 1000);
    }

    // Enhanced Scratch Functionality with Gradual Blur Removal
    function iniciarRaspagem() {
      const canvases = document.querySelectorAll("canvas.scratch-canvas");
      const images = document.querySelectorAll(".symbol-image");
      let liberados = 0;
      const raspadoCompleto = new Array(canvases.length).fill(false);
      let isScratching = false;

      canvases.forEach((canvas, index) => {
        const ctx = canvas.getContext("2d");
        const correspondingImage = images[index];

        // Set canvas size
        canvas.width = canvas.clientWidth * window.devicePixelRatio;
        canvas.height = canvas.clientHeight * window.devicePixelRatio;
        ctx.scale(window.devicePixelRatio, window.devicePixelRatio);

        const w = canvas.clientWidth;
        const h = canvas.clientHeight;

        // Create scratch surface with gradient
        const gradient = ctx.createLinearGradient(0, 0, w, h);
        gradient.addColorStop(0, '#c0c0c0');
        gradient.addColorStop(0.5, '#a0a0a0');
        gradient.addColorStop(1, '#808080');
        
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, w, h);
        
        // Add texture pattern
        ctx.fillStyle = 'rgba(255, 255, 255, 0.1)';
        for (let i = 0; i < w; i += 4) {
          for (let j = 0; j < h; j += 4) {
            if (Math.random() > 0.7) {
              ctx.fillRect(i, j, 2, 2);
            }
          }
        }

        ctx.globalCompositeOperation = 'destination-out';

        // Initialize image with full blur
        correspondingImage.style.filter = 'blur(10px)';

        const raspar = (x, y, pressure = 1) => {
          const radius = Math.max(30, 50 * pressure);
          ctx.beginPath();
          ctx.arc(x, y, radius, 0, Math.PI * 2);
          ctx.fill();
          
          // Play scratch sound with throttling
          if (somRaspar.paused || somRaspar.currentTime > 0.1) {
            somRaspar.currentTime = 0;
            somRaspar.play().catch(() => {});
          }

          // Update blur based on scratched percentage immediately
          updateBlurBasedOnProgress(canvas, correspondingImage, index);
        };

        const updateBlurBasedOnProgress = (canvas, image, cardIndex) => {
          const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
          const pixels = imgData.data;
          const total = pixels.length / 4;
          let apagados = 0;

          for (let i = 0; i < pixels.length; i += 4) {
            if (pixels[i + 3] < 128) apagados++;
          }

          const percentual = apagados / total;
          
          // Calculate blur amount based on percentage (10px to 0px)
          const maxBlur = 10;
          const blurAmount = Math.max(0, maxBlur * (1 - percentual));
          
          // Apply the calculated blur
          image.style.filter = `blur(${blurAmount}px)`;

          // Check if card is fully revealed (90% or more scratched)
          if (percentual >= 0.9 && !raspadoCompleto[cardIndex]) {
            raspadoCompleto[cardIndex] = true;
            liberados++;
            
            // Ensure image is completely clear
            image.style.filter = 'blur(0px)';
            
            // Visual feedback for the card
            const cardElement = canvas.closest('.scratch-area');
            cardElement.style.border = "3px solid #22c55e";
            cardElement.style.boxShadow = "0 0 20px rgba(34, 197, 94, 0.5)";
            
            // Check if all cards are revealed
            if (liberados === canvases.length) {
              setTimeout(mostrarMensagemResultado, 500);
            }
          }
        };

        const getCoordinates = (e) => {
          const rect = canvas.getBoundingClientRect();
          let x, y, pressure = 1;
          
          if (e.touches && e.touches.length) {
            x = e.touches[0].clientX - rect.left;
            y = e.touches[0].clientY - rect.top;
            pressure = e.touches[0].force || 1;
          } else {
            x = e.clientX - rect.left;
            y = e.clientY - rect.top;
            pressure = e.pressure || 1;
          }
          
          return { x, y, pressure };
        };

        // Mouse Events
        canvas.addEventListener("mousedown", (e) => {
          isScratching = true;
          const coords = getCoordinates(e);
          raspar(coords.x, coords.y, coords.pressure);
        });

        canvas.addEventListener("mouseup", () => {
          isScratching = false;
        });

        canvas.addEventListener("mouseleave", () => {
          isScratching = false;
        });

        canvas.addEventListener("mousemove", (e) => {
          if (isScratching) {
            const coords = getCoordinates(e);
            raspar(coords.x, coords.y, coords.pressure);
          }
        });

        // Touch Events
        canvas.addEventListener("touchstart", (e) => {
          isScratching = true;
          const coords = getCoordinates(e);
          raspar(coords.x, coords.y, coords.pressure);
          e.preventDefault();
        }, { passive: false });

        canvas.addEventListener("touchend", () => {
          isScratching = false;
        });

        canvas.addEventListener("touchcancel", () => {
          isScratching = false;
        });

        canvas.addEventListener("touchmove", (e) => {
          if (isScratching) {
            const coords = getCoordinates(e);
            raspar(coords.x, coords.y, coords.pressure);
          }
          e.preventDefault();
        }, { passive: false });
      });
    }

    function mostrarMensagemResultado() {
      if (!dataJogoAtual || !dataJogoAtual.mensagem) return;

      const mensagemElement = mensagem.querySelector('p');
      mensagemElement.textContent = dataJogoAtual.mensagem;
      
      mensagem.classList.remove("hidden");
      mensagem.classList.add("animate-fade-in");

      // Play appropriate sound and add visual effects
      if (dataJogoAtual.mensagem.includes("Parabéns")) {
        mensagem.querySelector('div').classList.add('bg-green-500', 'bg-opacity-20', 'border-green-500');
        mensagemElement.classList.add('text-green-400');
        somGanhou.play().catch(() => {});
        
        // Celebration effect
        createConfetti();
      } else {
        mensagem.querySelector('div').classList.add('bg-red-500', 'bg-opacity-20', 'border-red-500');
        mensagemElement.classList.add('text-red-400');
        somPerdeu.play().catch(() => {});
      }

      // Register the game result
      registrarJogada();
    }

    function createConfetti() {
      // Simple confetti effect
      const colors = ['#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#8b5cf6'];
      for (let i = 0; i < 50; i++) {
        setTimeout(() => {
          const confetti = document.createElement('div');
          confetti.style.cssText = `
            position: fixed;
            width: 10px;
            height: 10px;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            left: ${Math.random() * 100}vw;
            top: -10px;
            z-index: 1000;
            border-radius: 50%;
            pointer-events: none;
            animation: fall 3s linear forwards;
          `;
          
          document.body.appendChild(confetti);
          setTimeout(() => confetti.remove(), 3000);
        }, i * 50);
      }
    }

    function registrarJogada() {
      if (!dataJogoAtual) return;

      fetch("registrar_jogada.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          simbolos: dataJogoAtual.simbolos,
          ganhou: dataJogoAtual.ganhou,
          premio: dataJogoAtual.premio,
          aposta: 1.00
        })
      })
      .then(res => res.json())
      .then(res => {
        if (res.saldo !== undefined) {
          updateBalance(res.saldo);
        }
      })
      .catch(err => console.error('Erro ao registrar jogada:', err));
    }

    // Event Listeners
    btnJogar.addEventListener("click", async () => {
      if (isGameActive) return;
      
      isGameActive = true;
      btnJogar.disabled = true;
      btnRevelar.disabled = false;
      
      mensagem.classList.add("hidden");
      showLoading();

      try {
        const res = await fetch("jogar.php");
        const data = await res.json();

        if (data.erro) {
          showError(data.erro);
          return;
        }

        dataJogoAtual = data;

        // Create enhanced game interface
        raspadinhaContainer.innerHTML = `
          <div class="grid grid-cols-3 gap-4 sm:gap-6 max-w-2xl mx-auto animate-fade-in">
            ${data.simbolos.map((simbolo, index) => `
              <div class="scratch-area relative rounded-xl overflow-hidden shadow-lg border-2 border-gray-600 transition-all duration-300 hover:border-gray-400">
                <img src="assets/images/${simbolo}" 
                     alt="Símbolo da raspadinha ${index + 1}" 
                     class="symbol-image w-full h-24 sm:h-40 object-cover select-none" 
                     draggable="false" />
                <canvas class="scratch-canvas absolute top-0 left-0 w-full h-full"></canvas>
                
              </div>
            `).join('')}
          </div>
          <div class="text-center mt-6">
            
          </div>
        `;

        iniciarRaspagem();
        
      } catch (error) {
        showError("Erro ao carregar o jogo. Tente novamente.");
        console.error('Erro:', error);
      } finally {
        btnJogar.disabled = false;
        isGameActive = false;
      }
    });

    btnRevelar.addEventListener("click", () => {
      if (!dataJogoAtual) return;
      
      btnRevelar.disabled = true;

      // Clear all canvases and remove blur from all images
      const canvases = document.querySelectorAll("canvas.scratch-canvas");
      const images = document.querySelectorAll(".symbol-image");
      
      canvases.forEach((canvas, index) => {
        const ctx = canvas.getContext("2d");
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Remove blur from corresponding image
        images[index].style.filter = 'blur(0px)';
        
        const cardElement = canvas.closest('.scratch-area');
        cardElement.style.border = "3px solid #22c55e";
        cardElement.style.boxShadow = "0 0 20px rgba(34, 197, 94, 0.5)";
      });

      setTimeout(mostrarMensagemResultado, 300);
    });

    // Add CSS for confetti animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes fall {
        0% { transform: translateY(-10px) rotate(0deg); opacity: 1; }
        100% { transform: translateY(100vh) rotate(360deg); opacity: 0; }
      }
    `;
    document.head.appendChild(style);

    // Initialize page
    document.addEventListener('DOMContentLoaded', () => {
      // Add some initial animations
      setTimeout(() => {
        document.body.classList.add('animate-fade-in');
      }, 100);
    });
  </script>
</body>
</html>

