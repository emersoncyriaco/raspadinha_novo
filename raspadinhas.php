<?php
require 'includes/auth.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Escolha sua Raspadinha</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col items-center p-6">

  <h1 class="text-3xl font-bold text-green-700 mb-2">⭐ Escolha sua Raspadinha</h1>
  <p class="text-gray-600 mb-8">Escolha sua raspadinha e tente a sorte!</p>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full max-w-5xl">
    <!-- Raspadinha Esperança -->
    <div class="bg-white rounded-xl shadow-lg p-6 text-center relative">
      <div class="absolute top-2 right-2 bg-orange-400 text-white font-bold px-3 py-1 rounded-full text-sm">R$ 1,00</div>
      <h2 class="text-xl font-bold text-gray-800">Esperança</h2>
      <p class="mt-2 text-gray-600">Prêmio Máximo: <span class="text-green-600 font-bold">R$ 50,00</span></p>
      <p class="text-gray-600">Chance: <span class="text-blue-600 font-bold">1.00%</span></p>
      <a href="jogo.php?raspadinha=esperanca&valor=1.00" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-full transition">JOGAR</a>
    </div>

    <!-- Raspadinha Alegria -->
    <div class="bg-white rounded-xl shadow-lg p-6 text-center relative">
      <div class="absolute top-2 right-2 bg-orange-400 text-white font-bold px-3 py-1 rounded-full text-sm">R$ 2,00</div>
      <h2 class="text-xl font-bold text-gray-800">Alegria</h2>
      <p class="mt-2 text-gray-600">Prêmio Máximo: <span class="text-green-600 font-bold">R$ 100,00</span></p>
      <p class="text-gray-600">Chance: <span class="text-blue-600 font-bold">0.12%</span></p>
      <a href="jogo.php?raspadinha=alegria&valor=2.00" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-full transition">JOGAR</a>
    </div>

    <!-- Raspadinha Emoção -->
    <div class="bg-white rounded-xl shadow-lg p-6 text-center relative">
      <div class="absolute top-2 right-2 bg-orange-400 text-white font-bold px-3 py-1 rounded-full text-sm">R$ 20,00</div>
      <h2 class="text-xl font-bold text-gray-800">Emoção</h2>
      <p class="mt-2 text-gray-600">Prêmio Máximo: <span class="text-green-600 font-bold">R$ 500,00</span></p>
      <p class="text-gray-600">Chance: <span class="text-blue-600 font-bold">0.10%</span></p>
      <a href="jogo.php?raspadinha=emocao&valor=20.00" class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-full transition">JOGAR</a>
    </div>
  </div>
</body>
</html>
