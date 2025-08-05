<?php
// Incluir rastreamento de afiliados no início da página
require_once 'affiliate_tracker.php';

// Verificar se o usuário está logado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$usuarioLogado = isset($_SESSION['usuario_id']);
$saldo = 0;
$nomeUsuario = '';

if ($usuarioLogado) {
    require 'includes/db.php';
    $userId = $_SESSION['usuario_id'];
    $stmt = $conn->prepare("SELECT name, balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $nomeUsuario = $user['name'];
        $saldo = $user['balance'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raspadinha Virtual - Raspa Sorte</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-white:#121212;
            --dark-blue: #121212;
            --light-blue: #121212;
            --accent-yellow: #121212;
            --success-green: #121212;
            --mobile-padding: clamp(12px, 4vw, 24px);
            --mobile-gap: clamp(8px, 2vw, 16px);
        }
                
        body {
            font-family: 'Lexend', sans-serif;
            background: #000000;
            padding-bottom: 60px; /* Space for bottom navbar on mobile */
        }
                
        @media (min-width: 768px) {
            body {
                padding-bottom: 0;
            }
        }

        /* Logo Styles */
        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo-image {
            height: 2.5rem;
            width: auto;
            object-fit: contain;
            filter: brightness(1.1);
            transition: all 0.3s ease;
        }

        .logo-image:hover {
            filter: brightness(1.3) drop-shadow(0 0 10px rgba(34, 197, 94, 0.3));
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .logo-image {
                height: 2rem;
            }
        }

        /* Enhanced Section Header */
        .section-header {
            position: relative;
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
        }

        .section-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #22c55e, #10b981, #059669);
            border-radius: 2px;
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.5);
        }

        .section-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 900;
            background: linear-gradient(135deg, #ffffff 0%, #22c55e 50%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            text-shadow: 0 4px 20px rgba(34, 197, 94, 0.3);
            line-height: 1.2;
        }

        .section-subtitle {
            font-size: clamp(1rem, 3vw, 1.3rem);
            color: #e2e8f0;
            font-weight: 500;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        @keyframes glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(34, 197, 94, 0.3);
            }
            50% {
                box-shadow: 0 0 40px rgba(34, 197, 94, 0.6);
            }
        }

        .section-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, rgba(34, 197, 94, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            animation: glow 3s ease-in-out infinite;
        }

        /* Bottom Navigation for Mobile */
        .bottom-navbar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 8px 0;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, opacity 0.3s ease;
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
            padding: 6px 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 45px;
        }

        .nav-item:hover {
            background: rgba(114, 87, 180, 0.1);
            transform: translateY(-1px);
        }

        .nav-item.active {
            background: linear-gradient(135deg, var(--primary-white), var(--light-blue));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(114, 87, 180, 0.3);
        }

        /* Central highlighted button */
        .nav-item.central-button {
            background: linear-gradient(135deg, #6876df, #7257b4);
            color: white;
            border-radius: 12px;
            padding: 8px 12px;
            min-width: 55px;
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(104, 118, 223, 0.4);
        }

        .nav-item.central-button:hover {
            background: linear-gradient(135deg, #7257b4, #6876df);
            transform: translateY(-6px);
            box-shadow: 0 8px 25px rgba(104, 118, 223, 0.5);
        }

        .nav-item.central-button i {
            color: white;
            font-size: 18px;
        }

        .nav-item.central-button span {
            color: white;
            font-weight: 600;
        }

        .nav-item i {
            font-size: 16px;
            margin-bottom: 2px;
            color: #666;
            transition: all 0.3s ease;
        }

        .nav-item.active i {
            color: white;
        }

        .nav-item span {
            font-size: 8px;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
        }

        .nav-item.active span {
            color: white;
        }

        /* Desktop Navigation */
        .desktop-nav {
            background: rgb(0 0 0 / 95%);
            backdrop-filter: white(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 0;
        }

        /* Mobile Top Navigation - Only show buttons on mobile */
        @media (max-width: 767px) {
            .desktop-nav {
                display: block;
                padding: 0.75rem 0;
            }
            .desktop-nav .max-w-6xl {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            .desktop-nav .flex.gap-8 {
                display: none; /* Hide navigation links on mobile */
            }
            .desktop-nav .flex.gap-3 {
                display: none; /* Hide desktop buttons on mobile */
            }
            .mobile-auth-buttons {
                display: flex;
                gap: 0.5rem;
                align-items: center;
            }
            .mobile-auth-buttons button {
                font-size: 0.75rem;
                padding: 0.5rem 0.75rem;
                border-radius: 0.5rem;
                transition: all 0.3s ease;
                font-weight: 500;
            }
            .mobile-btn-login {
                color: white;
                border: 1px solid rgba(255, 255, 255, 0.3);
                background: transparent;
            }
            .mobile-btn-login:hover {
                background: rgba(255, 255, 255, 0.1);
                border-color: var(--primary-white);
            }
            .mobile-btn-register {
                background: #22c55e;
                color: white;
                border: none;
            }
            .mobile-btn-register:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            }
            .mobile-user-info {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.75rem;
            }
            .mobile-saldo-display {
                background: #22c55e;
                color: white;
                padding: 0.5rem 0.75rem;
                border-radius: 0.5rem;
                font-weight: 700;
                font-size: 0.75rem;
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
                white-space: nowrap;
            }
            .mobile-depositar-btn {
                background: #22c55e;
                color: white;
                padding: 0.5rem 0.75rem;
                border-radius: 0.5rem;
                font-weight: 700;
                font-size: 0.75rem;
                border: none;
                cursor: pointer;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 4px 15px rgba(251, 191, 36, 0.3);
                touch-action: manipulation;
                white-space: nowrap;
            }
            .mobile-depositar-btn:active {
                transform: scale(0.95);
            }
            .mobile-depositar-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(251, 191, 36, 0.4);
            }
        }

        @media (min-width: 768px) {
            .mobile-auth-buttons {
                display: none;
            }
            .mobile-user-info {
                display: none;
            }
        }

        /* Carousel Styles - UPDATED FOR LARGER PC SIZE WITH ROUNDED BORDERS */
        .carousel-container {
            position: relative;
            width: 100%;
            max-width: 1200px;
            height: 100px; /* Tamanho pequeno para mobile por padrão */
            overflow: hidden;
            border-radius: 0.5rem; /* Bordas pequenas para mobile */
            margin: 0 auto;
        }

        @media (min-width: 768px) {
            .carousel-container {
                height: 320px; /* AINDA MAIOR para desktop - era 230px */
                max-width: 1200px;
                border-radius: 2rem; /* Bordas bem arredondadas para PC */
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4); /* Sombra elegante */
            }
        }

        @media (max-width: 767px) {
            .carousel-container {
                height: 100px; /* Mantém pequeno no mobile */
                max-width: 100%;
                border-radius: 0.75rem; /* Bordas menores para mobile */
            }
        }

        .carousel-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
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
            background: linear-gradient(135deg, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.2) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        .carousel-content {
            max-width: 600px;
            padding: 0.5rem; /* Menor para mobile */
        }

        @media (min-width: 768px) {
            .carousel-content {
                padding: 2rem; /* Maior para desktop */
            }
        }

        .carousel-indicators {
            position: absolute;
            bottom: 10px; /* Pequeno para mobile */
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
        }

        @media (min-width: 768px) {
            .carousel-indicators {
                bottom: 30px; /* Maior para desktop - era 20px */
                gap: 15px; /* Gap maior */
            }
        }

        .indicator {
            width: 10px; /* Pequeno para mobile */
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        @media (min-width: 768px) {
            .indicator {
                width: 16px; /* Maior para desktop - era 12px */
                height: 16px;
            }
        }

        .indicator.active {
            background: white;
            transform: scale(1.2);
        }

        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-white), var(--light-blue));
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .btn-primary {
            background: #22c55e;
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
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--accent-yellow) 0%, #f59e0b 100%);
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 15px 35px rgba(251, 191, 36, 0.4);
        }

        .card-hover {
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: rgba(114, 87, 180, 0.3);
        }

        .stats-counter {
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-white) 0%, var(--light-blue) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        @keyframes slide-up {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-up {
            animation: slide-up 0.20s ease-out forwards;
        }

        /* NOVO: Título Destaques */
        .destaques-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            padding: 0 var(--mobile-padding);
        }

        .destaques-title {
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            font-weight: 500;
            color: #ffffff;
            margin: 0;
            line-height: 1;
        }

        .destaques-icon {
            width: 1.5rem;
            height: 1.5rem;
            color: #fbbf24;
            flex-shrink: 0;
        }

        @media (min-width: 768px) {
            .destaques-icon {
                width: 2rem;
                height: 2rem;
            }
        }

        /* Enhanced Raspadinha Cards - NOVO DESIGN COM BORDAS */
        .raspadinha-card {
            background: transparent;
            border-radius: clamp(14px, 3vw, 20px);
            padding: clamp(16px, 4vw, 24px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(45deg, rgba(34, 197, 94, 0.05), rgba(16, 185, 129, 0.05));
            opacity: 0;
            transition: opacity 0.5s ease;
            border-radius: clamp(14px, 3vw, 20px);
            z-index: -1;
        }

        .raspadinha-card:active::before,
        .raspadinha-card:hover::before {
            opacity: 1;
        }

        .raspadinha-card:active,
        .raspadinha-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(34, 197, 94, 0.6);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.2), 0 0 35px rgba(34, 197, 94, 0.3);
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

        .play-button.login-required {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
        }

        .play-button.login-required:hover {
            box-shadow: 0 12px 30px rgba(107, 114, 128, 0.5);
        }

        /* Ultra Compact Modal Styles */
        .modal-compact {
            max-width: 320px;
            padding: 1rem;
        }

        .modal-compact .form-input {
            padding: 0.5rem;
            font-size: 0.8rem;
        }

        .modal-compact .form-button {
            padding: 0.6rem 0.8rem;
            font-size: 0.8rem;
        }

        .modal-compact .modal-header {
            margin-bottom: 0.75rem;
        }

        .modal-compact .modal-header h2 {
            font-size: 1.25rem;
        }

        .modal-compact .modal-header p {
            font-size: 0.75rem;
        }

        .modal-compact .modal-header .w-12 {
            width: 2.5rem;
            height: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .modal-compact .space-y-3 > * + * {
            margin-top: 0.5rem;
        }

        .modal-compact label {
            font-size: 0.7rem;
            margin-bottom: 0.25rem;
        }

        .modal-compact .text-xs {
            font-size: 0.65rem;
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

        /* LIVE WINNERS SECTION - Layout horizontal sempre */
        .live-winners-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .live-winners-title {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .live-winners-carousel {
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }

        /* WINNERS CAROUSEL - COMPLETAMENTE REFEITO PARA MOBILE */
        .winners-carousel-container {
            position: relative;
            width: 100%;
            overflow: hidden;
            mask: linear-gradient(to right, transparent, black 20px, black calc(100% - 20px), transparent);
            -webkit-mask: linear-gradient(to right, transparent, black 20px, black calc(100% - 20px), transparent);
        }

        .winners-carousel {
            display: flex;
            width: max-content;
        }

        /* MOBILE: Mostra apenas 1.5 cards por vez */
        @media (max-width: 768px) {
            .winners-carousel {
                gap: 16px;
                animation: scroll-mobile-smooth 25s linear infinite;
            }
                        
            .winner-card {
                min-width: 200px;
                max-width: 200px;
                flex-shrink: 0;
            }
                        
            .winners-carousel-container {
                width: 100%;
                max-width: 320px; /* Limita para mostrar ~1.5 cards */
            }
        }

        /* DESKTOP: Mostra múltiplos cards */
        @media (min-width: 769px) {
            .winners-carousel {
                gap: 12px;
                animation: scroll-desktop-smooth 30s linear infinite;
            }
                        
            .winner-card {
                min-width: 180px;
                max-width: 180px;
                flex-shrink: 0;
            }
                        
            .winners-carousel-container {
                width: 100%;
            }
        }

        /* Animações otimizadas */
        @keyframes scroll-mobile-smooth {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-216px * 7)); /* 200px + 16px gap = 216px por card */
            }
        }

        @keyframes scroll-desktop-smooth {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-192px * 7)); /* 180px + 12px gap = 192px por card */
            }
        }

        /* WINNER CARD - Melhorado para mobile */
        .winner-card {
            cursor: pointer;
            user-select: none;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .winner-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(34, 197, 94, 0.4);
        }

        /* Pause animation on hover */
        .winners-carousel-container:hover .winners-carousel {
            animation-play-state: paused;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .winner-card {
                padding: 0.75rem;
                gap: 0.5rem;
            }
                        
            .winner-card img {
                width: 2rem;
                height: 2rem;
                flex-shrink: 0;
            }
                        
            .winner-card .text-xs {
                font-size: 0.7rem;
                line-height: 1.2;
            }
                        
            .winner-card .font-semibold {
                font-size: 0.75rem;
                font-weight: 700;
            }
        }

        @media (min-width: 769px) {
            .winner-card {
                padding: 0.75rem;
                gap: 0.75rem;
            }
                        
            .winner-card img {
                width: 2rem;
                height: 2rem;
                flex-shrink: 0;
            }
                        
            .winner-card .text-xs {
                font-size: 0.75rem;
                line-height: 1.3;
            }
        }

        /* Garantir que o texto não quebre */
        .winner-card .text-ellipsis {
            max-width: 120px;
        }

        @media (max-width: 768px) {
            .winner-card .text-ellipsis {
                max-width: 100px;
            }
        }

        /* NOVO FOOTER STYLES - Baseado na imagem */
        .footer-new {
            background: #1a1a1a;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem 0 2rem;
            margin-top: 4rem;
        }

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 3rem;
    align-items: start;
}

/* MOBILE: Layout específico conforme a imagem */
@media (max-width: 768px) {
    .footer-content {
        display: block;
        padding: 0 1.5rem;
        text-align: left;
    }
    
    .footer-logo-section {
        text-align: left;
        margin-bottom: 2rem;
    }
    
    .footer-logo {
        justify-content: flex-start;
        margin-bottom: 1rem;
    }
    
    .footer-links-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 1rem;
    }
}

.footer-links-container {
    display: contents;
}

@media (max-width: 768px) {
    .footer-links-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 1rem;
    }
    
    .footer-links-section {
        align-items: flex-start;
    }
    
    .footer-links-list {
        align-items: flex-start;
    }
}

        .footer-logo-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .footer-logo {
                justify-content: flex-start;
                margin-bottom: 1rem;
            }
        }

        .footer-logo-image {
            height: 3rem;
            width: auto;
            object-fit: contain;
            filter: brightness(1.1);
            transition: all 0.3s ease;
        }

        .footer-logo-image:hover {
            filter: brightness(1.3) drop-shadow(0 0 10px rgba(34, 197, 94, 0.3));
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .footer-logo-image {
                height: 2.5rem;
            }
        }

        .footer-copyright {
            color: #e2e8f0;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 0.5rem;
        }

        .footer-disclaimer {
            color: #9ca3af;
            font-size: 0.8rem;
            line-height: 1.4;
            max-width: 400px;
        }

        .footer-links-section {
            display: flex;
            flex-direction: column;
        }

        .footer-links-title {
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer-links-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .footer-link {
            color: #9ca3af;
            font-size: 0.9rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: #22c55e;
        }

        @media (max-width: 768px) {
            .footer-links-section {
                align-items: flex-start;
            }
            
            .footer-links-list {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body class="text-white min-h-screen">
    <!-- Desktop Navigation -->
    <nav class="desktop-nav">
        <div class="max-w-6xl mx-auto px-4 flex justify-between items-center">
            <div class="logo-container">
                <img src="/img/logo.webp" alt="Raspa Sorte Logo" class="logo-image">
            </div>
            <ul class="flex gap-8 text-sm">
                <li><a href="/" class="nav-link hover:text-white-300">Início</a></li>
                <li><a href="/raspadinhas" class="nav-link hover:text-white-300">Raspadinhas</a></li>
                <li><a href="/como-funciona" class="nav-link hover:text-white-300">Como Funciona</a></li>
                <li><a href="/premios" class="nav-link hover:text-white-300">Ganhadores</a></li>
            </ul>
            <!-- Desktop Buttons -->
            <?php if ($usuarioLogado): ?>
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
                    <div class="relative group">
                        <button class="flex items-center gap-1 text-sm font-medium hover:text-purple-300 transition-colors">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
                            </svg>
                            <?= htmlspecialchars($nomeUsuario) ?>
                        </button>
                        <div class="absolute hidden group-hover:block bg-gray-700 mt-1 rounded shadow-md w-40 right-0">
                            <a href="perfil.php" class="block px-4 py-2 hover:bg-gray-600 transition-colors">Perfil</a>
                            <a href="perfil.php" class="block px-4 py-2 hover:bg-gray-600 transition-colors">Sacar</a>
                            <a href="logout.php" class="block px-4 py-2 hover:bg-gray-600 transition-colors">Sair</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="flex gap-3">
                    <button onclick="abrirModal('login')" class="text-sm text-white hover:text-green-300 px-4 py-2 rounded-lg border border-slate-600 hover:border-green-400 transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Entrar
                    </button>
                    <button onclick="abrirModal('register')" class="btn-primary text-white text-sm px-6 py-2 rounded-lg font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Registrar
                    </button>
                </div>
            <?php endif; ?>
            <!-- Mobile Auth Buttons -->
            <?php if ($usuarioLogado): ?>
                <div class="mobile-user-info">
                    <span class="mobile-saldo-display">R$ <?= number_format($saldo, 2, ',', '.') ?></span>
                    <button onclick="window.location.href='perfil.php'" class="mobile-depositar-btn">
                        Sacar
                    </button>
                </div>
            <?php else: ?>
                <div class="mobile-auth-buttons">
                    <button onclick="abrirModal('login')" class="mobile-btn-login flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Entrar
                    </button>
                    <button onclick="abrirModal('register')" class="mobile-btn-register flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Registrar
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <div>
        <!-- Banner Carousel Section -->
        <section class="py-8 px-4">
            <div class="max-w-6xl mx-auto">
                <div class="carousel-container">
                    <!-- Slide 1 -->
                    <div class="carousel-slide active" style="background-image: url('img/NOVOS-BANNER-RASPA.webp')">
                        <div class="">
                            <div class="carousel-content">
                            </div>
                        </div>
                    </div>
                    <!-- Slide 2 -->
                    <div class="carousel-slide" style="background-image: url('img/NOVOS-BANNER-RASPA2 (1).webp')">
                        <div class="">
                            <div class="carousel-content">
                            </div>
                        </div>
                    </div>
                    <!-- Indicators -->
                    <div class="carousel-indicators">
                        <div class="indicator active" onclick="goToSlide(0)"></div>
                        <div class="indicator" onclick="goToSlide(1)"></div>
                    </div>
                </div>

                <!-- Live Winners Carousel - LAYOUT HORIZONTAL SEMPRE -->
                <div class="mt-8 p-4">
                    <div class="live-winners-section">
                        <!-- Título "AO VIVO" sempre horizontal -->
                        <div class="live-winners-title">
                            <svg viewBox="0 0 59 60" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 md:w-10 md:h-10 flex-shrink-0">
                                <path d="M2.381 31.8854L0.250732 32.1093L5.76436 16.3468L8.04082 16.1075L13.5753 30.7088L11.4242 30.9349L10.0667 27.2976L3.71764 27.9649L2.381 31.8854ZM6.64153 19.5306L4.34418 26.114L9.461 25.5762L7.14277 19.4779C7.101 19.3283 7.05227 19.1794 6.99657 19.0313C6.94088 18.8691 6.90607 18.7328 6.89215 18.6222C6.8643 18.7372 6.82949 18.8808 6.78772 19.0532C6.74595 19.2116 6.69722 19.3707 6.64153 19.5306Z" fill="#7B869D"></path>
                                <path d="M28.5469 21.5332C28.5469 23.0732 28.2336 24.4711 27.6071 25.727C26.9945 26.9674 26.1382 27.9814 25.0382 28.769C23.9522 29.5411 22.6922 30.0026 21.2581 30.1533C19.8518 30.3011 18.5987 30.1038 17.4988 29.5614C16.4128 29.0036 15.5634 28.1688 14.9508 27.0572C14.3382 25.9456 14.0319 24.6128 14.0319 23.0588C14.0319 21.5188 14.3382 20.1286 14.9508 18.8882C15.5774 17.6464 16.4336 16.6324 17.5197 15.8462C18.6057 15.0601 19.8588 14.5924 21.2789 14.4431C22.7131 14.2924 23.9731 14.4959 25.0591 15.0538C26.1451 15.6117 26.9945 16.4464 27.6071 17.558C28.2336 18.6681 28.5469 19.9932 28.5469 21.5332ZM26.3958 21.7593C26.3958 20.5833 26.18 19.577 25.7483 18.7404C25.3306 17.9023 24.7389 17.2855 23.9731 16.8899C23.2073 16.4804 22.3093 16.3298 21.2789 16.4381C20.2625 16.5449 19.3715 16.8836 18.6057 17.4541C17.8399 18.0106 17.2412 18.7525 16.8096 19.6799C16.3919 20.6058 16.183 21.6567 16.183 22.8327C16.183 24.0087 16.3919 25.0158 16.8096 25.8539C17.2412 26.6905 17.8399 27.3136 18.6057 27.7231C19.3715 28.1326 20.2625 28.2839 21.2789 28.1771C22.3093 28.0688 23.2073 27.7294 23.9731 27.1589C24.7389 26.5745 25.3306 25.8193 25.7483 24.8934C26.18 23.966 26.3958 22.9213 26.3958 21.7593Z" fill="#7B869D"></path>
                                <path d="M5.74539 52.1851L0.200195 37.8724L3.66344 37.5084L6.46607 44.7421C6.63956 45.1801 6.79971 45.6397 6.94652 46.1208C7.09332 46.6018 7.2468 47.156 7.40695 47.7833C7.59379 47.0525 7.76061 46.4445 7.90742 45.9594C8.06757 45.4729 8.22772 44.9998 8.38787 44.5401L11.1505 36.7215L14.5336 36.3659L9.08853 51.8337L5.74539 52.1851Z" fill="#00E880"></path>
                                <path d="M19.3247 35.8623V50.7578L16.0816 51.0987V36.2032L19.3247 35.8623Z" fill="#00E880"></path>
                                <path d="M26.4195 50.0121L20.8743 35.6995L24.3375 35.3355L27.1401 42.5692C27.3136 43.0072 27.4738 43.4667 27.6206 43.9478C27.7674 44.4289 27.9209 44.9831 28.081 45.6104C28.2679 44.8795 28.4347 44.2716 28.5815 43.7864C28.7416 43.2999 28.9018 42.8268 29.0619 42.3672L31.8245 34.5486L35.2077 34.193L29.7626 49.6608L26.4195 50.0121Z" fill="#00E880"></path>
                                <path d="M49.647 40.1029C49.647 41.6193 49.3401 42.9935 48.7261 44.2255C48.1122 45.4441 47.2581 46.4397 46.1637 47.2123C45.0694 47.9714 43.8015 48.4268 42.3602 48.5782C40.9322 48.7283 39.671 48.5388 38.5766 48.0097C37.4956 47.4658 36.6482 46.6491 36.0343 45.5595C35.4337 44.4686 35.1334 43.1649 35.1334 41.6485C35.1334 40.1321 35.4404 38.7646 36.0543 37.5461C36.6682 36.314 37.5156 35.3192 38.5967 34.5614C39.691 33.7889 40.9522 33.3275 42.3802 33.1774C43.8216 33.0259 45.0827 33.2222 46.1637 33.7661C47.2581 34.2952 48.1122 35.1045 48.7261 36.1941C49.3401 37.2836 49.647 38.5866 49.647 40.1029ZM46.2238 40.4627C46.2238 39.51 46.0703 38.7142 45.7634 38.0755C45.4564 37.4234 45.016 36.9463 44.4421 36.6443C43.8816 36.3409 43.201 36.2313 42.4002 36.3155C41.5995 36.3996 40.9122 36.653 40.3383 37.0757C39.7644 37.4983 39.324 38.0679 39.017 38.7846C38.7101 39.4878 38.5566 40.3158 38.5566 41.2686C38.5566 42.2214 38.7101 43.0238 39.017 43.6759C39.324 44.3281 39.7644 44.8051 40.3383 45.1071C40.9122 45.4091 41.5995 45.5181 42.4002 45.4339C43.201 45.3497 43.8816 45.097 44.4421 44.6758C45.016 44.2398 45.4564 43.6634 45.7634 42.9467C46.0703 42.2301 46.2238 41.4021 46.2238 40.4627Z" fill="#00E880"></path>
                                <circle cx="39" cy="20" r="6" fill="#222733"></circle>
                                <g filter="url(#filter0_d_726_17235)">
                                    <circle cx="39" cy="20" r="3.75" fill="#00E880"></circle>
                                </g>
                                <defs>
                                    <filter id="filter0_d_726_17235" x="31.25" y="16.25" width="15.5" height="15.5" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                                        <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                                        <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                                        <feOffset dy="4"/>
                                        <feGaussianBlur stdDeviation="2"/>
                                        <feComposite in2="hardAlpha" operator="out"/>
                                        <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.909804 0 0 0 0 0.501961 0 0 0 0.25 0"/>
                                        <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_726_17235"/>
                                        <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_726_17235" result="shape"/>
                                    </filter>
                                </defs>
                            </svg>
                            <div class="text-white">
                            </div>
                        </div>
                                                
                        <!-- Carousel dos ganhadores -->
                        <div class="live-winners-carousel">
                            <div class="winners-carousel-container">
                                <div class="winners-carousel">
                                    <!-- Winner Card 1 -->
                                    <div class="winner-card">
                                        <img src="img/1K.webp" class="object-contain rounded" alt="1000 Reais">
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-medium text-amber-400/75 text-ellipsis overflow-hidden whitespace-nowrap">Maria S***</span>
                                            <span class="font-medium text-gray-300 text-ellipsis overflow-hidden whitespace-nowrap text-xs">1000 Reais</span>
                                            <span class="font-semibold text-xs"><span class="text-emerald-300">R$ </span>1.000,00</span>
                                        </div>
                                    </div>
                                    <!-- Winner Card 2 -->
                                    <div class="winner-card">
                                        <img src="img/2K.webp" class="object-contain rounded" alt="2000 Reais">
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-medium text-amber-400/75 text-ellipsis overflow-hidden whitespace-nowrap">João P****</span>
                                            <span class="font-medium text-gray-300 text-ellipsis overflow-hidden whitespace-nowrap text-xs">2000 Reais</span>
                                            <span class="font-semibold text-xs"><span class="text-emerald-300">R$ </span>2.000,00</span>
                                        </div>
                                    </div>
                                    <!-- Winner Card 3 -->
                                    <div class="winner-card">
                                        <img src="img/5 REAIS.webp" class="object-contain rounded" alt="5 Reais">
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-medium text-amber-400/75 text-ellipsis overflow-hidden whitespace-nowrap">Ana C*****</span>
                                            <span class="font-medium text-gray-300 text-ellipsis overflow-hidden whitespace-nowrap text-xs">5 Reais</span>
                                            <span class="font-semibold text-xs"><span class="text-emerald-300">R$ </span>5,00</span>
                                        </div>
                                    </div>
                                    <!-- Winner Card 4 -->
                                    <div class="winner-card">
                                        <img src="img/5.webp" class="object-contain rounded" alt="5000 Reais">
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-medium text-amber-400/75 text-ellipsis overflow-hidden whitespace-nowrap">Carlos M***</span>
                                            <span class="font-medium text-gray-300 text-ellipsis overflow-hidden whitespace-nowrap text-xs">5000 Reais</span>
                                            <span class="font-semibold text-xs"><span class="text-emerald-300">R$ </span>5.000,00</span>
                                        </div>
                                    </div>
                                    <!-- Winner Card 5 -->
                                    <div class="winner-card">
                                        <img src="img/10.webp" class="object-contain rounded" alt="10000 Reais">
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-medium text-amber-400/75 text-ellipsis overflow-hidden whitespace-nowrap">Fernanda L****</span>
                                            <span class="font-medium text-gray-300 text-ellipsis overflow-hidden whitespace-nowrap text-xs">10000 Reais</span>
                                            <span class="font-semibold text-xs"><span class="text-emerald-300">R$ </span>10.000,00</span>
                                        </div>
                                    </div>
                                    <!-- Winner Card 6 -->
                                    <div class="winner-card">
                                        <img src="img/50-CENTAVOS-2.webp" class="object-contain rounded" alt="50 Centavos">
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-medium text-amber-400/75 text-ellipsis overflow-hidden whitespace-nowrap">Roberto S****</span>
                                            <span class="font-medium text-gray-300 text-ellipsis overflow-hidden whitespace-nowrap text-xs">50 Centavos</span>
                                            <span class="font-semibold text-xs"><span class="text-emerald-300">R$ </span>0,50</span>
                                        </div>
                                    </div>
                                    <!-- Winner Card 7 -->
                                    <div class="winner-card">
                                        <img src="img/500-REAIS.webp" class="object-contain rounded" alt="500 Reais">
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-medium text-amber-400/75 text-ellipsis overflow-hidden whitespace-nowrap">Luciana M****</span>
                                            <span class="font-medium text-gray-300 text-ellipsis overflow-hidden whitespace-nowrap text-xs">500 Reais</span>
                                            <span class="font-semibold text-xs"><span class="text-emerald-300">R$ </span>500,00</span>
                                        </div>
                                    </div>
                                    <!-- Duplicate cards for seamless loop -->
                                    <div class="winner-card">
                                        <img src="img/1K.webp" class="object-contain rounded" alt="1000 Reais">
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-medium text-amber-400/75 text-ellipsis overflow-hidden whitespace-nowrap">Maria S***</span>
                                            <span class="font-medium text-gray-300 text-ellipsis overflow-hidden whitespace-nowrap text-xs">1000 Reais</span>
                                            <span class="font-semibold text-xs"><span class="text-emerald-300">R$ </span> 1.000,00</span>
                                        </div>
                                    </div>
                                    <div class="winner-card">
                                        <img src="img/2K.webp" class="object-contain rounded" alt="2000 Reais">
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-medium text-amber-400/75 text-ellipsis overflow-hidden whitespace-nowrap">João P****</span>
                                            <span class="font-medium text-gray-300 text-ellipsis overflow-hidden whitespace-nowrap text-xs">2000 Reais</span>
                                            <span class="font-semibold text-xs"><span class="text-emerald-300">R$ </span> 2.000,00</span>
                                        </div>
                                    </div>
                                    <div class="winner-card">
                                        <img src="img/5 REAIS.webp" class="object-contain rounded" alt="5 Reais">
                                        <div class="flex flex-col min-w-0">
                                            <span class="font-medium text-amber-400/75 text-ellipsis overflow-hidden whitespace-nowrap">Ana C*****</span>
                                            <span class="font-medium text-gray-300 text-ellipsis overflow-hidden whitespace-nowrap text-xs">5 Reais</span>
                                            <span class="font-semibold text-xs"><span class="text-emerald-300">R$ </span> 5,00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NOVA SEÇÃO: Título Destaques -->
                <div class="destaques-header">
                    <svg width="1em" height="1em" fill="currentColor" class="destaques-icon bi bi-fire text-amber-400" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                        <path d="M8 16c3.314 0 6-2 6-5.5 0-1.5-.5-4-2.5-6 .25 1.5-1.25 2-1.25 2C11 4 9 .5 6 0c.357 2 .5 4-2 6-1.25 1-2 2.729-2 4.5C2 14 4.686 16 8 16m0-1c-1.657 0-3-1-3-2.75 0-.75.25-2 1.25-3C6.125 10 7 10.5 7 10.5c-.375-1.25.5-3.25 2-3.5-.179 1-.25 2 1 3 .625.5 1 1.364 1 2.25C11 14 9.657 15 8 15"></path>
                    </svg>
                    <h2 class="destaques-title">Destaques</h2>
                </div>

                <!-- Raspadinhas Grid -->
                <div class="raspadinha-grid">
                    <!-- Raspadinha R$ 1,00 -->
                    <div class="raspadinha-card">
                        <img src="https://i.ibb.co/dTqBHS1/PREMIOS-DIVERSOS-1.jpg" alt="Ícone" class="w-full h-16 object-cover mt-3 sm:mt-3 mb-4 rounded-md" />
                        <p class="prize-text">Prêmios até<br>R$ 1.000,00 NO PIX</p>
                        <span class="price-badge green">R$ 1,00</span>
                        <p class="game-description">Sonho de Consumo 😍<br>Prêmio até: R$ 1.000,00</p>
                        <?php if ($usuarioLogado): ?>
                            <a href="jogo.php?valor=1" class="play-button">
                                <i class="fas fa-play mr-2"></i> JOGAR AGORA
                            </a>
                        <?php else: ?>
                            <button onclick="abrirModal('login')" class="play-button login-required">
                                <i class="fas fa-sign-in-alt mr-2"></i> ENTRAR PARA JOGAR
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Raspadinha R$ 5,00 -->
                    <div class="raspadinha-card">
                        <img src="https://i.ibb.co/twq9SSqZ/PADR-O-02.jpg" alt="Ícone" class="w-full h-16 object-cover mt-3 sm:mt-3 mb-4 rounded-md" />
                        <p class="prize-text">Prêmios até<br>R$ 5.000,00 NO PIX</p>
                        <span class="price-badge orange">R$ 5,00</span>
                        <p class="game-description">Raspe da Emoção<br>Prêmio até: R$ 5.000,00</p>
                        <?php if ($usuarioLogado): ?>
                            <a href="jogo.php?valor=5" class="play-button">
                                <i class="fas fa-play mr-2"></i> JOGAR AGORA
                            </a>
                        <?php else: ?>
                            <button onclick="abrirModal('login')" class="play-button login-required">
                                <i class="fas fa-sign-in-alt mr-2"></i> ENTRAR PARA JOGAR
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Raspadinha R$ 10,00 -->
                    <div class="raspadinha-card">
                        <img src="https://i.ibb.co/V0GZT5V2/PADR-O-03.jpg" alt="Ícone" class="w-full h-16 object-cover mt-3 sm:mt-3 mb-4 rounded-md" />
                        <p class="prize-text">Prêmios até<br>R$ 6.300,00 NO PIX</p>
                        <span class="price-badge red">R$ 10,00</span>
                        <p class="game-description">Me mimei<br>Prêmio até: R$ 6.300,00</p>
                        <?php if ($usuarioLogado): ?>
                            <a href="jogo.php?valor=10" class="play-button">
                                <i class="fas fa-play mr-2"></i> JOGAR AGORA
                            </a>
                        <?php else: ?>
                            <button onclick="abrirModal('login')" class="play-button login-required">
                                <i class="fas fa-sign-in-alt mr-2"></i> ENTRAR PARA JOGAR
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Raspadinha R$ 20,00 -->
                    <div class="raspadinha-card">
                        <img src="https://i.ibb.co/hRXjDdPh/BIKE-MAQUINA-MOTO.jpg" alt="Ícone" class="w-full h-16 object-cover mt-3 sm:mt-3 mb-4 rounded-md" />
                        <p class="prize-text">Prêmios até<br>R$ 7.500,00 NO PIX</p>
                        <span class="price-badge" style="background: linear-gradient(135deg, #8b5cf6, #a855f7); color: white;">R$ 20,00</span>
                        <p class="game-description">Super Prêmios<br>Prêmio até: R$ 7.000,00</p>
                        <?php if ($usuarioLogado): ?>
                            <a href="jogo.php?valor=20" class="play-button">
                                <i class="fas fa-play mr-2"></i> JOGAR AGORA
                            </a>
                        <?php else: ?>
                            <button onclick="abrirModal('login')" class="play-button login-required">
                                <i class="fas fa-sign-in-alt mr-2"></i> ENTRAR PARA JOGAR
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Raspadinha R$ 50,00 -->
                    <div class="raspadinha-card">
                        <img src="https://i.ibb.co/HLDpqfR0/CONSOLES.jpg" alt="Ícone" class="w-full h-16 object-cover mt-3 sm:mt-3 mb-4 rounded-md" />
                        <p class="prize-text">Prêmios até<br>R$ 11.000,00 NO PIX</p>
                        <span class="price-badge green">R$ 50,00</span>
                        <p class="game-description">Sonho de Consumo 😍<br>Prêmio até: R$ 11.000,00</p>
                        <?php if ($usuarioLogado): ?>
                            <a href="jogo.php?valor=50" class="play-button">
                                <i class="fas fa-play mr-2"></i> JOGAR AGORA
                            </a>
                        <?php else: ?>
                            <button onclick="abrirModal('login')" class="play-button login-required">
                                <i class="fas fa-sign-in-alt mr-2"></i> ENTRAR PARA JOGAR
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Raspadinha R$ 100,00 -->
                    <div class="raspadinha-card">
                        <img src="https://i.ibb.co/d05zpNfL/luxo-1.jpg" alt="Ícone" class="w-full h-16 object-cover mt-3 sm:mt-3 mb-4 rounded-md" />
                        <p class="prize-text">Prêmios até<br>R$ 14.000,00 NO PIX</p>
                        <span class="price-badge green">R$ 100,00</span>
                        <p class="game-description">Sonho de Consumo 😍<br>Prêmio até: R$ 14.000,00</p>
                        <?php if ($usuarioLogado): ?>
                            <a href="jogo.php?valor=100" class="play-button">
                                <i class="fas fa-play mr-2"></i> JOGAR AGORA
                            </a>
                        <?php else: ?>
                            <button onclick="abrirModal('login')" class="play-button login-required">
                                <i class="fas fa-sign-in-alt mr-2"></i> ENTRAR PARA JOGAR
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- NOVO FOOTER - Baseado na imagem fornecida -->
        <footer class="footer-new">
    <div class="footer-content">
        <!-- Seção do Logo e Copyright -->
        <div class="footer-logo-section">
            <div class="footer-logo">
                <img src="/img/logo.webp" alt="Raspa Sorte Logo" class="footer-logo-image">
            </div>
            <div class="footer-copyright">
                © 2025 raspagreen.com. Todos os direitos reservados.
            </div>
            <div class="footer-disclaimer">
                Raspadinhas e outros jogos de azar são regulamentados e cobertos pela nossa licença de jogos. Jogue com responsabilidade.
            </div>
        </div>

        <!-- Container para as duas colunas de links (mobile) -->
        <div class="footer-links-container">
            <!-- Seção Regulamentos -->
            <div class="footer-links-section">
                <h3 class="footer-links-title">Regulamentos</h3>
                <div class="footer-links-list">
                    <a href="#" class="footer-link">Jogo responsável</a>
                    <a href="#" class="footer-link">Política de Privacidade</a>
                    <a href="#" class="footer-link">Termos de Uso</a>
                </div>
            </div>

            <!-- Seção Ajuda -->
            <div class="footer-links-section">
                <h3 class="footer-links-title">Ajuda</h3>
                <div class="footer-links-list">
                    <a href="#" class="footer-link">Perguntas Frequentes</a>
                    <a href="#" class="footer-link">Como Jogar</a>
                    <a href="#" class="footer-link">Suporte Técnico</a>
                </div>
            </div>
        </div>
    </div>
</footer>
    </div>

    <!-- =============================== -->
    <!-- NAVEGAÇÃO MOBILE REFINADA -->
    <!-- =============================== -->
    <div class="bg-[#000000] rounded-xl border-t shadow-lg z-10 fixed bottom-2 left-2 right-2 px-2 h-[72px] flex items-center gap-x-2.5 md:hidden">
        <!-- Botão Início -->
        <button onclick="scrollToTop()" class="group flex flex-col items-center justify-center gap-1 text-center text-inherit select-none text-primary font-semibold flex-1 transition-transform active:scale-90">
            <div>
                <svg width="1em" height="1em" viewBox="0 0 512 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg" class="size-5">
                    <path d="M416 174.74V48h-80v58.45L256 32 0 272h64v208h144V320h96v160h144V272h64z"></path>
                </svg>
            </div>
            <span class="text-[0.7rem] font-medium">Início</span>
        </button>

        <!-- Botão Prêmios -->
        <button onclick="<?= $usuarioLogado ? "window.location.href='premios'" : "abrirModal('login')" ?>" class="group flex flex-col items-center justify-center gap-1 text-center text-inherit select-none flex-1 transition-transform active:scale-90">
            <div>
                <i class="fas fa-trophy text-[1.2rem]"></i>
            </div>
            <span class="text-[0.7rem] font-medium">Prêmios</span>
        </button>

        <!-- Botão Depositar -->
        <button onclick="<?= $usuarioLogado ? "abrirDeposito()" : "abrirModal('login')" ?>" class="group flex flex-col items-center justify-center gap-1 text-center text-inherit select-none -translate-y-[1.25rem]">
            <div class="bg-[#22c55e] rounded-full border-4 border-surface text-white p-3 transition-transform group-active:scale-90">
                <svg fill="none" viewBox="0 0 24 24" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-[1.6rem]">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 15v3m0 3v-3m0 0h-3m3 0h3"></path>
                    <path fill="currentColor" fill-rule="evenodd" d="M5 5a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h7.083A6 6 0 0 1 12 18c0-1.148.322-2.22.881-3.131A3 3 0 0 1 9 12a3 3 0 1 1 5.869.881A5.97 5.97 0 0 1 18 12c1.537 0 2.939.578 4 1.528V8a3 3 0 0 0-3-3zm7 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <span class="text-[0.7rem] font-medium">Depósitar</span>
        </button>

        <!-- Botão Indique -->
        <button onclick="<?= $usuarioLogado ? "window.location.href='affiliate_dashboard'" : "abrirModal('login')" ?>" class="group flex flex-col items-center justify-center gap-1 text-center text-inherit select-none flex-1 transition-transform active:scale-90">
            <div>
                <svg viewBox="0 0 640 512" fill="currentColor" width="1em" height="1em" xmlns="http://www.w3.org/2000/svg" class="size-5">
                    <path d="M96 128a128 128 0 1 1 256 0 128 128 0 1 1-256 0zM0 482.3C0 383.8 79.8 304 178.3 304h91.4c98.5 0 178.3 79.8 178.3 178.3 0 16.4-13.3 29.7-29.7 29.7H29.7C13.3 512 0 498.7 0 482.3zM504 312v-64h-64c-13.3 0-24-10.7-24-24s10.7-24 24-24h64v-64c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24-24h-64v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"></path>
                </svg>
            </div>
            <span class="text-[0.7rem] font-medium">Indique</span>
        </button>

        <!-- Botão Perfil -->
        <button onclick="<?= $usuarioLogado ? "window.location.href='perfil'" : "abrirModal('login')" ?>" class="group flex flex-col items-center justify-center gap-1 text-center text-inherit select-none flex-1 transition-transform active:scale-90">
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

            <!-- LOGIN -->
            <div id="modalLogin" class="hidden">
                <div class="modal-header text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-700 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h2 class="font-bold text-white">Entrar na Conta</h2>
                    <p class="text-gray-300">Acesse sua conta e continue jogando</p>
                </div>
                <form id="formLogin" class="space-y-3 mt-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-white">Email</label>
                        <input type="email" name="email" placeholder="seu@email.com" required class="form-input w-full rounded-lg bg-gray-100 border border-gray-300 text-black focus:border-green-500 focus:outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-white">Senha</label>
                        <input type="password" name="senha" placeholder="Sua senha" required class="form-input w-full rounded-lg bg-gray-100 border border-gray-300 text-black focus:border-green-500 focus:outline-none transition-colors">
                    </div>
                    <button type="submit" class="form-button w-full bg-gradient-to-r from-green-500 to-green-700 hover:from-white-600 hover:to-white-800 text-white font-bold rounded-lg transition-all transform hover:scale-105">
                        <i class="fas fa-sign-in-alt mr-2"></i> Entrar
                    </button>
                    <p id="loginErro" class="text-red-500 text-xs text-center hidden"></p>
                </form>
                <div class="text-center mt-4 pt-4 border-t border-gray-200">
                    <p class="text-xs text-white">
                        Não tem conta?
                        <button onclick="abrirModal('register')" class="text-green-500 font-semibold hover:underline">
                            Registre-se aqui
                        </button>
                    </p>
                </div>
            </div>

            <!-- REGISTER -->
            <div id="modalRegister" class="hidden">
                <div class="modal-header text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-700 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                    </div>
                    <h2 class="font-bold text-white">Criar Conta</h2>
                    <p class="text-gray-300">Cadastre-se e comece a ganhar agora</p>
                </div>
                <form id="formRegister" class="space-y-3 mt-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-white">Nome Completo</label>
                        <input type="text" name="nome" placeholder="Seu nome completo" required class="form-input w-full rounded-lg bg-gray-100 border border-gray-300 text-black focus:border-green-500 focus:outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-white">Email</label>
                        <input type="email" name="email" placeholder="seu@email.com" required class="form-input w-full rounded-lg bg-gray-100 border border-gray-300 text-black focus:border-green-500 focus:outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-white">Senha</label>
                        <input type="password" name="senha" placeholder="Crie uma senha forte" required class="form-input w-full rounded-lg bg-gray-100 border border-gray-300 text-black focus:border-green-500 focus:outline-none transition-colors">
                    </div>
                    <div class="flex items-start space-x-2">
                        <input type="checkbox" id="termos" required class="mt-1">
                        <label for="termos" class="text-xs text-white">
                            Concordo com os <a href="#" class="text-green-400 hover:underline">Termos de Uso</a> e <a href="#" class="text-green-400 hover:underline">Política de Privacidade</a>
                        </label>
                    </div>
                    <button type="submit" class="form-button w-full bg-gradient-to-r from-green-500 to-green-700 hover:from-green-600 hover:to-green-800 text-white font-bold rounded-lg transition-all transform hover:scale-105">
                        <i class="fas fa-rocket mr-2"></i> Criar Conta e Jogar
                    </button>
                </form>
                <div class="text-center mt-4 pt-4 border-t border-gray-200">
                    <p class="text-xs text-white">
                        Já tem conta?
                        <button onclick="abrirModal('login')" class="text-green-500 font-semibold hover:underline">
                            Faça login aqui
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($usuarioLogado): ?>
        <!-- Modal Depósito (apenas para usuários logados) -->
        <div id="modalDeposito" class="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center z-50 hidden">
            <div class="modal-compact bg-black text-white rounded-2xl shadow-2xl w-[90%] relative transform transition-all border border-green-500">
                <button onclick="fecharDeposito()" class="absolute top-3 right-3 text-gray-500 hover:text-red-500 font-bold text-xl transition-colors">
                    <i class="fas fa-times"></i>
                </button>
                <div class="modal-header text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-700 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fab fa-pix text-white text-xl"></i>
                    </div>
                    <h2 class="font-bold text-white">Depositar via Pix</h2>
                    <p class="text-gray-300">Adicione saldo à sua conta de forma rápida e segura</p>
                </div>
                <div class="space-y-3 mt-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1 text-white">Valor do Depósito</label>
                        <input id="valorDeposito" type="number" min="1" step="0.01" placeholder="Ex: 10,00" class="form-input w-full rounded-lg bg-gray-100 border border-gray-300 text-black focus:border-green-500 focus:outline-none transition-colors" />
                        <p class="text-xs text-gray-400 mt-1">Valor mínimo: R$ 1,00</p>
                    </div>
                    <button onclick="gerarPix()" class="form-button w-full bg-gradient-to-r from-green-500 to-green-700 hover:from-green-600 hover:to-green-800 text-white font-bold rounded-lg transition-all transform hover:scale-105">
                        <i class="fab fa-pix mr-2"></i> Gerar Código Pix
                    </button>
                    <button onclick="fecharDeposito()" class="form-button w-full bg-gray-600 hover:bg-gray-700 text-white font-bold rounded-lg transition-all">
                        Cancelar
                    </button>
                    <div id="resultadoPix" class="mt-4 text-sm text-left"></div>
                </div>
            </div>
        </div>

        <!-- Modal QR Code (apenas para usuários logados) -->
        <div id="qrMode" class="fixed inset-0 bg-black/90 backdrop-blur-sm flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-2xl shadow-2xl w-[90%] max-w-md p-6 relative">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Código QR Pix</h2>
                    <p class="text-gray-600">Escaneie o código ou copie o código Pix</p>
                </div>
                <div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-4 mb-6 text-center">
                    <img id="qrCodeImage" src="/placeholder.svg" alt="QR Code Pix" class="w-full max-w-xs mx-auto rounded-lg shadow-md" />
                </div>
                <div class="space-y-3">
                    <button id="qrCopyBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-copy"></i> Copiar código Pix
                    </button>
                    <button onclick="fecharQRMode()" class="w-full bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded-lg transition-all">
                        Cancelar
                    </button>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-4">
                    <p class="text-blue-800 text-sm flex items-center gap-2">
                        <i class="fas fa-clock"></i> Após o pagamento, o saldo será liberado automaticamente.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script>
        // Carousel Variables
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const indicators = document.querySelectorAll('.indicator');
        const totalSlides = slides.length;

        // Carousel Functions
        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        function goToSlide(index) {
            currentSlide = index;
            showSlide(currentSlide);
        }

        // Auto-advance carousel every 3 seconds
        setInterval(nextSlide, 3000);

        // Bottom Navigation Functions
        function setActiveNav(element) {
            // Remove active class from all nav items except central button
            document.querySelectorAll('.nav-item').forEach(item => {
                if (!item.classList.contains('central-button')) {
                    item.classList.remove('active');
                }
            });
            // Add active class to clicked item (unless it's the central button)
            if (!element.classList.contains('central-button')) {
                element.classList.add('active');
            }
        }

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Modal Functions
        function abrirModal(tipo) {
            document.getElementById('modalContainer').classList.remove('hidden');
            document.getElementById('modalLogin').classList.add('hidden');
            document.getElementById('modalRegister').classList.add('hidden');
            if (tipo === 'login') document.getElementById('modalLogin').classList.remove('hidden');
            if (tipo === 'register') document.getElementById('modalRegister').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            // Hide bottom navbar when modal opens
            const bottomNavbar = document.querySelector('.bottom-navbar');
            if (bottomNavbar) {
                bottomNavbar.style.transform = 'translateY(100%)';
                bottomNavbar.style.opacity = '0';
            }
        }

        function fecharModal() {
            document.getElementById('modalContainer').classList.add('hidden');
            document.body.style.overflow = 'auto';
            // Show bottom navbar when modal closes
            const bottomNavbar = document.querySelector('.bottom-navbar');
            if (bottomNavbar) {
                bottomNavbar.style.transform = 'translateY(0)';
                bottomNavbar.style.opacity = '1';
            }
        }

        <?php if ($usuarioLogado): ?>
            // Funções de depósito (apenas para usuários logados)
            function abrirDeposito() {
                document.getElementById('modalDeposito').classList.remove('hidden');
                document.getElementById('resultadoPix').innerHTML = "";
                document.body.style.overflow = 'hidden';
            }

            function fecharDeposito() {
                document.getElementById('modalDeposito').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            function abrirQRMode(qrCodeData, pixCode) {
                fecharDeposito();
                document.getElementById('qrCodeImage').src = qrCodeData;
                const copyBtn = document.getElementById('qrCopyBtn');
                copyBtn.onclick = () => copiarPixCode(pixCode);
                document.getElementById('qrMode').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }

            function fecharQRMode() {
                document.getElementById('qrMode').classList.add('hidden');
                document.body.style.overflow = 'auto';
            }

            function copiarPixCode(codigo) {
                navigator.clipboard.writeText(codigo).then(() => {
                    const copyBtn = document.getElementById('qrCopyBtn');
                    const originalText = copyBtn.innerHTML;
                    copyBtn.classList.add('bg-green-600');
                    copyBtn.classList.remove('bg-blue-600');
                    copyBtn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                    setTimeout(() => {
                        copyBtn.classList.remove('bg-green-600');
                        copyBtn.classList.add('bg-blue-600');
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
                    const res = await fetch("gerar_pix_bspay.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ valor })
                    });

                    const data = await res.json();

                    if (data.erro) {
                        alert(data.erro);
                        return;
                    }

                    const qrPayload = encodeURIComponent(data.qrcode);
                    const imgSrc = `https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${qrPayload}`;
                    abrirQRMode(imgSrc, data.qrcode);
                } catch (error) {
                    alert('Erro ao gerar código Pix. Tente novamente.');
                }
            }
        <?php endif; ?>

        // Smooth Scroll
        function scrollToSection(sectionId) {
            document.getElementById(sectionId).scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Close modal when clicking outside
        document.getElementById('modalContainer').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });

        <?php if ($usuarioLogado): ?>
            // Close modals when clicking outside (apenas para usuários logados)
            document.getElementById('modalDeposito')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharDeposito();
                }
            });

            document.getElementById('qrMode')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharQRMode();
                }
            });
        <?php endif; ?>

        // Form Handlers
        document.getElementById('formLogin').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            const errorElement = document.getElementById('loginErro');

            try {
                const res = await fetch('login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await res.json();

                if (result.sucesso) {
                    window.location.reload(); // Recarrega a página para mostrar o usuário logado
                } else {
                    errorElement.textContent = result.erro || 'Erro ao fazer login';
                    errorElement.classList.remove('hidden');
                }
            } catch (err) {
                errorElement.textContent = 'Erro de conexão. Tente novamente.';
                errorElement.classList.remove('hidden');
            }
        });

        document.getElementById('formRegister').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const dados = Object.fromEntries(formData.entries());

            try {
                const res = await fetch("register.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify(dados)
                });

                const resposta = await res.json();

                if (resposta.status === "sucesso") {
                    window.location.reload(); // Recarrega a página para mostrar o usuário logado
                } else {
                    alert(resposta.mensagem || 'Erro ao criar conta');
                }
            } catch (err) {
                alert('Erro de conexão. Tente novamente.');
            }
        });

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-slide-up');
                }
            });
        }, observerOptions);

        const observer2 = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-slide-up');
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.card-hover, .stats-counter, .raspadinha-card').forEach(el => {
            observer.observe(el);
        });

        // Observe elements for animation
        document.querySelectorAll('.card-hover, .stats-counter, .raspadinha-card').forEach(el => {
            observer2.observe(el);
        });

        // Counter animation
        function animateCounters() {
            const counters = document.querySelectorAll('.stats-counter');
            counters.forEach(counter => {
                const target = counter.textContent;
                const isNumber = /^\d+/.test(target);
                                
                if (isNumber) {
                    const finalNumber = parseInt(target.replace(/\D/g, ''));
                    let current = 0;
                    const increment = finalNumber / 50;
                                        
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= finalNumber) {
                            current = finalNumber;
                            clearInterval(timer);
                        }
                        counter.textContent = target.replace(/\d+/, Math.floor(current));
                    }, 30);
                }
            });
        }

        // Start counter animation when page loads
        window.addEventListener('load', () => {
            setTimeout(animateCounters, 500);
        });
    </script>
</body>
</html>
