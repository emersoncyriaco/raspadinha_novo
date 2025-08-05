// Lógica do jogo Fortuna PIX com raspagem gradual realística
// Versão apenas notas: todas as 9 posições sempre exibem notas de dinheiro
class FortunaPixGame {
    constructor() {
        this.mainScratchContainer = document.querySelector(".main-scratch-container");
        this.scratchGrid = document.querySelector(".scratch-grid");
        this.scratchAreas = document.querySelectorAll(".scratch-area");
        this.messageElement = document.querySelector(".message");
        this.playAgainButton = document.querySelector(".play-again-button");
        this.balanceButton = document.querySelector(".balance-button"); // Adicionado para o saldo
        
        this.gameResults = [];
        this.hasWinner = false;
        this.winningPrize = null;
        this.winningNote = null;
        this.isMainScratched = false;
        
        // Configurações da raspagem
        this.scratchRadius = 25;
        this.scratchThreshold = 0.8; // 80% da área deve ser raspada
        
        // Mapeamento de notas de dinheiro
        this.moneyNotes = {
            "2": "./assets/money_notes/2REAIS.jpg",
            "3": "./assets/money_notes/3REAIS.jpg", 
            "5": "./assets/money_notes/5REAIS.png",
            "10": "./assets/money_notes/10REAIS.png",
            "50": "./assets/money_notes/50REAIS.png"
        };
        
        this.init();
    }

    init() {
        this.setupMainScratchArea();
        this.setupPlayAgainButton();
        this.generateGameResults();
        this.fetchBalance(); // Busca o saldo inicial
    }

    setupMainScratchArea() {
        // Cria canvas para a raspagem principal
        this.createMainScratchCanvas();
    }

    createMainScratchCanvas() {
        const container = this.mainScratchContainer;
        const canvas = document.createElement("canvas");
        const ctx = canvas.getContext("2d");
        
        canvas.className = "scratch-canvas";
        canvas.style.position = "absolute";
        canvas.style.top = "0";
        canvas.style.left = "0";
        canvas.style.cursor = "crosshair";
        canvas.style.zIndex = "10";
        
        // Ajusta o tamanho do canvas
        const rect = container.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;
        canvas.style.width = rect.width + "px";
        canvas.style.height = rect.height + "px";
        
        // Desenha a camada de raspagem
        ctx.fillStyle = "#8B5CF6";
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Adiciona padrão de raspagem
        this.drawScratchPattern(ctx, canvas.width, canvas.height);
        
        // Adiciona texto "RASPE AQUI"
        ctx.fillStyle = "white";
        ctx.font = "bold 24px Arial";
        ctx.textAlign = "center";
        ctx.fillText("RASPE AQUI", canvas.width / 2, canvas.height / 2);
        
        container.appendChild(canvas);
        
        this.mainCanvas = canvas;
        this.mainCtx = ctx;
        this.setupMainScratchEvents();
    }

    drawScratchPattern(ctx, width, height) {
        // Cria padrão diagonal para simular área de raspagem
        ctx.strokeStyle = "rgba(255, 255, 255, 0.1)";
        ctx.lineWidth = 2;
        
        for (let i = 0; i < width + height; i += 10) {
            ctx.beginPath();
            ctx.moveTo(i, 0);
            ctx.lineTo(i - height, height);
            ctx.stroke();
        }
    }

    setupMainScratchEvents() {
        let isScratching = false;
        let scratchedPixels = 0;
        const totalPixels = this.mainCanvas.width * this.mainCanvas.height;
        
        // Eventos para mouse
        this.mainCanvas.addEventListener("mousedown", (e) => {
            isScratching = true;
            this.scratch(e, "mouse");
        });
        
        this.mainCanvas.addEventListener("mousemove", (e) => {
            if (isScratching) {
                scratchedPixels += this.scratch(e, "mouse");
                this.checkMainScratchProgress(scratchedPixels, totalPixels);
            }
        });
        
        this.mainCanvas.addEventListener("mouseup", () => {
            isScratching = false;
        });
        
        // Eventos para touch (mobile)
        this.mainCanvas.addEventListener("touchstart", (e) => {
            e.preventDefault();
            isScratching = true;
            this.scratch(e.touches[0], "touch");
        });
        
        this.mainCanvas.addEventListener("touchmove", (e) => {
            e.preventDefault();
            if (isScratching) {
                scratchedPixels += this.scratch(e.touches[0], "touch");
                this.checkMainScratchProgress(scratchedPixels, totalPixels);
            }
        });
        
        this.mainCanvas.addEventListener("touchend", (e) => {
            e.preventDefault();
            isScratching = false;
        });
    }

    scratch(event, type) {
        const rect = this.mainCanvas.getBoundingClientRect();
        let x, y;
        
        if (type === "mouse") {
            x = event.clientX - rect.left;
            y = event.clientY - rect.top;
        } else {
            x = event.clientX - rect.left;
            y = event.clientY - rect.top;
        }
        
        // Ajusta coordenadas para o canvas real
        x = x * (this.mainCanvas.width / rect.width);
        y = y * (this.mainCanvas.height / rect.height);
        
        // Remove a área raspada
        this.mainCtx.globalCompositeOperation = "destination-out";
        this.mainCtx.beginPath();
        this.mainCtx.arc(x, y, this.scratchRadius, 0, 2 * Math.PI);
        this.mainCtx.fill();
        
        // Retorna área aproximada raspada
        return Math.PI * this.scratchRadius * this.scratchRadius;
    }

    checkMainScratchProgress(scratchedPixels, totalPixels) {
        const progress = scratchedPixels / totalPixels;
        
        if (progress >= this.scratchThreshold && !this.isMainScratched) {
            this.isMainScratched = true;
            this.revealMainArea();
        }
    }

    revealMainArea() {
        // Remove o canvas de raspagem
        this.mainCanvas.style.opacity = "0";
        
        setTimeout(() => {
            this.mainScratchContainer.style.display = "none";
            this.scratchGrid.classList.remove("hidden");
            this.scratchGrid.classList.add("visible");
            
            // Revela todos os prêmios automaticamente
            this.revealAllPrizes();
        }, 500);
    }

    revealAllPrizes() {
        this.scratchAreas.forEach((area, index) => {
            const result = this.gameResults[index];
            
            // Animação escalonada para cada área
            setTimeout(() => {
                // Todas as posições agora sempre exibem notas de dinheiro
                if (Object.keys(this.moneyNotes).includes(result)) {
                    // Cria imagem da nota
                    const img = document.createElement("img");
                    img.src = this.moneyNotes[result];
                    img.alt = `Nota de R$ ${result}`;
                    
                    area.innerHTML = "";
                    area.appendChild(img);
                    area.classList.add("money-note");
                }
                
                // Animação de revelação
                area.style.transform = "scale(1.1)";
                setTimeout(() => {
                    area.style.transform = "scale(1)";
                }, 200);
                
            }, index * 150); // Delay escalonado de 150ms entre cada área
        });
        
        // Mostra o resultado final após todas as áreas serem reveladas
        setTimeout(() => {
            this.showFinalResult();
        }, 9 * 150 + 500); // Aguarda todas as animações + 500ms extra
    }

    setupPlayAgainButton() {
        this.playAgainButton.addEventListener("click", () => this.resetGame());
    }

    generateGameResults() {
        const possibleNotes = ["2", "3", "5", "10", "50"]; // Notas de dinheiro disponíveis
        
        this.gameResults = [];
        this.hasWinner = false;
        this.winningPrize = null;
        this.winningNote = null;

        // Decide se haverá um prêmio (chance de 30% de ter 3 iguais)
        const shouldWin = Math.random() < 0.3;

        if (shouldWin) {
            // Escolhe a nota vencedora
            const winningNote = possibleNotes[Math.floor(Math.random() * possibleNotes.length)];
            this.winningNote = winningNote;
            this.hasWinner = true;
            
            // Calcula o prêmio dinamicamente: valor da nota × 3
            const noteValue = parseInt(winningNote);
            this.winningPrize = `R$ ${(noteValue * 3).toFixed(2).replace(".", ",")}`;

            // Cria array com exatamente 3 notas vencedoras em posições aleatórias
            const winningPositions = [];
            while (winningPositions.length < 3) {
                const randomPos = Math.floor(Math.random() * 9);
                if (!winningPositions.includes(randomPos)) {
                    winningPositions.push(randomPos);
                }
            }

            // Preenche as 9 posições
            for (let i = 0; i < 9; i++) {
                if (winningPositions.includes(i)) {
                    // Posição vencedora: coloca a nota vencedora
                    this.gameResults.push(winningNote);
                } else {
                    // Posição não vencedora: coloca placeholder temporário
                    this.gameResults.push("placeholder");
                }
            }

            // Preenche as posições não vencedoras apenas com notas de dinheiro
            this.fillNonWinningPositionsOnlyNotes(winningNote, winningPositions);

        } else {
            // Sem vitória: preenche com notas aleatórias, garantindo que não haja 3 iguais
            for (let i = 0; i < 9; i++) {
                let note = possibleNotes[Math.floor(Math.random() * possibleNotes.length)];
                this.gameResults.push(note);
            }
            // Verifica e corrige se acidentalmente gerou 3 iguais
            this.ensureNoThreeIdenticalOnlyNotes();
        }
    }

    fillNonWinningPositionsOnlyNotes(winningNote, winningPositions) {
        const possibleNotes = ["2", "3", "5", "10", "50"];
        
        // Remove a nota vencedora das opções para evitar conflitos
        const availableNotes = possibleNotes.filter(note => note !== winningNote);
        
        // Contador para controlar quantas vezes cada nota aparece
        const noteCounts = {};
        availableNotes.forEach(note => noteCounts[note] = 0);
        
        // Preenche as posições não vencedoras
        for (let i = 0; i < 9; i++) {
            if (!winningPositions.includes(i)) {
                // Filtra notas que ainda podem ser usadas (aparecem menos de 2 vezes)
                const availableNotesForPosition = availableNotes.filter(note => noteCounts[note] < 2);
                
                if (availableNotesForPosition.length > 0) {
                    const selectedNote = availableNotesForPosition[Math.floor(Math.random() * availableNotesForPosition.length)];
                    this.gameResults[i] = selectedNote;
                    noteCounts[selectedNote]++;
                } else {
                    // Se todas as notas já apareceram 2 vezes, escolhe uma aleatória
                    // (isso é raro, mas garante que sempre seja uma nota)
                    const randomNote = availableNotes[Math.floor(Math.random() * availableNotes.length)];
                    this.gameResults[i] = randomNote;
                }
            }
        }
    }

    ensureNoThreeIdenticalOnlyNotes() {
        let needsReshuffle = true;
        const possibleNotes = ["2", "3", "5", "10", "50"];
        
        while (needsReshuffle) {
            needsReshuffle = false;
            const counts = {};
            for (const note of this.gameResults) {
                counts[note] = (counts[note] || 0) + 1;
                if (counts[note] >= 3) {
                    needsReshuffle = true;
                    // Se encontrou 3 iguais, troca um deles para uma nota diferente
                    const indexToChange = this.gameResults.lastIndexOf(note);
                    
                    let newNote = possibleNotes[Math.floor(Math.random() * possibleNotes.length)];
                    while (newNote === note) {
                        newNote = possibleNotes[Math.floor(Math.random() * possibleNotes.length)];
                    }
                    this.gameResults[indexToChange] = newNote;
                    break; // Sai do loop interno e verifica novamente
                }
            }
        }
    }

    checkWinCondition() {
        const counts = {};
        for (const note of this.gameResults) {
            counts[note] = (counts[note] || 0) + 1;
            if (counts[note] >= 3) {
                this.hasWinner = true;
                this.winningNote = note;
                
                // Calcula o prêmio dinamicamente baseado no valor da nota
                if (Object.keys(this.moneyNotes).includes(note)) {
                    const noteValue = parseInt(note);
                    this.winningPrize = (noteValue * 3).toFixed(2);
                } else {
                    // Caso seja algum outro tipo de resultado (não deveria acontecer)
                    this.winningPrize = "0.00";
                }
                return;
            }
        }
        this.hasWinner = false;
    }

    showFinalResult() {
        this.checkWinCondition(); // Verifica a condição de vitória antes de mostrar o resultado

        if (this.hasWinner) {
            this.messageElement.textContent = `🎉 Parabéns! Você ganhou R$ ${this.winningPrize.replace(".", ",")}! 🎉`;
            this.messageElement.style.color = "#22C55E";
        } else {
            this.messageElement.textContent = "Não foi dessa vez. 😔";
            this.messageElement.style.color = "#EF4444";
        }
        
        this.playAgainButton.classList.add("visible");
        this.sendGameResult(this.hasWinner, parseFloat(this.winningPrize)); // Envia o resultado para o backend
    }

    resetGame() {
        // Reset das variáveis
        this.hasWinner = false;
        this.winningPrize = null;
        this.winningNote = null;
        this.isMainScratched = false;
        
        // Remove canvas existentes
        const existingCanvases = document.querySelectorAll(".scratch-canvas, .individual-scratch-canvas");
        existingCanvases.forEach(canvas => canvas.remove());
        
        // Reset da interface
        this.mainScratchContainer.style.display = "flex";
        this.scratchGrid.classList.add("hidden");
        this.scratchGrid.classList.remove("visible");
        
        // Reset das áreas individuais
        this.scratchAreas.forEach(area => {
            area.classList.remove("prize", "money-note", "nothing");
            area.innerHTML = "RASPE AQUI";
            area.style.transform = "scale(1)";
            area.style.position = "static";
        });
        
        // Reset da mensagem e botão
        this.messageElement.textContent = "";
        this.messageElement.style.color = "white";
        this.playAgainButton.classList.remove("visible");
        
        // Gera novos resultados e recria a área principal
        this.generateGameResults();
        this.createMainScratchCanvas();
    }

    // Função para enviar o resultado do jogo para o backend
    sendGameResult(ganhou, premio) {
        fetch("registrar_jogada.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ ganhou: ganhou, premio: premio }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.saldo) {
                this.updateBalance(data.saldo); // Atualiza o saldo na interface
            }
        })
        .catch(error => {
            console.error("Erro ao registrar jogada:", error);
        });
    }

    // Função para buscar e exibir o saldo atual
    fetchBalance() {
        fetch("get_balance.php") // Você precisará criar este arquivo PHP
            .then(response => response.json())
            .then(data => {
                if (data.saldo) {
                    this.updateBalance(data.saldo);
                }
            })
            .catch(error => {
                console.error("Erro ao buscar saldo:", error);
            });
    }

    // Função para atualizar o saldo na interface
    updateBalance(saldo) {
        this.balanceButton.textContent = `Saldo: R$ ${saldo}`;
    }
}

// Inicializa o jogo quando a página carrega
document.addEventListener("DOMContentLoaded", () => {
    new FortunaPixGame();
});


