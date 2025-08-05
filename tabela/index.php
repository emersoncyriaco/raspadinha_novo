<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require '../includes/db.php';
require '../includes/auth.php';

$userId = $_SESSION['usuario_id'] ?? null;
if (!$userId) {
  header("Location: ../login.php");
  exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$saldo = $usuario['balance'];?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>Raspa Sorte - Escolha sua Raspadinha</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-purple: #7257b4;
      --dark-blue: #000000;
      --light-blue: #000000;
      --accent-yellow: #fbbf24;
      --success-green: #10b981;
      --mobile-padding: clamp(12px, 4vw, 24px);
      --mobile-gap: clamp(8px, 2vw, 16px);
    }
    
    * {
      box-sizing: border-box;
      -webkit-tap-highlight-color: transparent;
    }
    
    body {
      font-family: 'Lexend', sans-serif;
      background: black;
      padding-bottom: 80px; /* Increased space for bottom navbar */
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      transition: filter 0.3s ease;
    }

    /* Classe para aplicar desfoque no fundo quando modal estiver aberto */
    body.modal-open > *:not(#modalDeposito):not(#qrMode){
      filter: blur(5px);
      transition: filter 0.3s ease;
    }

    body.modal-open {
      overflow: hidden;
    }

    /* Ocultar navbar quando modal estiver aberto */
    body.modal-open .desktop-nav {
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }

    /* Ocultar bottom navbar quando modal estiver aberto */
    body.modal-open .bottom-navbar {
      transform: translateY(100%) !important;
      opacity: 0 !important;
      transition: all 0.3s ease !important;
    }
    
    @media (min-width: 768px) {
      body {
        padding-bottom: 0;
      }
    }
    
    /* Enhanced Mobile-First Carousel */
    .carousel-container {
      position: relative;
      width: 100%;
      height: clamp(100px, 35vw, 230px);
      overflow: hidden;
      border-radius: clamp(20px, 3vw, 1px);
      margin: 0 auto;
      background: transparent;
      touch-action: pan-y;
    }
    
    .carousel-slide {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      transition: opacity 1.2s cubic-bezier(0.4, 0, 0.2, 1);
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      image-rendering: -webkit-optimize-contrast;
      image-rendering: crisp-edges;
    }
    
    .carousel-slide.active {
      opacity: 1;
    }
    
    .carousel-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: transparent;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
    }
    
    .carousel-content {
      max-width: 90%;
      padding: var(--mobile-padding);
    }
    
    .carousel-indicators {
      position: absolute;
      bottom: clamp(10px, 3vw, 20px);
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: clamp(6px, 2vw, 12px);
      z-index: 10;
    }
    
    
    
    .indicator.active {
      background: white;
      transform: scale(0.0);
      box-shadow: 0 0 2px rgba(255, 255, 255, 0.6);
    }
    
    /* Enhanced Winners Section */
    .winners-header {
      text-align: center;
      padding: var(--mobile-padding);
      background: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(20px);
      border-bottom: 2px solid #52fc60;
      margin-bottom: clamp(16px, 4vw, 32px);
      position: relative;
      overflow: hidden;
    }

    .winners-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(82, 252, 96, 0.15), transparent);
      animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
      0% { left: -100%; }
      100% { left: 100%; }
    }

    .winners-header h1 {
      font-size: clamp(1.4rem, 6vw, 2.8rem);
      color: #52fc60;
      text-shadow: 0 0 25px rgba(82, 252, 96, 0.6);
      margin-bottom: clamp(6px, 2vw, 12px);
      animation: glow 2.5s ease-in-out infinite alternate;
      font-weight: 700;
      letter-spacing: 1px;
    }

    .winners-header p {
      font-size: clamp(0.9rem, 3vw, 1.3rem);
      color: #e2e8f0;
      opacity: 0.95;
      font-weight: 400;
    }

    @keyframes glow {
      from { 
        text-shadow: 0 0 25px rgba(82, 252, 96, 0.6);
        transform: scale(1);
      }
      to { 
        text-shadow: 0 0 35px rgba(82, 252, 96, 0.9), 0 0 45px rgba(82, 252, 96, 0.4);
        transform: scale(1.02);
      }
    }

    .slider-container {
      position: relative;
      width: 100%;
      max-width: 100vw;
      margin: 0 auto;
      overflow: hidden;
      padding: 0 var(--mobile-padding);
    }

    .slider {
      display: flex;
      animation: slide 25s infinite linear;
      gap: var(--mobile-gap);
      will-change: transform;
    }

    .slide {
      flex: 0 0 auto;
      width: clamp(240px, 70vw, 360px);
      background: linear-gradient(145deg, #2a2a3e, #1e1e2e);
      border-radius: clamp(14px, 3vw, 20px);
      padding: clamp(14px, 4vw, 22px);
      border: 2px solid transparent;
      background-clip: padding-box;
      box-shadow: 
        0 10px 40px rgba(0, 0, 0, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      cursor: pointer;
      touch-action: manipulation;
    }

    .slide::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, #52fc60, #00d4ff);
      opacity: 0;
      transition: opacity 0.5s ease;
      border-radius: clamp(14px, 3vw, 20px);
      z-index: -1;
    }

    .slide:active::before,
    .slide:hover::before {
      opacity: 0.15;
    }

    .slide:active,
    .slide:hover {
      transform: translateY(-6px) scale(1.02);
      box-shadow: 
        0 25px 60px rgba(0, 0, 0, 0.6),
        0 0 35px rgba(82, 252, 96, 0.4);
    }

    .slide-content {
      display: flex;
      align-items: center;
      gap: clamp(10px, 3vw, 16px);
      position: relative;
      z-index: 1;
    }

    .slide-icon {
      flex-shrink: 0;
      width: clamp(45px, 12vw, 65px);
      height: clamp(45px, 12vw, 65px);
      background: linear-gradient(135deg, #52fc60, #00d4ff);
      border-radius: clamp(8px, 2vw, 12px);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: clamp(18px, 5vw, 26px);
      box-shadow: 0 8px 25px rgba(82, 252, 96, 0.5);
      animation: pulse 3.5s infinite;
      position: relative;
      overflow: hidden;
    }

    .slide-icon::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.4), transparent);
      transform: rotate(45deg);
      animation: iconShine 4.5s infinite;
    }

    @keyframes iconShine {
      0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
      50% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.1); }
    }

    .slide-info {
      flex: 1;
      min-width: 0;
    }

    .prize-amount {
      font-size: clamp(1.1rem, 4vw, 1.6rem);
      font-weight: 800;
      color: #52fc60;
      text-shadow: 0 0 20px rgba(82, 252, 96, 0.7);
      margin-bottom: clamp(3px, 1vw, 6px);
      display: block;
      letter-spacing: 0.5px;
    }

    .winner-name {
      font-size: clamp(0.9rem, 3vw, 1.2rem);
      color: #fff;
      margin-bottom: clamp(2px, 0.5vw, 4px);
      font-weight: 600;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
    }

    .game-type {
      font-size: clamp(0.8rem, 2.5vw, 1rem);
      color: #cbd5e1;
      opacity: 0.9;
      font-weight: 400;
    }

    @keyframes slide {
      0% { transform: translateX(0); }
      100% { transform: translateX(calc(-100% - var(--mobile-gap))); }
    }

    /* Enhanced Bottom Navigation */
    .bottom-navbar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(25px);
      border-top: 1px solid rgba(0, 0, 0, 0.08);
      z-index: 1000;
      display: flex;
      justify-content: space-around;
      align-items: center;
      padding: clamp(8px, 2vw, 12px) 0;
      box-shadow: 0 -6px 30px rgba(0, 0, 0, 0.15);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      min-height: 70px;
    }
    
    @media (min-width: 768px) {
      .bottom-navbar {
        display: none;
      }
    }
    
    .nav-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: clamp(6px, 2vw, 10px) clamp(8px, 2vw, 12px);
      border-radius: clamp(8px, 2vw, 12px);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      min-width: clamp(50px, 15vw, 70px);
      touch-action: manipulation;
      position: relative;
      overflow: hidden;
    }
    
    .nav-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, var(--primary-purple), var(--light-blue));
      opacity: 0;
      transition: opacity 0.4s ease;
      border-radius: clamp(8px, 2vw, 12px);
    }
    
    .nav-item:active::before {
      opacity: 0.1;
    }
    
    .nav-item:active {
      transform: scale(0.95);
    }
    
    .nav-item.active::before {
      opacity: 1;
    }
    
    .nav-item.active {
      color: white;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 8px 20px rgba(114, 87, 180, 0.4);
    }
    
    .nav-item i {
      font-size: clamp(16px, 4vw, 20px);
      margin-bottom: clamp(2px, 1vw, 4px);
      color: #666;
      transition: all 0.4s ease;
      position: relative;
      z-index: 1;
    }
    
    .nav-item.active i {
      color: white;
    }
    
    .nav-item span {
      font-size: clamp(9px, 2.5vw, 11px);
      font-weight: 600;
      color: #666;
      transition: all 0.4s ease;
      position: relative;
      z-index: 1;
    }
    
    .nav-item.active span {
      color: white;
    }
    
    /* Enhanced Desktop Navigation */
    .desktop-nav {
      background: black;
      backdrop-filter: white(15px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      padding: clamp(0.75rem, 2vw, 1.25rem) 0;
      position: sticky;
      top: 0;
      z-index: 100;
      transition: opacity 0.3s ease;
    }
    
    /* Enhanced Mobile Top Navigation */
    @media (max-width: 767px) {
      .desktop-nav {
        padding: clamp(12px, 3vw, 18px) 0;
      }
      
      .desktop-nav .max-w-6xl {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        padding: 0 var(--mobile-padding);
      }
      
      .desktop-nav .flex.gap-8 {
        display: none;
      }
      
      .desktop-nav .flex.gap-3 {
        display: none;
      }
      
      .mobile-auth-buttons {
        display: flex;
        gap: clamp(6px, 2vw, 10px);
        align-items: center;
      }
      
      .mobile-auth-buttons .user-info {
        display: flex;
        align-items: center;
        gap: clamp(6px, 2vw, 10px);
        font-size: clamp(0.75rem, 2.5vw, 0.9rem);
      }
      
      .mobile-auth-buttons .saldo-display {
        background: #22c55e;
        color: white;
        padding: clamp(8px, 2vw, 12px) clamp(10px, 3vw, 16px);
        border-radius: clamp(8px, 2vw, 12px);
        font-weight: 700;
        font-size: clamp(0.75rem, 2.5vw, 0.9rem);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        white-space: #22c55e;
      }
      
      .mobile-auth-buttons .depositar-btn {
        background: #22c55e;
        color: ;
        padding: clamp(8px, 2vw, 12px) clamp(10px, 3vw, 16px);
        border-radius: clamp(8px, 2vw, 12px);
        font-weight: 700;
        font-size: clamp(0.75rem, 2.5vw, 0.9rem);
        border: none;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
        touch-action: manipulation;
        white-space: nowrap;
      }
      
      .mobile-auth-buttons .depositar-btn:active {
        transform: scale(0.95);
      }
      
      .mobile-auth-buttons .depositar-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(251, 191, 36, 0.4);
      }
    }
    
    @media (min-width: 768px) {
      .mobile-auth-buttons {
        display: none;
      }
    }
    
    /* Enhanced Raspadinha Cards */
    .raspadinha-card {
      background: linear-gradient(145deg, #2a2a3e, #1e1e2e);
      border-radius: clamp(14px, 3vw, 20px);
      padding: clamp(16px, 4vw, 24px);
      border: 2px solid transparent;
      background-clip: padding-box;
      box-shadow: 
        0 10px 40px rgba(0, 0, 0, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      cursor: pointer;
      touch-action: manipulation;
    }
    
    .raspadinha-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, #52fc60, #00d4ff);
      opacity: 0;
      transition: opacity 0.5s ease;
      border-radius: clamp(14px, 3vw, 20px);
      z-index: -1;
    }
    
    .raspadinha-card:active::before,
    .raspadinha-card:hover::before {
      opacity: 0.15;
    }
    
    .raspadinha-card:active,
    .raspadinha-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 
        0 25px 60px rgba(0, 0, 0, 0.6),
        0 0 35px rgba(82, 252, 96, 0.4);
    }
    
    .price-badge {
      display: inline-block;
      padding: clamp(8px, 2vw, 12px) clamp(12px, 3vw, 18px);
      border-radius: clamp(16px, 4vw, 24px);
      font-weight: 800;
      font-size: clamp(0.8rem, 2.5vw, 1rem);
      margin-bottom: clamp(12px, 3vw, 18px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }
    
    .price-badge.green {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }
    
    .price-badge.orange {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
    }
    
    .price-badge.red {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
    }
    
    .prize-text {
      color: #fbbf24;
      font-weight: 700;
      font-size: clamp(1rem, 3.5vw, 1.3rem);
      margin-bottom: clamp(8px, 2vw, 12px);
      text-shadow: 0 2px 8px rgba(251, 191, 36, 0.4);
      line-height: 1.3;
    }
    
    .game-description {
      color: #e2e8f0;
      font-size: clamp(0.8rem, 2.5vw, 1rem);
      margin-bottom: clamp(16px, 4vw, 24px);
      line-height: 1.4;
      opacity: 0.9;
    }
    
    .play-button {
      background: linear-gradient(135deg, var(--success-green) 0%, #059669 100%);
      color: white;
      font-weight: 800;
      padding: clamp(12px, 3vw, 16px) clamp(20px, 5vw, 28px);
      border-radius: clamp(16px, 4vw, 24px);
      text-decoration: none;
      display: inline-block;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      border: none;
      cursor: pointer;
      font-size: clamp(0.85rem, 2.5vw, 1rem);
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
      touch-action: manipulation;
      width: 100%;
      text-align: center;
    }
    
    .play-button:active {
      transform: scale(0.95);
    }
    
    .play-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 30px rgba(16, 185, 129, 0.5);
    }
    
    /* Enhanced Modal */
    .modal-overlay {
      background: rgba(0, 0, 0, 0.9);
      backdrop-filter: blur(20px);
      z-index: 9999;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
    }
    
    .modal-content {
      background: white;
      color: #1f2937;
      border-radius: clamp(16px, 4vw, 20px);
      box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
      max-width: clamp(280px, 85vw, 380px);
      width: 90%;
      margin: var(--mobile-padding);
      position: relative;
      z-index: 10000;
    }
    
    .modal-input {
      width: 100%;
      padding: clamp(12px, 3vw, 16px);
      border: 2px solid #e5e7eb;
      border-radius: clamp(8px, 2vw, 12px);
      font-size: clamp(0.9rem, 3vw, 1.1rem);
      transition: all 0.3s ease;
      touch-action: manipulation;
    }
    
    .modal-input:focus {
      outline: none;
      border-color: var(--success-green);
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .modal-button {
      background: linear-gradient(135deg, var(--success-green) 0%, #059669 100%);
      color: white;
      font-weight: 800;
      padding: clamp(12px, 3vw, 16px) clamp(20px, 5vw, 28px);
      border-radius: clamp(8px, 2vw, 12px);
      border: none;
      cursor: pointer;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      width: 100%;
      font-size: clamp(0.9rem, 3vw, 1.1rem);
      touch-action: manipulation;
    }
    
    .modal-button:active {
      transform: scale(0.95);
    }
    
    .modal-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
    }

    .cancel-button {
      background: #6b7280;
      color: white;
      font-weight: 600;
      padding: clamp(10px, 2.5vw, 14px) clamp(16px, 4vw, 24px);
      border-radius: clamp(8px, 2vw, 12px);
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
      width: 100%;
      font-size: clamp(0.85rem, 2.5vw, 1rem);
      touch-action: manipulation;
    }

    .cancel-button:hover {
      background: #4b5563;
    }

    .cancel-button:active {
      transform: scale(0.95);
    }

    /* NOVO: Estilos para o modo QR Code */
    .qr-mode {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.95);
      backdrop-filter: blur(20px);
      z-index: 10000;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: var(--mobile-padding);
    }

    .qr-container {
      background: white;
      border-radius: clamp(20px, 5vw, 24px);
      padding: clamp(24px, 6vw, 32px);
      max-width: clamp(320px, 90vw, 400px);
      width: 100%;
      text-align: center;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
      position: relative;
      animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .qr-header {
      margin-bottom: clamp(20px, 5vw, 24px);
    }

    .qr-header h2 {
      color: #1f2937;
      font-size: clamp(1.25rem, 4vw, 1.5rem);
      font-weight: 700;
      margin-bottom: clamp(8px, 2vw, 12px);
    }

    .qr-header p {
      color: #6b7280;
      font-size: clamp(0.85rem, 2.5vw, 1rem);
    }

    .qr-code-wrapper {
      background: #f9fafb;
      border-radius: clamp(16px, 4vw, 20px);
      padding: clamp(16px, 4vw, 20px);
      margin-bottom: clamp(20px, 5vw, 24px);
      border: 2px solid #e5e7eb;
    }

    .qr-code-wrapper img {
      width: 100%;
      max-width: clamp(200px, 50vw, 260px);
      height: auto;
      border-radius: clamp(8px, 2vw, 12px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .qr-actions {
      display: flex;
      flex-direction: column;
      gap: clamp(12px, 3vw, 16px);
    }

    .qr-copy-btn {
      background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
      color: white;
      font-weight: 700;
      padding: clamp(14px, 3.5vw, 18px) clamp(20px, 5vw, 28px);
      border-radius: clamp(12px, 3vw, 16px);
      border: none;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-size: clamp(0.9rem, 2.5vw, 1.1rem);
      touch-action: manipulation;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: clamp(8px, 2vw, 12px);
    }

    .qr-copy-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
    }

    .qr-copy-btn:active {
      transform: scale(0.95);
    }

    .qr-cancel-btn {
      background: #f3f4f6;
      color: #6b7280;
      font-weight: 600;
      padding: clamp(10px, 2.5vw, 12px) clamp(16px, 4vw, 20px);
      border-radius: clamp(8px, 2vw, 12px);
      border: 1px solid #d1d5db;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: clamp(0.8rem, 2vw, 0.9rem);
      touch-action: manipulation;
    }

    .qr-cancel-btn:hover {
      background: #e5e7eb;
      color: #4b5563;
    }

    .qr-cancel-btn:active {
      transform: scale(0.95);
    }

    .qr-info {
      background: #f0f9ff;
      border: 1px solid #bae6fd;
      border-radius: clamp(8px, 2vw, 12px);
      padding: clamp(12px, 3vw, 16px);
      margin-top: clamp(16px, 4vw, 20px);
    }

    .qr-info p {
      color: #0369a1;
      font-size: clamp(0.75rem, 2vw, 0.85rem);
      margin: 0;
      display: flex;
      align-items: center;
      gap: clamp(6px, 1.5vw, 8px);
    }

    /* Responsividade específica para mobile */
    @media (max-width: 480px) {
      .qr-container {
        margin: clamp(16px, 4vw, 20px);
        padding: clamp(20px, 5vw, 24px);
      }
      
      .qr-actions {
        gap: clamp(10px, 2.5vw, 12px);
      }
    }

    /* Animação para o botão de copiar quando clicado */
    .qr-copy-btn.copied {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      transform: scale(0.95);
    }

    .qr-copy-btn.copied::after {
      content: '✓ Copiado!';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: clamp(0.8rem, 2vw, 0.9rem);
    }
    
    /* Enhanced Stats Section */
    .stats-counter {
      font-weight: 800;
      background: linear-gradient(135deg, var(--primary-purple) 0%, var(--light-blue) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-size: clamp(1.5rem, 6vw, 2.5rem);
    }
    
    /* Enhanced Animations */
    @keyframes slide-up {
      from { 
        opacity: 0; 
        transform: translateY(40px); 
      }
      to { 
        opacity: 1; 
        transform: translateY(0); 
      }
    }
    
    .animate-slide-up {
      animation: slide-up 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    
    /* Performance Optimizations */
    .carousel-slide,
    .slide,
    .raspadinha-card,
    .nav-item {
      will-change: transform;
    }
    
    /* Touch Improvements */
    @media (hover: none) and (pointer: coarse) {
      .slide:hover,
      .raspadinha-card:hover,
      .play-button:hover,
      .modal-button:hover {
        transform: none;
      }
      
      .slide:active,
      .raspadinha-card:active {
        transform: scale(0.98);
      }
    }
    
    /* Improved Grid Layout for Mobile */
    .raspadinha-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: clamp(16px, 4vw, 24px);
      padding: 0 var(--mobile-padding);
    }
    
    @media (max-width: 640px) {
      .raspadinha-grid {
        grid-template-columns: 1fr;
        gap: clamp(12px, 3vw, 18px);
      }
    }
    
    /* Enhanced CTA Section */
    .cta-section {
      background: linear-gradient(135deg, #7c3aed 0%, #3b82f6 100%);
      border-radius: clamp(16px, 4vw, 24px);
      padding: clamp(24px, 6vw, 48px);
      margin: clamp(24px, 6vw, 48px) var(--mobile-padding);
      position: relative;
      overflow: hidden;
    }
    
    .cta-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.2);
      z-index: 1;
    }
    
    .cta-content {
      position: relative;
      z-index: 2;
    }
    
    /* Enhanced Footer */
    .footer {
      background: #0f172a;
      padding: clamp(32px, 8vw, 64px) var(--mobile-padding) clamp(24px, 6vw, 48px);
      margin-top: clamp(32px, 8vw, 64px);
    }
    
    .footer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: clamp(24px, 6vw, 48px);
      margin-bottom: clamp(24px, 6vw, 48px);
    }
    
    @media (max-width: 640px) {
      .footer-grid {
        grid-template-columns: 1fr;
        gap: clamp(16px, 4vw, 24px);
      }
    }
  </style>
</head>
<body class="text-white min-h-screen">
  <!-- Enhanced Desktop Navigation -->
  <nav class="desktop-nav">
    <div class="max-w-6xl mx-auto px-4 flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <div class="w-8 h-8 bg-gradient-to-br from-white-500 to-blue-600 rounded-lg flex items-center justify-center">
          <i class="fas fa-coins text-white text-sm"></i>
        </div>
        <h1 class="text-white-400 font-extrabold text-xl">Raspa Sorte</h1>
      </div>
      
      <ul class="flex gap-8 text-sm">
        <li><a href="/raspadinhas" class="nav-link hover:text-white-300">Início</a></li>
        <li><a href="/raspadinhas" class="nav-link hover:text-white-300">Raspadinhas</a></li>
        <li><a href="/como-funciona" class="nav-link hover:text-white-300">Como Funciona</a></li>
        <li><a href="/premios" class="nav-link hover:text-white-300">Ganhadores</a></li>
      </ul>
      
      <!-- Desktop Buttons -->
      <div class="flex gap-3 items-center">
  <span class="bg-green-500 text-white px-3 py-1 rounded text-sm font-semibold">
    R$ <?= number_format($saldo, 2, ',', '.') ?>
  </span>
  <button onclick="abrirDeposito()" class="bg-green-500 hover:bg-emerald-600 px-3 py-1 rounded text-sm font-semibold transition-all flex items-center gap-1">
  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M5 19h14a2 2 0 002-2v-2H3v2a2 2 0 002 2z"/>
  </svg>
  Depositar
</button>


  
  <style>
  /* By default, hide the button on screens smaller than 768px (a common breakpoint for tablets/desktops) */
  .desktop-only {
    display: none;
  }

  /* Show the button when the screen width is 768px or wider */
  @media (min-width: 768px) {
    .desktop-only {
      display: block; /* Or 'inline-block', 'flex', etc., depending on your layout needs */
    }
  }
</style>

<button onclick="window.location.href='../perfil.php'" class="bg-green-500 hover:bg-emerald-600 h-[27px] px-2 rounded text-sm font-semibold transition-all desktop-only flex items-center gap-1">
  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21V9m0 0l-4 4m4-4l4 4M5 5h14a2 2 0 012 2v2H3V7a2 2 0 012-2z"/>
  </svg>
</button>



  
  <div class="relative group">
    <button class="flex items-center gap-1 text-sm font-medium hover:text-purple-300 transition-colors">
      <!-- Ícone de usuário (SVG) -->
      <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
      </svg>
      <?= htmlspecialchars($usuario['name']) ?>
    </button>
    <div class="absolute hidden group-hover:block bg-gray-700 mt-1 rounded shadow-md w-40 right-0">
      <a href="../perfil.php" class="block px-4 py-2 hover:bg-gray-600 transition-colors">Perfil</a>
      <a href="../perfil.php" class="block px-4 py-2 hover:bg-gray-600 transition-colors">Sacar</a>
      <a href="../logout.php" class="block px-4 py-2 hover:bg-gray-600 transition-colors">Sair</a>
    </div>
  </div>
</div>

      
      <!-- Enhanced Mobile Auth Buttons -->
      <div class="mobile-auth-buttons">
        <div class="user-info">
          <span class="saldo-display">R$ <?= number_format($saldo, 2, ',', '.') ?></span>
          <button onclick="window.location.href='../perfil'" class="depositar-btn">
  Sacar
</button>

        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="max-w-6xl mx-auto">
    <!-- Enhanced Banner Carousel Section -->
   <!-- Banner Carousel Section -->
    <section class="py-8 px-4">
      <div class="max-w-6xl mx-auto">
        <div class="carousel-container">
          <!-- Slide 1 -->
          <div class="carousel-slide active" style="background-image: url('https://i.ibb.co/HDFc6Nq4/01-K027-FSPHG8-GR8-A2-ZMAKZT61-D.jpgg')">
            <div class="">
              <div class="carousel-content">
                
              </div>
            </div>
          </div>
          
          <!-- Slide 2 -->
          <div class="carousel-slide" style="background-image: url('https://i.ibb.co/wNcZmx5L/01-K027-E1-CFJ906-ACNDNJB612-TP.jpg')">
            <div class="">
              <div class="carousel-content">
               
              </div>
            </div>
          </div>
          
         
          
          <!-- Indicators -->
          <div class="carousel-indicators">
           
          </div>
        </div>
        
          
          <!-- Enhanced Indicators -->
          <div class="carousel-indicators">
            <div class="indicator active" onclick="goToSlide(0)"></div>
            <div class="indicator" onclick="goToSlide(1)"></div>
            <div class="indicator" onclick="goToSlide(2)"></div>
          </div>
        </div>

         <div data-slot="card" class="mt-8 bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm">
  <div data-slot="card-content" class="px-6">
    <div role="group" aria-label="progress" class="flex relative flex-col sm:flex-row items-start w-full gap-5 lg:gap-2">

      <!-- Etapa 1 - Cadastre-se -->
      <div class="group relative flex w-full sm:flex-col lg:items-center lg:justify-center gap-4 sm:gap-3" data-state="completed">
        <div class="absolute left-[calc(50%+20px)] right-[calc(-50%+10px)] top-5 h-0.5 hidden lg:block rounded-full !bg-green-500"></div>
    <button
  class="bg-green-500 text-white flex items-center justify-center rounded-full pointer-events-none shrink-0"
  type="button"
  style="width: 36px; height: 36px; padding: 0; line-height: 0;"
>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    class="w-5 h-5"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
  >
    <path d="M20 6 9 17l-5-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
  </svg>
</button>



        <div class="mt-0.5 flex flex-col items-start sm:items-center text-start sm:text-center">
          <h4 class="text-md font-bold lg:text-base">Cadastre-se</h4>
          <p class="text-sm text-muted-foreground">Realize seu cadastro para participar</p>
        </div>
      </div>

      <!-- Etapa 2 - Deposite -->
      <div aria-current="true" data-state="active" data-orientation="horizontal" class="items-center group data-[disabled]:pointer-events-none relative flex w-full sm:flex-col lg:items-center lg:justify-center gap-4 sm:gap-3">
        <div data-orientation="horizontal" role="none" data-state="active" class="group-data-[disabled]:bg-muted group-data-[disabled]:opacity-50 group-data-[state=completed]:bg-accent-foreground absolute left-[calc(50%+20px)] right-[calc(-50%+10px)] top-5 h-0.5 shrink-0 rounded-full bg-muted hidden lg:block"></div>
       <button data-slot="button" 
  class="justify-center whitespace-nowrap text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\\'\])]:size-4 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive bg-[#22c55e] text-primary-foreground shadow-xs hover:bg-primary/90 size-9 p-1 flex flex-col items-center text-center gap-1 z-10 rounded-full shrink-0 pointer-events-none" 
  type="button" data-state="active" data-orientation="horizontal" tabindex="0" aria-describedby="reka-stepper-item-description-v-5" aria-labelledby="reka-stepper-item-title-v-4">
  <svg viewBox="0 0 512 512" fill="currentColor" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-5">
    <path d="M242.4 292.5c5.4-5.4 14.7-5.4 20.1 0l77 77c14.2 14.2 33.1 22 53.1 22h15.1l-97.1 97.1c-30.3 29.5-79.5 29.5-109.8 0l-97.5-97.4h9.3c20 0 38.9-7.8 53.1-22zm20.1-73.6c-6.4 5.5-14.6 5.6-20.1 0l-76.7-76.7c-14.2-15.1-33.1-22-53.1-22h-9.3l97.4-97.44c30.4-30.346 79.6-30.346 109.9 0l97.2 97.14h-15.2c-20 0-38.9 7.8-53.1 22zm-149.9-76.2c13.8 0 26.5 5.6 37.1 15.4l76.7 76.7c7.2 6.3 16.6 10.8 26.1 10.8 9.4 0 18.8-4.5 26-10.8l77-77c9.8-9.7 23.3-15.3 37.1-15.3h37.7l58.3 58.3c30.3 30.3 30.3 79.5 0 109.8l-58.3 58.3h-37.7c-13.8 0-27.3-5.6-37.1-15.4l-77-77c-13.9-13.9-38.2-13.9-52.1.1l-76.7 76.6c-10.6 9.8-23.3 15.4-37.1 15.4H80.78l-58.02-58c-30.346-30.3-30.346-79.5 0-109.8l58.02-58.1z"></path>
  </svg>
</button>


        <div class="mt-0.5 flex flex-col items-start sm:items-center text-start sm:text-center">
          <h4 id="reka-stepper-item-title-v-4" style="color: #22c55e;" class="whitespace-nowrap text-primary text-md font-bold transition lg:text-base">Deposite</h4>

          <p id="reka-stepper-item-description-v-5" class="text-sm text-muted-foreground transition lg:text-sm">Realize um depósito</p>
        </div>
      </div>

      <!-- Etapa 3 - Hora de Raspar -->
      <div class="group relative flex w-full sm:flex-col lg:items-center lg:justify-center gap-4 sm:gap-3" data-state="inactive">
        <div class="absolute left-[calc(50%+20px)] right-[calc(-50%+10px)] top-5 h-0.5 hidden lg:block bg-muted"></div>
        <button class="bg-background border size-9 p-1 flex flex-col items-center justify-center text-center gap-1 rounded-full pointer-events-none hover:bg-accent hover:text-accent-foreground" type="button">
          <svg fill="currentColor" viewBox="0 0 24 24" class="size-5">
            <path d="M1.5 6.375c0-1.036.84-1.875 1.875-1.875h17.25c1.035 0 1.875.84 1.875 1.875v3.026a.75.75 0 0 1-.375.65 2.249 2.249 0 0 0 0 3.898.75.75 0 0 1 .375.65v3.026c0 1.035-.84 1.875-1.875 1.875H3.375A1.875 1.875 0 0 1 1.5 17.625v-3.026a.75.75 0 0 1 .374-.65 2.249 2.249 0 0 0 0-3.898.75.75 0 0 1-.374-.65z"/>
          </svg>
        </button>
        <div class="mt-0.5 flex flex-col items-start sm:items-center text-start sm:text-center">
          <h4 class="text-md font-bold lg:text-base">Hora de Raspar</h4>
          <p class="text-sm text-muted-foreground">Raspe o cartão para ganhar prêmios</p>
        </div>
      </div>

      <!-- Etapa 4 - Ganhe Prêmios -->
      <div class="group relative flex w-full sm:flex-col lg:items-center lg:justify-center gap-4 sm:gap-3" data-state="inactive" disabled>
        <button class="bg-background border size-9 p-1 flex flex-col items-center justify-center text-center gap-1 rounded-full pointer-events-none hover:bg-accent hover:text-accent-foreground" type="button" disabled>
          <svg fill="currentColor" viewBox="0 0 512 512" class="size-5">
            <path d="M64 88c0 14.4 3.5 28 9.6 40H32c-17.7 0-32 14.3-32 32v64c0 17.7 14.3 32 32 32h448c17.7 0 32-14.3 32-32v-64c0-17.7-14.3-32-32-32h-41.6c6.1-12 9.6-25.6 9.6-40 0-48.6-39.4-88-88-88h-2.2c-31.9 0-61.5 16.9-77.7 44.4L256 85.5l-24.1-41C215.7 16.9 186.1 0 154.2 0H152c-48.6 0-88 39.4-88 88z" />
          </svg>
        </button>
        <div class="mt-0.5 flex flex-col items-start sm:items-center text-start sm:text-center">
          <h4 class="text-md font-bold lg:text-base">Ganhe Prêmios</h4>
          <p class="text-sm text-muted-foreground">Receba números para concorrer</p>
        </div>
      </div>

    </div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-10">

<!-- INÍCIO - CONTAINER RASPADINHAS -->
<!-- INÍCIO - CONTAINER SORTEIOS -->
<div data-slot="card"
  class="text-card-foreground flex flex-col rounded-xl border shadow-sm col-span-1 px-7 py-5 bg-white/1 gap-3 sm:gap-4 sm:border-r-2 sm:border-r-primary/65 relative overflow-hidden"
  style="background-image: url('https://i.ibb.co/fVh8SP9c/raffle.png'); background-size: cover; background-position: center;">

  
  <!-- Sobreposição escura com desfoque -->
  <div class="absolute inset-0 bg-black/20 backdrop-blur-[10px] z-0 pointer-events-none"></div>

  <!-- Conteúdo -->
  <div class="relative z-10">

    <!-- ÍCONE -->
   <!-- Substituindo SVG por uma imagem -->
<img src="https://i.ibb.co/dTqBHS1/PREMIOS-DIVERSOS-1.jpg" alt="Ícone" class="w-[600px] h-16 object-cover mt-3 sm:mt-3 mb-1 rounded-md mx-auto" />







    <!-- TÍTULO -->
    <p class="text-card-foreground/65 text-sm mt-4">
    
    </p>
    <!-- DESCRIÇÃO -->
     
        <p class="prize-text">Prêmios até<br>R$ 1.000,00 NO PIX</p>
        <span class="price-badge green">R$ 1,00</span>
        <p class="game-description">Sonho de Consumo 😍<br>Prêmio até: R$ 1.000,00</p>
        <a href="../jogo/?valor=1" class="play-button">
          <i class="fas fa-play mr-2"></i>
          JOGAR AGORA
        </a>

    <!-- BOTÃO -->
    <a href="/raspadinhas" class="">
     
    </a>

    <!-- IMAGEM DECORATIVA -->
    <img src="/assets/scratch.png" class="absolute -right-40 sm:-right-72 top-4 sm:top-1 sm:h-90 opacity-22 object-contain" alt="">

  </div>
</div>
<!-- FIM - CONTAINER RASPADINHAS -->




<!-- INÍCIO - CONTAINER SORTEIOS -->
<!-- INÍCIO - CONTAINER SORTEIOS -->
<div data-slot="card"
  class="text-card-foreground flex flex-col rounded-xl border shadow-sm col-span-1 px-7 py-5 bg-white/1 gap-3 sm:gap-4 sm:border-r-2 sm:border-r-primary/65 relative overflow-hidden"
  style="background-image: url('https://i.ibb.co/XkZKwCj8/scratch.png'); background-size: cover; background-position: center;">

  
  <!-- Sobreposição escura com desfoque -->
  <div class="absolute inset-0 bg-black/20 backdrop-blur-[10px] z-0 pointer-events-none"></div>

  <!-- Conteúdo -->
  <div class="relative z-10">

    <!-- ÍCONE -->
   <!-- Substituindo SVG por uma imagem -->
<img src="https://i.ibb.co/twq9SSqZ/PADR-O-02.jpg" alt="Ícone" class="w-[600px] h-16 object-cover mt-3 sm:mt-3 mb-1 rounded-md mx-auto" />


    <!-- TÍTULO -->
     <p class="text-card-foreground/65 text-sm mt-4">
    
    </p>
    <!-- DESCRIÇÃO (com espaçamento extra acima) -->
      
        <p class="prize-text">Prêmios até<br>R$ 5.000,00 NO PIX</p>
        <span class="price-badge orange">R$ 5,00</span>
        <p class="game-description">Raspe da Emoção<br>Prêmio até: R$ 5.000,00</p>
        <a href="../jogo/?valor=5" class="play-button">
          <i class="fas fa-play mr-2"></i>
          JOGAR AGORA
        </a>

    <!-- BOTÃO (mais abaixo) -->
    <a href="/raspadinhas" class="">
     
    </a>

  </div>
</div>
<!-- FIM - CONTAINER SORTEIOS -->

<!-- INÍCIO - CONTAINER RASPADINHAS -->
<!-- INÍCIO - CONTAINER SORTEIOS -->
<div data-slot="card"
  class="text-card-foreground flex flex-col rounded-xl border shadow-sm col-span-1 px-7 py-5 bg-white/1 gap-3 sm:gap-4 sm:border-r-2 sm:border-r-primary/65 relative overflow-hidden"
  style="background-image: url('https://i.ibb.co/fVh8SP9c/raffle.png'); background-size: cover; background-position: center;">

  
<!-- Sobreposição escura com desfoque -->
  <div class="absolute inset-0 bg-black/20 backdrop-blur-[10px] z-0 pointer-events-none"></div>

  <!-- Conteúdo -->
  <div class="relative z-10">

    <!-- ÍCONE -->
   <!-- Substituindo SVG por uma imagem -->
<img src="https://i.ibb.co/V0GZT5V2/PADR-O-03.jpg" alt="Ícone" class="w-[600px] h-16 object-cover mt-3 sm:mt-3 mb-1 rounded-md mx-auto" />


    <!-- TÍTULO -->
    <p class="text-card-foreground/65 text-sm mt-4">
    
    </p>
    <!-- DESCRIÇÃO -->
   
        <p class="prize-text">Prêmios até<br>R$ 6.300,00 NO PIX</p>
         <span class="price-badge red">R$ 10,00</span>
        <p class="game-description">Me mimei<br>Prêmio até: R$ 6.300,00</p>
        <a href="../jogo/?valor=10" class="play-button">
          <i class="fas fa-play mr-2"></i>
          JOGAR AGORA
        </a>

    <!-- BOTÃO -->
    <a href="/raspadinhas" class="">
     
    </a>

    <!-- IMAGEM DECORATIVA -->
    <img src="/assets/scratch.png" class="absolute -right-40 sm:-right-72 top-4 sm:top-1 sm:h-90 opacity-22 object-contain" alt="">

  </div>
</div>
<!-- FIM - CONTAINER RASPADINHAS -->




<!-- INÍCIO - CONTAINER SORTEIOS -->
<!-- INÍCIO - CONTAINER SORTEIOS -->
<div data-slot="card"
  class="text-card-foreground flex flex-col rounded-xl border shadow-sm col-span-1 px-7 py-5 bg-white/1 gap-3 sm:gap-4 sm:border-r-2 sm:border-r-primary/65 relative overflow-hidden"
  style="background-image: url('https://i.ibb.co/XkZKwCj8/scratch.png'); background-size: cover; background-position: center;">

  
<!-- Sobreposição escura com desfoque -->
  <div class="absolute inset-0 bg-black/20 backdrop-blur-[10px] z-0 pointer-events-none"></div>

  <!-- Conteúdo -->
  <div class="relative z-10">

    <!-- ÍCONE -->
   <!-- Substituindo SVG por uma imagem -->
<img src="https://i.ibb.co/hRXjDdPh/BIKE-MAQUINA-MOTO.jpg" alt="Ícone" class="w-[600px] h-16 object-cover mt-3 sm:mt-3 mb-1 rounded-md mx-auto" />


    <!-- TÍTULO -->
     <p class="text-card-foreground/65 text-sm mt-4">
    
    </p>
    <!-- DESCRIÇÃO (com espaçamento extra acima) -->
    
        <p class="prize-text">Prêmios até<br>R$ 7.500,00 NO PIX</p>
         <span class="price-badge" style="background: linear-gradient(135deg, #8b5cf6, #a855f7); color: white;">R$ 20,00</span>
        <p class="game-description">Super Prêmios<br>Prêmio até: R$ 7.000,00</p>
        <a href="../jogo/?valor=20" class="play-button">
          <i class="fas fa-play mr-2"></i>
          JOGAR AGORA
        </a>

    <!-- BOTÃO (mais abaixo) -->
    <a href="/raspadinhas" class="">
      
    </a>

  </div>
</div>
<!-- FIM - CONTAINER SORTEIOS -->

<!-- INÍCIO - CONTAINER RASPADINHAS -->
<!-- INÍCIO - CONTAINER SORTEIOS -->
<div data-slot="card"
  class="text-card-foreground flex flex-col rounded-xl border shadow-sm col-span-1 px-7 py-5 bg-white/1 gap-3 sm:gap-4 sm:border-r-2 sm:border-r-primary/65 relative overflow-hidden"
  style="background-image: url('https://i.ibb.co/fVh8SP9c/raffle.png'); background-size: cover; background-position: center;">

  
<!-- Sobreposição escura com desfoque -->
  <div class="absolute inset-0 bg-black/20 backdrop-blur-[10px] z-0 pointer-events-none"></div>

  <!-- Conteúdo -->
  <div class="relative z-10">

    <!-- ÍCONE -->
   <!-- Substituindo SVG por uma imagem -->
<img src="https://i.ibb.co/HLDpqfR0/CONSOLES.jpg" alt="Ícone" class="w-[600px] h-16 object-cover mt-3 sm:mt-3 mb-1 rounded-md mx-auto" />


    <!-- TÍTULO -->
    <p class="text-card-foreground/65 text-sm mt-4">
    
    </p>
    <!-- DESCRIÇÃO -->
     <!-- DESCRIÇÃO (com espaçamento extra acima) -->
    
        <p class="prize-text">Prêmios até<br>R$ 11.000,00 NO PIX</p>
        <span class="price-badge green">R$ 50,00</span>
        <p class="game-description">Sonho de Consumo 😍<br>Prêmio até: R$ 11.000,00</p>
        <a href="../jogo/?valor=50" class="play-button">
          <i class="fas fa-play mr-2"></i>
          JOGAR AGORA
        </a>

    <!-- BOTÃO -->
    <a href="/raspadinhas" class="">
      
    </a>

    <!-- IMAGEM DECORATIVA -->
    <img src="/assets/scratch.png" class="absolute -right-40 sm:-right-72 top-4 sm:top-1 sm:h-90 opacity-22 object-contain" alt="">

  </div>
</div>
<!-- FIM - CONTAINER RASPADINHAS -->




<!-- INÍCIO - CONTAINER SORTEIOS -->
<!-- INÍCIO - CONTAINER SORTEIOS -->
<div data-slot="card"
  class="text-card-foreground flex flex-col rounded-xl border shadow-sm col-span-1 px-7 py-5 bg-white/1 gap-3 sm:gap-4 sm:border-r-2 sm:border-r-primary/65 relative overflow-hidden"
  style="background-image: url('https://i.ibb.co/XkZKwCj8/scratch.png'); background-size: cover; background-position: center;">

  
<!-- Sobreposição escura com desfoque -->
  <div class="absolute inset-0 bg-black/20 backdrop-blur-[10px] z-0 pointer-events-none"></div>

  <!-- Conteúdo -->
  <div class="relative z-10">

    <!-- ÍCONE -->
   <!-- Substituindo SVG por uma imagem -->
<img src="https://i.ibb.co/d05zpNfL/luxo-1.jpg" alt="Ícone" class="w-[600px] h-16 object-cover mt-3 sm:mt-3 mb-1 rounded-md mx-auto" />

    <!-- TÍTULO -->
     <p class="text-card-foreground/65 text-sm mt-4">
    
    </p>
    <!-- DESCRIÇÃO (com espaçamento extra acima) -->
    
        <p class="prize-text">Prêmios até<br>R$ 14.000,00 NO PIX</p>
        <span class="price-badge green">R$ 100,00</span>
        <p class="game-description">Sonho de Consumo 😍<br>Prêmio até: R$ 14.000,00</p>
        <a href="../jogo/?valor=100" class="play-button">
          <i class="fas fa-play mr-2"></i>
          JOGAR AGORA
        </a>

    <!-- BOTÃO (mais abaixo) -->
    <a href="../jogo/?valor=1" class="">
     
    </a>

  </div>
</div>
<!-- FIM - CONTAINER SORTEIOS -->

<!-- FIM - CONTAINER SORTEIOS -->





   

  </div>
  <!-- FIM - CONTAINER SORTEIOS -->

</div>
    
    

   <!-- Rodapé -->
<footer class="bg-[transparent] mt-0 py-12 px-4 border-t border-slate-700">
  <div class="max-w-6xl mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
      <div>
        <div class="flex items-center space-x-2 mb-4">
          <div class="w-8 h-8 bg-gradient-to-br from-white-500 to-blue-600 rounded-lg flex items-center justify-center">
            <i class="fas fa-coins text-white text-sm"></i>
          </div>
          <h3 class="text-white font-extrabold text-xl">Raspa Sorte</h3>
        </div>
        <p class="text-gray-400 text-sm mb-4">
          A plataforma de raspadinhas virtuais mais confiável do Brasil.
        </p>
        <div class="flex space-x-4">
          <a href="#" class="text-gray-400 hover:text-white-400 transition-colors">
            <i class="fab fa-facebook text-xl"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-white-400 transition-colors">
            <i class="fab fa-instagram text-xl"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-white-400 transition-colors">
            <i class="fab fa-twitter text-xl"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-white-400 transition-colors">
            <i class="fab fa-youtube text-xl"></i>
          </a>
        </div>
      </div>

      <!-- Restante das colunas do rodapé... -->
    </div>

    <div class="border-t border-slate-700 pt-8 text-center">
      <p class="text-gray-400 text-sm">
        &copy; 2025 Raspa Sorte. Todos os direitos reservados. | 
        <span class="text-green-400">Jogue com responsabilidade</span>
      </p>
    </div>
  </div>
</footer>
  </div>

  <!-- =============================== -->
  <!-- NAVEGAÇÃO MOBILE REFINADA       -->
  <!-- =============================== -->
  <!-- Barra inferior mobile com fundo colorido -->
<div class="bg-[#000000] rounded-xl border-t shadow-lg z-10 fixed bottom-2 left-2 right-2 px-2 h-[72px] flex items-center gap-x-2.5 md:hidden">
  
  <!-- Botão Início -->
<button onclick="window.location.href='#'" class="group flex flex-col items-center justify-center gap-1 text-center text-inherit select-none text-primary font-semibold flex-1 transition-transform active:scale-90">
  <div>
    <svg width="1em" height="1em" viewBox="0 0 512 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="size-5">
      <path d="M416 174.74V48h-80v58.45L256 32 0 272h64v208h144V320h96v160h144V272h64z"></path>
    </svg>
  </div>
  <span class="text-[0.7rem] font-medium">Início</span>
</button>


  <!-- Botão Raspadinhas -->
 <!-- Botão com ícone de troféu -->
<button onclick="window.location.href='../premios'" class="group flex flex-col items-center justify-center gap-1 text-center text-inherit select-none flex-1 transition-transform active:scale-90">
  <div>
    <i class="fas fa-trophy text-[1.2rem]"></i>
  </div>
  <span class="text-[0.7rem] font-medium">Prêmios</span>
</button>



  <!-- Botão Depositar com funções setActiveNav e abrirDeposito -->
<button onclick="setActiveNav(this); abrirDeposito();" class="group flex flex-col items-center justify-center gap-1 text-center text-inherit select-none -translate-y-[1.25rem]">
  <div class="bg-[#22c55e] rounded-full border-4 border-surface text-white p-3 transition-transform group-active:scale-90">
    <svg fill="none" viewBox="0 0 24 24" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-[1.6rem]">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 15v3m0 3v-3m0 0h-3m3 0h3"></path>
      <path fill="currentColor" fill-rule="evenodd" d="M5 5a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h7.083A6 6 0 0 1 12 18c0-1.148.322-2.22.881-3.131A3 3 0 0 1 9 12a3 3 0 1 1 5.869.881A5.97 5.97 0 0 1 18 12c1.537 0 2.939.578 4 1.528V8a3 3 0 0 0-3-3zm7 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" clip-rule="evenodd"></path>
    </svg>
  </div>
  <span class="text-[0.7rem] font-medium">Depósitar</span>
</button>




 <!-- Botão Indique -->
<button onclick="window.location.href='../affiliate_dashboard'" class="group flex flex-col items-center justify-center gap-1 text-center text-inherit select-none flex-1 transition-transform active:scale-90">
  <div>
    <svg viewBox="0 0 640 512" fill="currentColor" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-5">
      <path d="M96 128a128 128 0 1 1 256 0 128 128 0 1 1-256 0zM0 482.3C0 383.8 79.8 304 178.3 304h91.4c98.5 0 178.3 79.8 178.3 178.3 0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM504 312v-64h-64c-13.3 0-24-10.7-24-24s10.7-24 24-24h64v-64c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24h-64v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"></path>
    </svg>
  </div>
  <span class="text-[0.7rem] font-medium">Indique</span>
</button>


  <!-- Botão Perfil -->
<button onclick="window.location.href='../perfil'" class="group flex flex-col items-center justify-center gap-1 text-center text-inherit select-none flex-1 transition-transform active:scale-90">
  <div>
    <svg viewBox="0 0 448 512" fill="currentColor" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-5">
      <path d="M224 256a128 128 0 1 0 0-256 128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3 0 498.7 13.3 512 29.7 512h388.6c16.4 0 29.7-13.3 29.7-29.7 0-98.5-79.8-178.3-178.3-178.3z"></path>
    </svg>
  </div>
  <span class="text-[0.7rem] font-medium">Perfil</span>
</button>

</div>
  <!-- Modais Compactos -->
<div id="modalContainer" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 hidden">
  <div class="modal-compact bg-black text-white rounded-2xl shadow-2xl w-[90%] relative transform transition-all border border-green-500">
    <button onclick="fecharModal()" class="absolute top-3 right-3 text-gray-500 hover:text-red-500 font-bold text-xl transition-colors">
      <i class="fas fa-times"></i>
    </button>
    </div>
  </div>

  <!-- Enhanced Modal Depósito -->
  <div id="modalDeposito" class="fixed inset-0 modal-overlay flex items-center justify-center z-50 hidden">
    <div class="modal-content p-6">
      <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-700 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="fab fa-pix text-white text-2xl"></i>
        </div>
        <h2 class="text-2xl font-bold mb-2">Depositar via Pix</h2>
        <p class="text-gray-600">Adicione saldo à sua conta de forma rápida e segura</p>
      </div>
      
      <div class="mb-4">
        <label class="block text-sm font-semibold mb-2">Valor do Depósito</label>
        <input id="valorDeposito" type="number" min="1" step="0.01" placeholder="Ex: 10,00" 
               class="modal-input" />
        <p class="text-xs text-gray-500 mt-1">Valor mínimo: R$ 1,00</p>
      </div>
      
      <button onclick="gerarPix()" class="modal-button mb-4">
        <i class="fab fa-pix mr-2"></i>
        Gerar Código Pix
      </button>
      
      <button onclick="fecharDeposito()" class="cancel-button">
        Cancelar
      </button>
      
      <div id="resultadoPix" class="mt-4 text-sm text-left"></div>
    </div>
  </div>

  <!-- NOVO: Modal QR Code -->
  <div id="qrMode" class="qr-mode hidden">
    <div class="qr-container">
      <div class="qr-header">
        <h2>Código QR Pix</h2>
        <p>Escaneie o código ou copie o código Pix</p>
      </div>
      
      <div class="qr-code-wrapper">
        <img id="qrCodeImage" src="" alt="QR Code Pix" />
      </div>
      
      <div class="qr-actions">
        <button id="qrCopyBtn" class="qr-copy-btn">
          <i class="fas fa-copy"></i>
          Copiar código Pix
        </button>
        
        <button onclick="fecharQRMode()" class="qr-cancel-btn">
          Cancelar
        </button>
      </div>
      
      <div class="qr-info">
        <p>
          <i class="fas fa-clock"></i>
          Após o pagamento, o saldo será liberado automaticamente.
        </p>
      </div>
    </div>
  </div>

  <script>
  function setActiveNav(element) {
    document.querySelectorAll('.nav-item').forEach(item => {
      item.classList.remove('active');
    });
    element.classList.add('active');
  }

  function abrirDeposito() {
    document.getElementById('modalDeposito').classList.remove('hidden');
    document.getElementById('resultadoPix').innerHTML = "";
    
    // Adiciona classe para desfocar o fundo e ocultar navbars
    document.body.classList.add('modal-open');
  }

  function fecharDeposito() {
    document.getElementById('modalDeposito').classList.add('hidden');
    
    // Remove classe para remover desfoque e mostrar navbars
    document.body.classList.remove('modal-open');
  }

  // NOVA FUNÇÃO: Abrir modo QR Code
  function abrirQRMode(qrCodeData, pixCode) {
    // Fechar modal de depósito
    fecharDeposito();
    
    // Configurar QR Code
    document.getElementById('qrCodeImage').src = qrCodeData;
    
    // Configurar botão de copiar
    const copyBtn = document.getElementById('qrCopyBtn');
    copyBtn.onclick = () => copiarPixCode(pixCode);
    
    // Mostrar modo QR
    document.getElementById('qrMode').classList.remove('hidden');
    
    // Ocultar todos os outros elementos
    document.body.classList.add('modal-open');
  }

  // NOVA FUNÇÃO: Fechar modo QR Code
  function fecharQRMode() {
    document.getElementById('qrMode').classList.add('hidden');
    document.body.classList.remove('modal-open');
  }

  // NOVA FUNÇÃO: Copiar código Pix
  function copiarPixCode(codigo) {
    navigator.clipboard.writeText(codigo).then(() => {
      const copyBtn = document.getElementById('qrCopyBtn');
      const originalText = copyBtn.innerHTML;
      
      // Animação de feedback
      copyBtn.classList.add('copied');
      copyBtn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
      
      setTimeout(() => {
        copyBtn.classList.remove('copied');
        copyBtn.innerHTML = originalText;
      }, 2000);
    }).catch(() => {
      alert("Erro ao copiar o código Pix.");
    });
  }

  async function gerarPix() {
    const valor = parseFloat(document.getElementById('valorDeposito').value);
    if (!valor || valor < 1) {
      alert("Valor mínimo: R$ 1,00");
      return;
    }

    try {
      const res = await fetch("../gerar_pix_bspay.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ valor })
      });
      const data = await res.json();

      if (data.erro) {
        alert(data.erro);
        return;
      }

      // Codifica o payload para URL
      const qrPayload = encodeURIComponent(data.qrcode);
      // URL da API QRServer para gerar o QR Code
      const imgSrc = `https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${qrPayload}`;

      // MODIFICADO: Abrir modo QR Code em vez de mostrar no modal
      abrirQRMode(imgSrc, data.qrcode);

    } catch (error) {
      alert('Erro ao gerar código Pix. Tente novamente.');
    }
  }

  function copiarCodigoPix(codigo) {
    navigator.clipboard.writeText(codigo).then(() => {
      alert("Código Pix copiado!");
    }).catch(err => {
      alert("Erro ao copiar o código Pix.");
    });
  }

    
    // Enhanced Carousel Functions
    let currentSlide = 0;
    const slides = document.querySelectorAll(".carousel-slide");
    const indicators = document.querySelectorAll(".indicator");
    const totalSlides = slides.length;
    
    function goToSlide(index) {
      slides[currentSlide].classList.remove("active");
      indicators[currentSlide].classList.remove("active");
      
      currentSlide = index;
      
      slides[currentSlide].classList.add("active");
      indicators[currentSlide].classList.add("active");
    }
    
    function nextSlide() {
      const nextIndex = (currentSlide + 1) % totalSlides;
      goToSlide(nextIndex);
    }
    
    // Auto-advance every 4 seconds
    setInterval(nextSlide, 4000);
    
    // Touch/Swipe Support for Carousel
    let startX = 0;
    let endX = 0;
    
    const carousel = document.querySelector('.carousel-container');
    
    carousel.addEventListener('touchstart', (e) => {
      startX = e.touches[0].clientX;
    });
    
    carousel.addEventListener('touchend', (e) => {
      endX = e.changedTouches[0].clientX;
      handleSwipe();
    });
    
    function handleSwipe() {
      const threshold = 50;
      const diff = startX - endX;
      
      if (Math.abs(diff) > threshold) {
        if (diff > 0) {
          // Swipe left - next slide
          nextSlide();
        } else {
          // Swipe right - previous slide
          const prevIndex = currentSlide === 0 ? totalSlides - 1 : currentSlide - 1;
          goToSlide(prevIndex);
        }
      }
    }
    
    // Close modal when clicking outside
    document.getElementById('modalDeposito').addEventListener('click', function(e) {
      if (e.target === this) {
        fecharDeposito();
      }
    });

    // NOVO: Close QR mode when clicking outside
    document.getElementById('qrMode').addEventListener('click', function(e) {
      if (e.target === this) {
        fecharQRMode();
      }
    });
    
    // Enhanced Intersection Observer for animations
    const observerOptions = {
      threshold: 0.1,
      rootMargin: '0px 0px -30px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-slide-up');
        }
      });
    }, observerOptions);
    
    // Observe elements for animation
    document.querySelectorAll(".raspadinha-card").forEach(el => {
      observer.observe(el);
    });
    
    // Performance optimization: Preload images
    const imageUrls = [
      'https://i.ibb.co/ynjjLXrZ/1752257985-1.webp',
      'https://i.ibb.co/XBDRyhQ/1752257991-1.webp',
      'https://i.ibb.co/xtc7XYtD/2.png',
      'https://i.ibb.co/FRLNyjT/3.png',
      'https://i.ibb.co/HTzqtMnt/1.png',
      'https://i.ibb.co/KjKHvr6R/4.png'
    ];
    
    imageUrls.forEach(url => {
      const img = new Image();
      img.src = url;
    });
    
    // Prevent zoom on double tap (iOS Safari)
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function (event) {
      const now = (new Date()).getTime();
      if (now - lastTouchEnd <= 300) {
        event.preventDefault();
      }
      lastTouchEnd = now;
    }, false);
    
    // Enhanced scroll performance
    let ticking = false;
    
    function updateScrollPosition() {
      // Add any scroll-based animations here
      ticking = false;
    }
    
    window.addEventListener('scroll', function() {
      if (!ticking) {
        requestAnimationFrame(updateScrollPosition);
        ticking = true;
      }
    });
  </script>
</body>
</html>

