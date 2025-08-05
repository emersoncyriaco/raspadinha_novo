<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depoimentos de Ganhadores - Raspadinhas Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url(\'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap\');
        
        body {
            font-family: \'Inter\', sans-serif;
        }
        
        .testimonial-card {
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(108, 102, 202, 0.2);
        }
        
        .avatar-glow {
            box-shadow: 0 0 20px rgba(108, 102, 202, 0.3);
        }
        
        .gradient-bg {
            background: black;
        }
        
        .star-rating {
            color: #fbbf24;
        }
        
        .verified-badge {
            background: linear-gradient(45deg, #10b981, #059669);
        }
        
        .floating-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .hidden-testimonial {
            display: none;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <!-- Header Section -->
    <div class="text-center py-12 px-4">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                <i class="fas fa-trophy text-yellow-400 mr-3"></i>
                Depoimentos de Ganhadores
            </h1>
            <p class="text-xl text-gray-300 mb-8">
                Histórias reais de pessoas que transformaram suas vidas com nossas raspadinhas
            </p>
            <div class="flex justify-center items-center space-x-6 text-gray-300">
                <div class="flex items-center">
                    <i class="fas fa-users text-blue-400 mr-2"></i>
                    <span>+50.000 jogadores</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-money-bill-wave text-green-400 mr-2"></i>
                    <span>R$ 2.5M pagos</span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-clock text-green-400 mr-2"></i>
                    <span>Pix instantâneo</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Testimonials Grid -->
    <div class="max-w-7xl mx-auto px-4 pb-16">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3" id="testimonials-container">
            
            <!-- Depoimento 1 -->
            <div class="testimonial-card bg-slate-800/80 p-6 rounded-2xl shadow-xl border border-slate-700/50 floating-animation">
                <div class="flex items-start gap-4 mb-4">
                    <div class="relative">
                        <img src="https://i.pravatar.cc/80?img=1" class="w-16 h-16 rounded-full avatar-glow" alt="Avatar Ana Paula">
                        <div class="absolute -bottom-1 -right-1 verified-badge w-6 h-6 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-white">Ana Paula</h3>
                            <span class="text-xs bg-blue-600 text-white px-2 py-1 rounded-full">São Paulo</span>
                        </div>
                        <div class="flex items-center gap-1 mb-2">
                            <div class="star-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="text-xs text-gray-400 ml-2">Verificado</span>
                        </div>
                    </div>
                </div>
                <blockquote class="text-gray-300 text-sm leading-relaxed mb-4">
                    "No começo, eu estava um pouco cética sobre as raspadinhas online, mas decidi tentar com um valor pequeno. Para minha surpresa, ganhei R$ 500 na minha primeira raspadinha! O processo de saque via Pix foi incrivelmente rápido, o dinheiro caiu na minha conta em menos de 5 minutos."
                </blockquote>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span><i class="fas fa-calendar mr-1"></i>Há 2 dias</span>
                    <span class="text-green-400 font-semibold"><i class="fas fa-money-bill mr-1"></i>Ganhou R$ 500</span>
                </div>
            </div>

            <!-- Depoimento 2 -->
            <div class="testimonial-card bg-slate-800/80 p-6 rounded-2xl shadow-xl border border-slate-700/50 floating-animation" style="animation-delay: 0.5s;">
                <div class="flex items-start gap-4 mb-4">
                    <div class="relative">
                        <img src="https://i.pravatar.cc/80?img=2" class="w-16 h-16 rounded-full avatar-glow" alt="Avatar Ricardo">
                        <div class="absolute -bottom-1 -right-1 verified-badge w-6 h-6 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-white">Ricardo Lima</h3>
                            <span class="text-xs bg-red-600 text-white px-2 py-1 rounded-full">Rio de Janeiro</span>
                        </div>
                        <div class="flex items-center gap-1 mb-2">
                            <div class="star-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="text-xs text-gray-400 ml-2">Verificado</span>
                        </div>
                    </div>
                </div>
                <blockquote class="text-gray-300 text-sm leading-relaxed mb-4">
                    "Sempre gostei de jogos de azar, mas a praticidade das raspadinhas online me chamou a atenção. Com apenas R$ 1, consegui um retorno de R$ 100. A interface é muito intuitiva e a transparência dos resultados é algo que valorizo muito."
                </blockquote>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span><i class="fas fa-calendar mr-1"></i>Há 1 semana</span>
                    <span class="text-green-400 font-semibold"><i class="fas fa-money-bill mr-1"></i>Ganhou R$ 100</span>
                </div>
            </div>

            <!-- Depoimento 3 -->
            <div class="testimonial-card bg-slate-800/80 p-6 rounded-2xl shadow-xl border border-slate-700/50 floating-animation" style="animation-delay: 1s;">
                <div class="flex items-start gap-4 mb-4">
                    <div class="relative">
                        <img src="https://i.pravatar.cc/80?img=3" class="w-16 h-16 rounded-full avatar-glow" alt="Avatar Juliana">
                        <div class="absolute -bottom-1 -right-1 verified-badge w-6 h-6 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-white">Juliana Torres</h3>
                            <span class="text-xs bg-green-600 text-white px-2 py-1 rounded-full">Minas Gerais</span>
                        </div>
                        <div class="flex items-center gap-1 mb-2">
                            <div class="star-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="text-xs text-gray-400 ml-2">Verificado</span>
                        </div>
                    </div>
                </div>
                <blockquote class="text-gray-300 text-sm leading-relaxed mb-4">
                    "Eu estava procurando algo diferente para fazer em uma tarde de sábado e me deparei com este site. Comecei a jogar as raspadinhas e, em poucas horas, acumulei R$ 1.200 em ganhos! É muito mais emocionante e recompensador do que qualquer loteria que já joguei."
                </blockquote>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span><i class="fas fa-calendar mr-1"></i>Há 3 dias</span>
                    <span class="text-green-400 font-semibold"><i class="fas fa-money-bill mr-1"></i>Ganhou R$ 1.200</span>
                </div>
            </div>

            <!-- Depoimento 4 -->
            <div class="testimonial-card bg-slate-800/80 p-6 rounded-2xl shadow-xl border border-slate-700/50 floating-animation" style="animation-delay: 1.5s;">
                <div class="flex items-start gap-4 mb-4">
                    <div class="relative">
                        <img src="https://i.pravatar.cc/80?img=4" class="w-16 h-16 rounded-full avatar-glow" alt="Avatar Felipe">
                        <div class="absolute -bottom-1 -right-1 verified-badge w-6 h-6 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-white">Felipe Rocha</h3>
                            <span class="text-xs bg-purple-600 text-white px-2 py-1 rounded-full">Paraná</span>
                        </div>
                        <div class="flex items-center gap-1 mb-2">
                            <div class="star-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="text-xs text-gray-400 ml-2">Verificado</span>
                        </div>
                    </div>
                </div>
                <blockquote class="text-gray-300 text-sm leading-relaxed mb-4">
                    "Já testei diversos jogos online, mas as raspadinhas deste site são de longe as melhores. A experiência é muito mais dinâmica e os ganhos são frequentes. Ganhei um valor considerável e o saque foi instantâneo, sem burocracia."
                </blockquote>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span><i class="fas fa-calendar mr-1"></i>Há 5 dias</span>
                    <span class="text-green-400 font-semibold"><i class="fas fa-money-bill mr-1"></i>Múltiplos ganhos</span>
                </div>
            </div>

            <!-- Depoimento 5 -->
            <div class="testimonial-card bg-slate-800/80 p-6 rounded-2xl shadow-xl border border-slate-700/50 floating-animation" style="animation-delay: 2s;">
                <div class="flex items-start gap-4 mb-4">
                    <div class="relative">
                        <img src="https://i.pravatar.cc/80?img=5" class="w-16 h-16 rounded-full avatar-glow" alt="Avatar Camila">
                        <div class="absolute -bottom-1 -right-1 verified-badge w-6 h-6 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-white">Camila Souza</h3>
                            <span class="text-xs bg-cyan-600 text-white px-2 py-1 rounded-full">Santa Catarina</span>
                        </div>
                        <div class="flex items-center gap-1 mb-2">
                            <div class="star-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="text-xs text-gray-400 ml-2">Verificado</span>
                        </div>
                    </div>
                </div>
                <blockquote class="text-gray-300 text-sm leading-relaxed mb-4">
                    "Em apenas 10 minutos de jogo, consegui transformar R$ 50 em R$ 250! A simplicidade das raspadinhas é o que mais me agrada. Não precisa de estratégias complexas, é só raspar e ver o resultado. É perfeito para quem tem pouco tempo."
                </blockquote>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span><i class="fas fa-calendar mr-1"></i>Há 1 dia</span>
                    <span class="text-green-400 font-semibold"><i class="fas fa-money-bill mr-1"></i>Ganhou R$ 250</span>
                </div>
            </div>

            <!-- Depoimento 6 -->
            <div class="testimonial-card bg-slate-800/80 p-6 rounded-2xl shadow-xl border border-slate-700/50 floating-animation" style="animation-delay: 2.5s;">
                <div class="flex items-start gap-4 mb-4">
                    <div class="relative">
                        <img src="https://i.pravatar.cc/80?img=6" class="w-16 h-16 rounded-full avatar-glow" alt="Avatar Bruno">
                        <div class="absolute -bottom-1 -right-1 verified-badge w-6 h-6 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-white">Bruno Martins</h3>
                            <span class="text-xs bg-orange-600 text-white px-2 py-1 rounded-full">Bahia</span>
                        </div>
                        <div class="flex items-center gap-1 mb-2">
                            <div class="star-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="text-xs text-gray-400 ml-2">Verificado</span>
                        </div>
                    </div>
                </div>
                <blockquote class="text-gray-300 text-sm leading-relaxed mb-4">
                    "Decidi arriscar um pouco e o resultado foi surpreendente. Consegui um bom dinheiro com as raspadinhas e pude comprar aquele presente especial que minha filha tanto queria. A sensação de poder realizar um desejo com algo que começou como uma brincadeira é indescritível."
                </blockquote>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span><i class="fas fa-calendar mr-1"></i>Há 4 dias</span>
                    <span class="text-green-400 font-semibold"><i class="fas fa-heart mr-1"></i>Presente especial</span>
                </div>
            </div>

            <!-- Depoimento 7 -->
            <div class="testimonial-card bg-slate-800/80 p-6 rounded-2xl shadow-xl border border-slate-700/50 floating-animation hidden-testimonial">
                <div class="flex items-start gap-4 mb-4">
                    <div class="relative">
                        <img src="https://i.pravatar.cc/80?img=7" class="w-16 h-16 rounded-full avatar-glow" alt="Avatar Larissa">
                        <div class="absolute -bottom-1 -right-1 verified-badge w-6 h-6 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-white">Larissa Neves</h3>
                            <span class="text-xs bg-pink-600 text-white px-2 py-1 rounded-full">Ceará</span>
                        </div>
                        <div class="flex items-center gap-1 mb-2">
                            <div class="star-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="text-xs text-gray-400 ml-2">Verificado</span>
                        </div>
                    </div>
                </div>
                <blockquote class="text-gray-300 text-sm leading-relaxed mb-4">
                    "Muito melhor que qualquer outro site que testei. E o Pix cai na hora! Não há nada mais frustrante do que esperar dias para receber seus ganhos. Aqui, a agilidade é impressionante."
                </blockquote>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span><i class="fas fa-calendar mr-1"></i>Há 6 dias</span>
                    <span class="text-green-400 font-semibold"><i class="fas fa-money-bill mr-1"></i>Pix na hora</span>
                </div>
            </div>

            <!-- Depoimento 8 -->
            <div class="testimonial-card bg-slate-800/80 p-6 rounded-2xl shadow-xl border border-slate-700/50 floating-animation hidden-testimonial" style="animation-delay: 3s;">
                <div class="flex items-start gap-4 mb-4">
                    <div class="relative">
                        <img src="https://i.pravatar.cc/80?img=8" class="w-16 h-16 rounded-full avatar-glow" alt="Avatar Diego">
                        <div class="absolute -bottom-1 -right-1 verified-badge w-6 h-6 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-white">Diego Andrade</h3>
                            <span class="text-xs bg-yellow-600 text-white px-2 py-1 rounded-full">Maranhão</span>
                        </div>
                        <div class="flex items-center gap-1 mb-2">
                            <div class="star-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="text-xs text-gray-400 ml-2">Verificado</span>
                        </div>
                    </div>
                </div>
                <blockquote class="text-gray-300 text-sm leading-relaxed mb-4">
                    "As raspadinhas são realmente viciantes, no bom sentido! Já ganhei várias vezes e a cada nova raspadinha a emoção é renovada. A plataforma é segura e fácil de usar, o que torna a experiência ainda mais agradável."
                </blockquote>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span><i class="fas fa-calendar mr-1"></i>Há 8 dias</span>
                    <span class="text-green-400 font-semibold"><i class="fas fa-money-bill mr-1"></i>Ganhos recorrentes</span>
                </div>
            </div>

            <!-- Depoimento 9 -->
            <div class="testimonial-card bg-slate-800/80 p-6 rounded-2xl shadow-xl border border-slate-700/50 floating-animation hidden-testimonial" style="animation-delay: 3.5s;">
                <div class="flex items-start gap-4 mb-4">
                    <div class="relative">
                        <img src="https://i.pravatar.cc/80?img=9" class="w-16 h-16 rounded-full avatar-glow" alt="Avatar Patrícia">
                        <div class="absolute -bottom-1 -right-1 verified-badge w-6 h-6 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-white">Patrícia Melo</h3>
                            <span class="text-xs bg-lime-600 text-white px-2 py-1 rounded-full">Pará</span>
                        </div>
                        <div class="flex items-center gap-1 mb-2">
                            <div class="star-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="text-xs text-gray-400 ml-2">Verificado</span>
                        </div>
                    </div>
                </div>
                <blockquote class="text-gray-300 text-sm leading-relaxed mb-4">
                    "Fiquei impressionada com a minha sorte! Em apenas duas raspadinhas, ganhei R$ 800. Compartilhei a novidade com todos os meus amigos e familiares, e muitos já estão jogando também."
                </blockquote>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span><i class="fas fa-calendar mr-1"></i>Há 10 dias</span>
                    <span class="text-green-400 font-semibold"><i class="fas fa-money-bill mr-1"></i>Ganhou R$ 800</span>
                </div>
            </div>

            <!-- Depoimento 10 -->
            <div class="testimonial-card bg-slate-800/80 p-6 rounded-2xl shadow-xl border border-slate-700/50 floating-animation hidden-testimonial" style="animation-delay: 4s;">
                <div class="flex items-start gap-4 mb-4">
                    <div class="relative">
                        <img src="https://i.pravatar.cc/80?img=10" class="w-16 h-16 rounded-full avatar-glow" alt="Avatar Leandro">
                        <div class="absolute -bottom-1 -right-1 verified-badge w-6 h-6 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-white">Leandro Silva</h3>
                            <span class="text-xs bg-teal-600 text-white px-2 py-1 rounded-full">Goiás</span>
                        </div>
                        <div class="flex items-center gap-1 mb-2">
                            <div class="star-rating">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="text-xs text-gray-400 ml-2">Verificado</span>
                        </div>
                    </div>
                </div>
                <blockquote class="text-gray-300 text-sm leading-relaxed mb-4">
                    "Gostei muito da experiência. Já tive altos e baixos, como em qualquer jogo, mas no final das contas, fechei o mês no positivo. O importante é a diversão e a emoção que as raspadinhas proporcionam."
                </blockquote>
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span><i class="fas fa-calendar mr-1"></i>Há 12 dias</span>
                    <span class="text-green-400 font-semibold"><i class="fas fa-money-bill mr-1"></i>Fechou positivo</span>
                </div>
            </div>

        </div>

        <!-- Load More Button -->
        <div class="text-center mt-12">
            <button id="load-more-btn" class="bg-gradient-to-r from-green-500 to-green-500 hover:from-purple-700 hover:to-blue-700 text-white font-semibold py-3 px-8 rounded-full transition-all duration-300 transform hover:scale-105 shadow-lg">
                <i class="fas fa-plus mr-2"></i>
                Ver Mais Depoimentos
            </button>
        </div>

        <!-- Trust Indicators -->
        <div class="mt-16 bg-slate-800/50 rounded-2xl p-8 border border-slate-700/50">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-white mb-2">Por que confiar em nós?</h2>
                <p class="text-gray-400">Transparência e segurança em primeiro lugar</p>
            </div>
            <div class="grid md:grid-cols-4 gap-6 text-center">
                <div class="pulse-animation">
                    <div class="bg-green-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">100% Seguro</h3>
                    <p class="text-gray-400 text-sm">Criptografia SSL</p>
                </div>
                <div class="pulse-animation" style="animation-delay: 0.5s;">
                    <div class="bg-blue-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-bolt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">Pix Instantâneo</h3>
                    <p class="text-gray-400 text-sm">Saque em segundos</p>
                </div>
                <div class="pulse-animation" style="animation-delay: 1s;">
                    <div class="bg-purple-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">+50k Jogadores</h3>
                    <p class="text-gray-400 text-sm">Comunidade ativa</p>
                </div>
                <div class="pulse-animation" style="animation-delay: 1.5s;">
                    <div class="bg-yellow-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-star text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">4.9/5 Estrelas</h3>
                    <p class="text-gray-400 text-sm">Avaliação média</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer CTA -->
    <div class="bg-gradient-to-r from-black to-black py-16">
        <div class="max-w-4xl mx-auto text-center px-4">
            <h2 class="text-3xl font-bold text-white mb-4">Pronto para ser o próximo ganhador?</h2>
            <p class="text-xl text-gray-300 mb-8">Junte-se a milhares de pessoas que já transformaram suas vidas</p>
           <button 
    class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-4 px-12 rounded-full text-lg transition-all duration-300 transform hover:scale-105 shadow-2xl" 
    onclick="window.location.href='/raspadinhas';"
>
    <i class="fas fa-play mr-3"></i>
    Começar Agora
</button>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loadMoreBtn = document.getElementById('load-more-btn');
            const testimonialsContainer = document.getElementById('testimonials-container');
            const hiddenTestimonials = document.querySelectorAll('.hidden-testimonial');
            let testimonialsToShow = 3; // Number of testimonials to show initially
            let currentIndex = 0;

            function showTestimonials() {
                for (let i = 0; i < testimonialsToShow; i++) {
                    if (hiddenTestimonials[currentIndex]) {
                        hiddenTestimonials[currentIndex].classList.remove('hidden-testimonial');
                        currentIndex++;
                    } else {
                        loadMoreBtn.style.display = 'none'; // Hide button if no more testimonials
                        break;
                    }
                }
            }

            loadMoreBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Add click effect
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
                showTestimonials();
            });

            // Add hover effects to testimonial cards
            const cards = document.querySelectorAll('.testimonial-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.borderColor = 'rgba(108, 102, 202, 0.5)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.borderColor = 'rgba(71, 85, 105, 0.5)';
                });
            });
        });
    </script>
</body>
</html>

