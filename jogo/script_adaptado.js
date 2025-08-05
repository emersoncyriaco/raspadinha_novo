// Lógica do jogo Fortuna PIX adaptada para comunicação com backend PHP
class FortunaPixGamePHP {
    constructor() {
        this.mainScratchImage = document.querySelector('.main-scratch-image');
        this.mainScratchContainer = document.querySelector('.main-scratch-container');
        this.scratchGrid = document.querySelector('.scratch-grid');
        this.scratchAreas = document.querySelectorAll('.scratch-area');
        this.messageElement = document.querySelector('.message');
        this.playAgainButton = document.querySelector('.play-again-button');
        this.balanceElement = document.querySelector('.balance-button');
        
        this.gameData = null;
        this.isScratched = false;
        
        this.init();
    }

    init() {
        this.setupMainScratchArea();
        this.setupPlayAgainButton();
        this.loadGameFromBackend();
    }

    setupMainScratchArea() {
        this.mainScratchImage.addEventListener('click', () => this.scratchMainArea());
    }

    setupPlayAgainButton() {
        this.playAgainButton.addEventListener('click', () => this.resetGame());
    }

    async loadGameFromBackend() {
        try {
            // Obtém os parâmetros da URL para manter compatibilidade
            const urlParams = new URLSearchParams(window.location.search);
            const tipo = urlParams.get('raspadinha') || 'esperanca';
            const valor = urlParams.get('valor') || '1.00';
            
            const response = await fetch(`jogar.php?raspadinha=${tipo}&valor=${valor}`);
            const data = await response.json();
            
            if (data.erro) {
                this.showError(data.erro);
                return;
            }
            
            this.gameData = data;
            console.log('Dados do jogo carregados:', this.gameData);
            
        } catch (error) {
            console.error('Erro ao carregar dados do jogo:', error);
            this.showError('Erro ao carregar o jogo. Tente novamente.');
        }
    }

    showError(message) {
        this.messageElement.textContent = message;
        this.messageElement.style.color = '#EF4444';
        this.messageElement.parentElement.style.display = 'block';
    }

    scratchMainArea() {
        if (this.isScratched || !this.gameData) return;
        
        this.isScratched = true;

        // Adiciona efeito de "raspagem" na imagem principal
        this.mainScratchImage.style.opacity = '0.3';
        this.mainScratchImage.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            // Esconde a imagem principal e mostra o grid com todos os resultados
            this.mainScratchContainer.style.display = 'none';
            this.scratchGrid.classList.remove('hidden');
            this.scratchGrid.classList.add('visible');
            
            // Revela todos os resultados imediatamente
            this.revealAllResults();
            
            // Mostra o resultado final após um pequeno delay
            setTimeout(() => {
                this.showFinalResult();
            }, 800);
            
        }, 300);
    }

    revealAllResults() {
        if (!this.gameData || !this.gameData.simbolos) {
            console.error('Dados do jogo não disponíveis');
            return;
        }

        this.scratchAreas.forEach((area, index) => {
            if (index < this.gameData.simbolos.length) {
                // Para compatibilidade, assumimos que os símbolos são strings ou emojis
                const result = this.gameData.simbolos[index];
                area.textContent = result;
                
                // Aplica classes CSS baseadas no tipo de resultado
                if (typeof result === 'string' && result.startsWith('R$')) {
                    area.classList.add('prize');
                } else if (this.isEmoji(result)) {
                    area.classList.add('emoji');
                } else {
                    area.classList.add('nothing');
                }
                
                // Adiciona animação escalonada
                setTimeout(() => {
                    area.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        area.style.transform = 'scale(1)';
                    }, 200);
                }, index * 100);
            }
        });
    }

    isEmoji(str) {
        const emojiRegex = /[\u{1F600}-\u{1F64F}]|[\u{1F300}-\u{1F5FF}]|[\u{1F680}-\u{1F6FF}]|[\u{1F1E0}-\u{1F1FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]/u;
        return emojiRegex.test(str);
    }

    async showFinalResult() {
        if (!this.gameData) return;

        // Exibe a mensagem do backend
        if (this.gameData.mensagem) {
            this.messageElement.textContent = this.gameData.mensagem;
            
            if (this.gameData.ganhou) {
                this.messageElement.style.color = '#22C55E';
            } else {
                this.messageElement.style.color = '#EF4444';
            }
        }
        
        this.messageElement.parentElement.style.display = 'block';
        this.playAgainButton.classList.add('visible');

        // Registra a jogada no backend
        await this.registerGame();
    }

    async registerGame() {
        if (!this.gameData) return;

        try {
            const response = await fetch('registrar_jogada.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    simbolos: this.gameData.simbolos,
                    ganhou: this.gameData.ganhou,
                    premio: this.gameData.premio,
                    aposta: this.gameData.aposta || 1.00
                })
            });

            const result = await response.json();
            
            if (result.saldo !== undefined) {
                this.updateBalance(result.saldo);
            }
            
        } catch (error) {
            console.error('Erro ao registrar jogada:', error);
        }
    }

    updateBalance(newBalance) {
        if (this.balanceElement) {
            this.balanceElement.textContent = `Saldo: R$ ${newBalance}`;
            this.balanceElement.classList.add('animate-pulse');
            setTimeout(() => this.balanceElement.classList.remove('animate-pulse'), 1000);
        }
    }

    resetGame() {
        // Reset das variáveis
        this.isScratched = false;
        
        // Reset da imagem principal
        this.mainScratchImage.style.opacity = '1';
        this.mainScratchImage.style.transform = 'scale(1)';
        this.mainScratchContainer.style.display = 'flex';
        
        // Reset do grid
        this.scratchGrid.classList.add('hidden');
        this.scratchGrid.classList.remove('visible');
        
        // Reset das áreas individuais
        this.scratchAreas.forEach(area => {
            area.classList.remove('prize', 'emoji', 'nothing');
            area.textContent = '';
            area.style.transform = 'scale(1)';
        });
        
        // Reset da mensagem e botão
        this.messageElement.textContent = '';
        this.messageElement.style.color = 'white';
        this.messageElement.parentElement.style.display = 'none';
        this.playAgainButton.classList.remove('visible');
        
        // Carrega novos dados do backend
        this.loadGameFromBackend();
    }
}

// Inicializa o jogo quando a página carrega
document.addEventListener('DOMContentLoaded', () => {
    new FortunaPixGamePHP();
});

