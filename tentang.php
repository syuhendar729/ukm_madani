<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - UKM Madani</title>
    
     <!-- Favicon - Logo Madani di Tab -->
    <link rel="icon" type="image/x-icon" href="assets/images/logo-madani.png">
    <link rel="shortcut icon" href="assets/images/logo-madani.png">
    <link rel="apple-touch-icon" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/logo-madani.png">
    
    <!-- Meta tags untuk SEO -->
    <meta name="description" content="Mengenal lebih dekat UKM Madani ITERA - Lembaga Dakwah Kampus yang membangun generasi muslim berakhlak mulia. Visi, misi, dan program-program kami.">
    <meta name="keywords" content="tentang UKM Madani, profil organisasi, LDK ITERA, visi misi, sejarah UKM Madani, struktur organisasi">
    <meta name="author" content="UKM Madani ITERA">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Tentang Kami - UKM Madani ITERA">
    <meta property="og:description" content="Mengenal lebih dekat UKM Madani ITERA - Lembaga Dakwah Kampus yang membangun generasi muslim berakhlak mulia">
    <meta property="og:image" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/assets/images/logo-madani.png">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="UKM Madani ITERA">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Tentang Kami - UKM Madani ITERA">
    <meta name="twitter:description" content="Mengenal lebih dekat UKM Madani ITERA - Lembaga Dakwah Kampus yang membangun generasi muslim berakhlak mulia">
    <meta name="twitter:image" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/assets/images/logo-madani.png">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Mobile Nav CSS -->
    <link href="assets/css/mobile-nav.css" rel="stylesheet">
    <link href="assets/css/audit-fixes.css" rel="stylesheet">
    
    <!-- AOS Animation Library -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #1a5f3f;
            --secondary-color: #d4af37;
            --accent-color: #2d8659;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-glass: rgba(255, 255, 255, 0.1);
            --border-color: #e9ecef;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.15);
            --gradient: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
            --gradient-gold: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%);
        }

        [data-theme="dark"] {
            --text-primary: #ecf0f1;
            --text-secondary: #bdc3c7;
            --bg-primary: #2c3e50;
            --bg-secondary: #34495e;
            --bg-glass: rgba(0, 0, 0, 0.2);
            --border-color: #455a64;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-primary);
            overflow-x: hidden;
            transition: all 0.3s ease;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Progress Bar */
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background: var(--gradient-gold);
            z-index: 10000;
            transition: width 0.1s ease;
        }

        /* Header Enhanced - Minimal interference with mobile-nav.css */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            transition: all 0.3s ease;
        }

        .header.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-lg);
        }

        .navbar {
            padding: 15px 0;
            transition: padding 0.3s ease;
        }

        .header.scrolled .navbar {
            padding: 10px 0;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--primary-color);
            font-family: 'Amiri', serif;
            font-size: 1.8rem;
            font-weight: 700;
            transition: transform 0.3s ease;
        }

        .nav-brand:hover {
            transform: scale(1.05);
        }

        .nav-brand img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-5px) rotate(5deg); }
        }

        /* Remove all conflicting nav-link styles */
        /* Let mobile-nav.css handle all navigation styling */

        /* Hero Section Enhanced */
        .hero {
            background: var(--gradient);
            min-height: 100vh;
            width: 100vw;                           /* ← FULL WIDTH */
            margin-left: calc(-50vw + 50%);        /* ← BREAKOUT TECHNIQUE */
            margin-right: calc(-50vw + 50%);       /* ← BREAKOUT TECHNIQUE */
            display: flex;
            align-items: center;
            justify-content: center;               /* ← PERFECT CENTER */
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
        }

        /* Floating Geometric Elements */
        .floating-element {
            position: absolute;
            opacity: 0.1;
            animation: float-random 6s ease-in-out infinite;
        }

        .floating-element:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            top: 20%;
            right: 15%;
            animation-delay: 2s;
        }

        .floating-element:nth-child(3) {
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float-random {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg); 
                opacity: 0.1;
            }
            50% { 
                transform: translateY(-20px) rotate(180deg); 
                opacity: 0.2;
            }
        }

        .hero-content {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;                   /* ← CENTER HORIZONTAL */
            justify-content: center;               /* ← CENTER VERTICAL */
            text-align: center;                    /* ← CENTER TEXT */
        }

        .hero-title {
                margin: 0 auto 30px;                   /* ← AUTO MARGIN */

            font-size: clamp(3rem, 8vw, 5rem);
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #ffffff, #f4d03f, #ffffff);
            background-size: 200% 200%;
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .hero-subtitle {
        font-size: clamp(1.1rem, 3vw, 1.5rem);
            opacity: 0.9;               /* ← GANTI */
            max-width: 700px;           /* ← GANTI */
            margin: 0 auto 40px;        /* ← GANTI */
            line-height: 1.8;           /* ← GANTI */
            text-align: center;
            width: 100%;
                }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
            margin-bottom: 120px;
        }

        .btn-hero {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary-hero {
            background: var(--gradient-gold);
            color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        }

        .btn-outline-hero {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-outline-hero:hover {
            background: white;
            color: var(--primary-color);
        }

        /* Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 2rem;
            animation: bounce 2s infinite;
            cursor: pointer;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
            40% { transform: translateX(-50%) translateY(-10px); }
            60% { transform: translateX(-50%) translateY(-5px); }
        }

        /* Visi Misi Enhanced */
        .visi-misi {
            padding: 100px 0;
            background: var(--bg-secondary);
            position: relative;
        }

        .visi-misi::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="%231a5f3f" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23pattern)"/></svg>');
        }

        .section-header {
            text-align: center;
            margin-bottom: 80px;
            position: relative;
            z-index: 2;
        }

        .section-title {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }

        .section-title::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: var(--gradient-gold);
            opacity: 0.1;
            border-radius: 15px;
            z-index: -1;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--gradient-gold);
            border-radius: 2px;
        }

        .section-subtitle {
            font-size: clamp(1rem, 3vw, 1.3rem);
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .visi-misi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
            position: relative;
            z-index: 2;
        }

        .visi-card, .misi-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 50px;
            border-radius: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .visi-card::before, .misi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient);
        }

        .visi-card:hover, .misi-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
        }

        .visi-card h3, .misi-card h3 {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .visi-card h3 i, .misi-card h3 i {
            background: var(--gradient);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: var(--shadow);
        }

        .visi-card p {
            font-size: 1.2rem;
            line-height: 1.9;
            color: var(--text-primary);
        }

        .misi-card ul {
            list-style: none;
        }

        .misi-card li {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-primary);
            padding: 15px 0;
            padding-left: 40px;
            position: relative;
            border-bottom: 1px solid rgba(26, 95, 63, 0.1);
            transition: all 0.3s ease;
        }

        .misi-card li:hover {
            padding-left: 50px;
            color: var(--primary-color);
        }

        .misi-card li:last-child {
            border-bottom: none;
        }

        .misi-card li::before {
            content: '✦';
            position: absolute;
            left: 0;
            color: var(--secondary-color);
            font-weight: bold;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .misi-card li:hover::before {
            transform: rotate(180deg) scale(1.2);
        }

        /* Organization Chart Enhanced */
        .organigram {
            padding: 100px 0;
            background: var(--bg-primary);
            position: relative;
        }

        /* Division Layout Enhanced */
        .division-section {
            margin-bottom: 120px;
            position: relative;
            padding: 40px 0;
        }

        .division-header {
            text-align: center;
            margin-bottom: 60px;
            padding: 40px;
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            border: 2px solid var(--secondary-color);
            position: relative;
            overflow: hidden;
        }

        .division-header::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: var(--gradient-gold);
            border-radius: 27px;
            z-index: -1;
            opacity: 0.3;
        }

        .division-name {
            font-size: 3rem;
            font-weight: 800;
            background: var(--gradient);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .division-description {
            color: var(--text-secondary);
            font-size: 1.2rem;
            font-weight: 500;
        }

        /* Enhanced Avatar Cards */
        .avatar-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            z-index: 10;
        }

        .avatar-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .avatar-card:hover::before {
            left: 100%;
        }

        .avatar-card:hover {
            transform: translateY(-15px) rotateY(5deg);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            border-color: var(--secondary-color);
        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }

        .avatar-card:hover .avatar {
            transform: scale(1.1) rotateY(10deg);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        /* Enhanced Avatar Colors */
        .avatar.presidium {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .avatar.kepala {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .avatar.admin {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .avatar.koordinator {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .person-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }

        .avatar-card:hover .person-name {
            color: var(--primary-color);
        }

        .person-role {
            font-size: 1rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .person-details {
            font-size: 0.9rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        /* Layout Improvements */
        .presidium-layout {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 60px;
            position: relative;
        }

        .presidium-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 50px;
            flex-wrap: wrap;
            position: relative;
        }

        .division-layout {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 60px;
            position: relative;
        }

        .kepala-row {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            position: relative;
        }

        .admin-row {
            display: flex;
            justify-content: center;
            gap: 80px;
            margin-bottom: 50px;
            flex-wrap: wrap;
            position: relative;
        }

        .subdept-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            width: 100%;
        }

        /* Enhanced Subdepartment Cards */
        .subdept-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            border-left: 5px solid var(--secondary-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .subdept-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .subdept-card:hover::before {
            opacity: 0.05;
        }

        .subdept-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border-left-color: var(--primary-color);
        }

        .subdept-card h4 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .koordinator-info {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .koordinator-info .person-name {
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .anggota-list {
            list-style: none;
        }

        .anggota-list h5 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .anggota-list li {
            padding: 8px 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .anggota-list li:last-child {
            border-bottom: none;
        }

        .anggota-list li::before {
            content: '•';
            color: var(--secondary-color);
            font-weight: bold;
            font-size: 1.2rem;
        }

        /* Search Enhanced */
        .search-section {
            margin-bottom: 60px;
            text-align: center;
        }

        .search-container {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
        }

        .search-box {
            width: 100%;
            padding: 20px 60px 20px 25px;
            border: 2px solid var(--border-color);
            border-radius: 50px;
            font-size: 1.1rem;
            font-family: inherit;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 5px rgba(26, 95, 63, 0.1);
            transform: scale(1.02);
        }

        .search-icon {
            position: absolute;
            right: 25px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            font-size: 1.3rem;
            transition: all 0.3s ease;
        }

        .search-box:focus + .search-icon {
            color: var(--primary-color);
            transform: translateY(-50%) scale(1.1);
        }

        /* Modal Enhanced */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 20000;
            backdrop-filter: blur(10px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: modalFadeIn 0.4s ease;
        }

        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(30px);
            border-radius: 25px;
            padding: 50px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: modalSlideUp 0.4s ease;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 25px;
            background: var(--gradient);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            transform: rotate(90deg) scale(1.1);
            background: var(--secondary-color);
        }

        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fab:hover {
            transform: scale(1.1) rotate(360deg);
            box-shadow: 0 10px 30px rgba(26, 95, 63, 0.4);
        }

        .fab-menu {
            position: fixed;
            bottom: 100px;
            right: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 9998;
        }

        .fab-menu.active {
            opacity: 1;
            visibility: visible;
        }

        .fab-item {
            width: 50px;
            height: 50px;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .fab-item:hover {
            transform: scale(1.1);
            background: var(--primary-color);
        }

        /* Animations */
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Responsive Design Enhanced */
        @media (max-width: 1024px) {
            .visi-misi-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .admin-row {
                gap: 40px;
            }

            .subdept-grid {
                grid-template-columns: 1fr;
            }

            .hero-title {
                font-size: clamp(2.5rem, 8vw, 4rem);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }

            .hero {
                padding: 100px 0 60px;
                min-height: 80vh;
            }

            .visi-misi, .organigram {
                padding: 60px 0;
            }

            .visi-card, .misi-card {
                padding: 30px;
            }

            .presidium-row, .admin-row {
                flex-direction: column;
                gap: 30px;
                align-items: center;
            }

            .avatar {
                width: 80px;
                height: 80px;
                font-size: 1.6rem;
            }

            .avatar-card {
                padding: 25px;
                max-width: 300px;
                margin: 0 auto;
            }

            .modal-content {
                padding: 30px;
                margin: 20px;
            }

            .division-section {
                margin-bottom: 80px;
            }

            .fab, .fab-item {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }

            .fab-menu {
                bottom: 80px;
                right: 25px;
            }

            .fab {
                bottom: 25px;
                right: 25px;
                width: 50px;
                height: 50px;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2.5rem !important;
            }

            .hero-subtitle {
                font-size: 1.1rem !important;
            }

            .navbar {
                padding: 10px 0;
            }

            .nav-brand {
                font-size: 1.5rem !important;
            }

            .nav-brand img {
                width: 35px !important;
                height: 35px !important;
            }

            .visi-card, .misi-card {
                padding: 25px;
            }

            .visi-card h3, .misi-card h3 {
                font-size: 2rem;
            }

            .avatar {
                width: 70px;
                height: 70px;
                font-size: 1.4rem;
            }

            .avatar-card {
                padding: 20px;
            }

            .person-name {
                font-size: 1.1rem;
            }

            .person-role {
                font-size: 0.9rem;
            }

            .division-name {
                font-size: 2rem;
            }

            .search-box {
                padding: 15px 50px 15px 20px;
                font-size: 1rem;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--gradient);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Particle Background */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: var(--secondary-color);
            border-radius: 50%;
            opacity: 0.1;
            animation: float-particle 6s infinite linear;
        }

        @keyframes float-particle {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.1;
            }
            90% {
                opacity: 0.1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        /* Mobile Navigation CSS - Simulating external file */
        .nav-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 9998;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .nav-overlay.active {
            opacity: 1;
        }

        .mobile-toggle {
            display: none;
            flex-direction: column;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            z-index: 10002;
            position: relative;
        }

        .mobile-toggle span {
            width: 25px;
            height: 3px;
            background: var(--primary-color);
            margin: 3px 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .mobile-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }

        .mobile-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }

        .nav-links.desktop {
            display: flex;
        }

        .nav-links.mobile {
            display: none;
            position: fixed;
            top: 0;
            right: -100%;
            width: 320px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(30px);
            flex-direction: column;
            justify-content: flex-start;
            align-items: stretch;
            padding: 6rem 2rem 2rem;
            gap: 0;
            transition: all 0.3s ease;
            z-index: 9999;
            box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
            border-left: 1px solid rgba(255, 255, 255, 0.2);
            list-style: none;
        }

        .nav-links.mobile.active {
            right: 0;
        }

        .nav-links.mobile .nav-link {
            width: 100%;
            padding: 1rem 1.5rem;
            margin: 0.25rem 0;
            border-radius: 15px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .nav-links.mobile .nav-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .nav-links.mobile .nav-link i {
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        @media (max-width: 868px) {
            .nav-links.desktop {
                display: none;
            }

            .mobile-toggle {
                display: flex;
            }

            .nav-overlay {
                display: block;
            }

            .nav-links.mobile {
                display: flex;
            }
        }

        @media (max-width: 480px) {
            .nav-links.mobile {
                width: 280px;
                padding: 5rem 1.5rem 2rem;
            }

            .nav-links.mobile .nav-link {
                padding: 0.875rem 1.25rem;
                font-size: 1rem;
            }
        }
    </style>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #1a5f3f;
            --secondary-color: #d4af37;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --border-color: #e9ecef;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --gradient: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-primary);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ====== HEADER NAVBAR ====== */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar {
            padding: 15px 0;
        }

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--primary-color);
            font-family: 'Amiri', serif;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .nav-brand img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 30px;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        .back-link {
            background: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: #b8941f;
            transform: translateY(-2px);
        }

        /* ====== RESPONSIVE NAVBAR ====== */
        @media (max-width: 1024px) {
            .nav-brand {
                font-size: 1.5rem !important;
            }
                
            .nav-brand img {
                width: 35px !important;
                height: 35px !important;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
        }

        @media (max-width: 480px) {
            .navbar {
                padding: 10px 0;
            }

            .nav-brand {
                font-size: 1.3rem !important;
            }

            .nav-brand img {
                width: 30px !important;
                height: 30px !important;
            }
        }

        /* ====== HERO SECTION ====== */
        .hero {
            background: var(--gradient);
            color: white;
            padding: 120px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 100vh;        /* ← TAMBAHAN */
            display: flex;            /* ← TAMBAHAN */
            align-items: center;      /* ← TAMBAHAN */
            width: 100%;             /* ← TAMBAHAN */
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(212, 175, 55, 0.1) 0%, transparent 50%);
        }

        .hero-title {
            font-size: clamp(3rem, 8vw, 5rem);
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #ffffff, #f4d03f, #ffffff);  /* ← TAMBAHAN */
            background-size: 200% 200%;     /* ← TAMBAHAN */
            background-clip: text;          /* ← TAMBAHAN */
            -webkit-background-clip: text;  /* ← TAMBAHAN */
            -webkit-text-fill-color: transparent;  /* ← TAMBAHAN */
            animation: shimmer 3s ease-in-out infinite;  /* ← TAMBAHAN */
            text-align: center;             /* ← TAMBAHAN */
            width: 100%;                    /* ← TAMBAHAN */
        }


        .hero-title {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: clamp(1.1rem, 3vw, 1.3rem);
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* ====== CONTENT SECTIONS ====== */
        .section {
            padding: 100px 0;
            background: var(--bg-secondary);
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .section-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .text-center { text-align: center; }
        .mb-4 { margin-bottom: 2rem; }
        .mt-4 { margin-top: 2rem; }

        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .hero {
                padding: 100px 0 60px;
                min-height: 100vh;
                width: 100vw;                           /* ← KUNCI UNTUK FULL WIDTH */
                margin-left: calc(-50vw + 50%);        /* ← KUNCI UNTUK FULL WIDTH */
                margin-right: calc(-50vw + 50%);       /* ← KUNCI UNTmsUK FULL WIDTH */
            }
            
            .hero .container {
                width: 100%;
                max-width: 100%;
                padding: 0 20px;
            }
            
            .hero-content {
                padding: 0 15px;
                width: 100%;
            }
            
            .hero-title {
                font-size: clamp(2.5rem, 10vw, 3.5rem);
                text-align: center;                     /* ← MEMASTIKAN CENTER */
                margin: 0 auto 20px;                    /* ← MEMASTIKAN CENTER */
                display: block;                         /* ← MEMASTIKAN CENTER */
                width: 100%;                           /* ← MEMASTIKAN CENTER */
            }
        }
    </style>
</head>
</head>
<body>
    <!-- Progress Bar -->
    <div class="progress-bar"></div>

    <!-- Particles Background -->
    <div class="particles" id="particles"></div>

    <!-- Header dengan Mobile Support -->
    <header class="header" id="header">
        <!-- Navigation Overlay untuk Mobile -->
        <div class="nav-overlay" id="navOverlay"></div>
        
        <nav class="navbar">
            <div class="container">
                <!-- Mobile Toggle Button -->
                <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <a href="index.php" class="nav-brand">
                    <?php if (file_exists('assets/images/logo-madani.png')): ?>
                        <img src="assets/images/logo-madani.png" alt="UKM Madani Logo">
                    <?php else: ?>
                        <div style="width: 40px; height: 40px; background: var(--secondary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">M</div>
                    <?php endif; ?>
                    UKM MADANI
                </a>
                
                <!-- Desktop Navigation -->
                <ul class="nav-links desktop">
                    <li><a href="index.php" class="nav-link">Beranda</a></li>
                    <li><a href="tentang.php" class="nav-link" style="color: var(--primary-color);">Tentang</a></li>
                    <li><a href="berita.php" class="nav-link">Berita</a></li>
                    <li><a href="artikel.php" class="nav-link">Artikel</a></li>
                    <li><a href="galeri.php" class="nav-link">Galeri</a></li>
                    <li><a href="index.php#donation" class="nav-link">Infaq</a></li>
                    <li><a href="index.php#contact" class="nav-link">
                        <i class="fas fa-envelope"></i> Kontak
                    </a></li>
                </ul>

                <!-- Mobile Navigation -->
                <ul class="nav-links mobile" id="mobileNav">
                    <li><a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i> Beranda
                    </a></li>
                    <li><a href="tentang.php" class="nav-link" style="color: var(--primary-color); background: rgba(26, 95, 63, 0.1);">
                        <i class="fas fa-info-circle"></i> Tentang
                    </a></li>
                    <li><a href="berita.php" class="nav-link">
                        <i class="fas fa-newspaper"></i> Berita
                    </a></li>
                    <li><a href="artikel.php" class="nav-link">
                        <i class="fas fa-pen-fancy"></i> Artikel
                    </a></li>
                    <li><a href="galeri.php" class="nav-link">
                        <i class="fas fa-images"></i> Galeri
                    </a></li>
                    <li><a href="index.php#donation" class="nav-link">
                        <i class="fas fa-hand-holding-heart"></i> Infaq
                    </a></li>
                    <li><a href="index.php#contact" class="nav-link">
                        <i class="fas fa-envelope"></i> Kontak
                    </a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Hero Section Enhanced -->
    <section class="hero">
        <!-- Floating Elements -->
        <div class="floating-element">
            <i class="fas fa-mosque" style="font-size: 3rem; color: white;"></i>
        </div>
        <div class="floating-element">
            <i class="fas fa-star-and-crescent" style="font-size: 2.5rem; color: white;"></i>
        </div>
        <div class="floating-element">
            <i class="fas fa-users" style="font-size: 3.5rem; color: white;"></i>
        </div>

        <div class="container">
            <div class="hero-content" data-aos="fade-up" data-aos-duration="1000">
                <h1 class="hero-title">Tentang UKM Madani</h1>
                <p class="hero-subtitle">
                    Mengenal lebih dalam visi, misi, dan struktur organisasi<br>
                    <strong>Mahasiswa Peradaban Islam ITERA</strong>
                </p>
                <div class="hero-buttons">
                    <a href="#visi-misi" class="btn-hero btn-primary-hero">
                        <i class="fas fa-eye"></i> Lihat Visi & Misi
                    </a>
                    <a href="#organigram" class="btn-hero btn-outline-hero">
                        <i class="fas fa-users"></i> Struktur Organisasi
                    </a>
                </div>
            </div>
        </div>

        <div class="scroll-indicator" onclick="document.getElementById('visi-misi').scrollIntoView({behavior: 'smooth'})">
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <!-- Visi Misi Enhanced -->
    <section id="visi-misi" class="visi-misi">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2 class="section-title">Visi & Misi</h2>
                <p class="section-subtitle">
                </p>
            </div>
            
            <div class="visi-misi-grid">
                <div class="visi-card" data-aos="fade-right" data-aos-delay="200">
                    <h3><i class="fas fa-eye"></i> Visi</h3>
                    <p>Menjadikan LDK Madani ITERA sebagai rumah dan sarana dakwah berkelanjutan yang aktif, inovatif, dan inklusif untuk mewujudkan kader yang berkualitas dan berintegritas berlandaskan Al-Quran dan Sunnah.</p>
                </div>
                
                <div class="misi-card" data-aos="fade-left" data-aos-delay="400">
                    <h3><i class="fas fa-bullseye"></i> Misi</h3>
                    <ul>
                        <li>Menjadikan Alquran dan Sunnah sebagai landasan utama LDK Madani ITERA dalam kehidupan sehari-hari.</li>
                        <li>Menguatkan ukhuwah islamiyah sehingga tercipta rasa komitmen dalam setiap kader untuk menjalankan perannya.</li>
                        <li>Meningkatkan sistem pembinaan yang berkualitas dan terstruktur.</li>
                        <li>Mengembangkan metode dakwah melalui proses penuntutan ilmu, pengamalan konsisten dan penyampaian ilmu yang tepat sasaran.</li>
                        <li>Memperluas jaringan kolaborasi dakwah baik internal maupun eksternal.</li>
                        <li>Memberikan kontribusi terhadap isu permasalahan Islam baik dari cakupan lokal maupun global.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Organigram Section Enhanced -->
    <section id="organigram" class="organigram">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2 class="section-title">Struktur Organisasi</h2>
                <p class="section-subtitle">
                    Kepengurusan UKM Madani ITERA periode 2025/2026<br>
                </p>
            </div>

            <!-- Search Enhanced -->
            <div class="search-section" data-aos="fade-up" data-aos-delay="200">
                <div class="search-container">
                    <input type="text" id="searchBox" placeholder="Cari nama pengurus..." class="search-box">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>

            <!-- Presidium with Connections -->
            <div class="division-section" data-aos="fade-up" data-aos-delay="300">
                <div class="division-header">
                    <h3 class="division-name">PRESIDIUM</h3>
                    <p class="division-description">Badan Pengurus Harian MADANI</p>
                </div>
                
                <div class="presidium-layout">
                    <!-- Ketua Umum -->
                    <div class="presidium-row">
                        <div class="avatar-card" onclick="showPersonModal('Syuhada Rantisi', 'Ketua Umum', 'Teknik Informatika 2022', 'SR')" data-aos="zoom-in" data-aos-delay="400">
                            <div class="avatar presidium">SR</div>
                            <div class="person-name">Syuhada Rantisi</div>
                            <div class="person-role">Ketua Umum</div>
                            <div class="person-details">Teknik Informatika '22</div>
                        </div>
                    </div>
                    
                    <!-- Wakil Ketua -->
                    <div class="presidium-row">
                        <div class="avatar-card" onclick="showPersonModal('Hanifah Hamaasatul Adilah', 'Wakil Ketua Umum', 'Teknik Lingkungan 2022', 'HA')" data-aos="zoom-in" data-aos-delay="500">
                            <div class="avatar presidium">HA</div>
                            <div class="person-name">Hanifah Hamaasatul 'Adilah</div>
                            <div class="person-role">Wakil Ketua Umum</div>
                            <div class="person-details">Teknik Lingkungan '22</div>
                        </div>
                    </div>
                    
                    <!-- Sekretaris & Bendahara -->
                    <div class="presidium-row">
                        <div class="avatar-card" onclick="showPersonModal('M. Hirzan Al Ashri', 'Sekretaris 1', 'Perencanaan Wilayah Kota 2022', 'MH')" data-aos="zoom-in" data-aos-delay="600">
                            <div class="avatar admin">MH</div>
                            <div class="person-name">M. Hirzan Al Ashri</div>
                            <div class="person-role">Sekretaris 1</div>
                            <div class="person-details">PWK '22</div>
                        </div>
                        
                        <div class="avatar-card" onclick="showPersonModal('Annisa Widya', 'Sekretaris 2', 'Matematika 2022', 'AW')" data-aos="zoom-in" data-aos-delay="700">
                            <div class="avatar admin">AW</div>
                            <div class="person-name">Annisa Widya</div>
                            <div class="person-role">Sekretaris 2</div>
                            <div class="person-details">Matematika '22</div>
                        </div>
                        
                        <div class="avatar-card" onclick="showPersonModal('Fatan Assyidiqi', 'Bendahara 1', 'Teknik Geologi 2022', 'FA')" data-aos="zoom-in" data-aos-delay="800">
                            <div class="avatar admin">FA</div>
                            <div class="person-name">Fatan Assyidiqi</div>
                            <div class="person-role">Bendahara 1</div>
                            <div class="person-details">Teknik Geofisika '22</div>
                        </div>
                        
                        <div class="avatar-card" onclick="showPersonModal('Fayyaza Aqila Syafitri', 'Bendahara 2', 'Sains Data 2022', 'FS')" data-aos="zoom-in" data-aos-delay="900">
                            <div class="avatar admin">FS</div>
                            <div class="person-name">Fayyaza Aqila</div>
                            <div class="person-role">Bendahara 2</div>
                            <div class="person-details">Sains Data '22</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PSDM Division -->
            <div class="division-section" data-aos="fade-up" data-aos-delay="400">
                <div class="division-header">
                    <h3 class="division-name">PSDM</h3>
                    <p class="division-description">Pengembangan Sumber Daya MADANI</p>
                </div>
                
                <div class="division-layout">
                    <div class="kepala-row">
                        <div class="avatar-card" onclick="showPersonModal('Isnina Aida Bahri', 'Kepala Divisi PSDM', 'Teknologi Pangan 2022', 'IA')" data-aos="zoom-in" data-aos-delay="500">
                            <div class="avatar kepala">IA</div>
                            <div class="person-name">Isnina Aida Bahri</div>
                            <div class="person-role">Kepala Divisi</div>
                            <div class="person-details">Teknologi Pangan '22</div>
                        </div>
                    </div>
                    
                    <div class="admin-row">
                        <div class="avatar-card" onclick="showPersonModal('Tria Putriani', 'Sekretaris PSDM', 'Farmasi 2022', 'TP')" data-aos="zoom-in" data-aos-delay="600">
                            <div class="avatar admin">TP</div>
                            <div class="person-name">Tria Putriani</div>
                            <div class="person-role">Sekretaris</div>
                            <div class="person-details">Farmasi '22</div>
                        </div>
                        
                        <div class="avatar-card" onclick="showPersonModal('Ahmad Nasrulloh Syafi MT', 'Bendahara PSDM', 'Desain Komunikasi Visual 2022', 'AN')" data-aos="zoom-in" data-aos-delay="700">
                            <div class="avatar admin">AN</div>
                            <div class="person-name">Ahmad Nasrulloh</div>
                            <div class="person-role">Bendahara</div>
                            <div class="person-details">Desain Komunikasi Visual '22</div>
                        </div>
                    </div>
                    
                    <div class="subdept-grid">
                        <div class="subdept-card" data-aos="fade-up" data-aos-delay="800">
                            <h4><i class="fas fa-user-graduate"></i> KADERISASI</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Achmad Syahdian Harahap</div>
                                <div class="person-details">Teknik Pertambangan '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Rama Dhani Hairy</li>
                                <li>Dewi Lindu</li>
                                <li>Risma Widiastuti</li>
                                <li>Muhammad Sanjaya Ardy Wibowo</li>
                                <li>Halimah</li>
                            </div>
                        </div>
                        
                        <div class="subdept-card" data-aos="fade-up" data-aos-delay="900">
                            <h4><i class="fas fa-chalkboard-teacher"></i> MENTORING</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Aisyah Musfirah</div>
                                <div class="person-details">Sains Data '23</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Azka Tsabita</li>
                                <li>Chairunnisa Umpuan Ratu</li>
                                <li>Ali Rais Arifin</li>
                            </div>
                        </div>
                        
                        <div class="subdept-card" data-aos="fade-up" data-aos-delay="1000">
                            <h4><i class="fas fa-users"></i> PIM</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Putri Azzahra</div>
                                <div class="person-details">Teknik Biomedis '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Nadia Faraj Alyefaatin Simbolon</li>
                                <li>Isa Azzuhrissyam</li>
                                <li>Siti Muthia Choiril Anwa</li>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MPS Structure -->
            <div class="division-section">
                <div class="division-header">
                    <h3 class="division-name">MPS</h3>
                    <p class="division-description">Madani Public Services</p>
                </div>
                
                <div class="division-layout">
                    <div class="kepala-row">
                        <div class="avatar-card" onclick="showPersonModal('Abdi Romadoni', 'Kepala Divisi MPS', 'Teknik Mesin 2022', 'AR')">
                            <div class="avatar kepala">AR</div>
                            <div class="person-name">Abdi Romadoni</div>
                            <div class="person-role">Kepala Divisi</div>
                            <div class="person-details">Teknik Mesin '22</div>
                        </div>
                    </div>
                    
                    <div class="admin-row">
                        <div class="avatar-card" onclick="showPersonModal('Ajeng Kusuma Dewi', 'Sekretaris MPS', 'Matematika 2022', 'AK')">
                            <div class="avatar admin">AK</div>
                            <div class="person-name">Ajeng Kusuma Dewi</div>
                            <div class="person-role">Sekretaris</div>
                            <div class="person-details">Matematika '22</div>
                        </div>
                        
                        <div class="avatar-card" onclick="showPersonModal('Muhammad Abdul Hafiz', 'Bendahara MPS', 'Teknik Elektro 2022', 'MH')">
                            <div class="avatar admin">MH</div>
                            <div class="person-name">Muhammad Abdul Hafiz</div>
                            <div class="person-role">Bendahara</div>
                            <div class="person-details">Teknik Elektro '22</div>
                        </div>
                    </div>
                    
                    <div class="subdept-grid">
                        <div class="subdept-card">
                            <h4><i class="fas fa-home"></i> INTERNAL</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Dinna Fitri Aryanti</div>
                                <div class="person-details">Teknik Kelautan '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Rayhan Nur Aditya</li>
                                <li>Maulladi Apriansyah</li>
                            </div>
                        </div>
                        
                        <div class="subdept-card">
                            <h4><i class="fas fa-university"></i> INTRA KAMPUS</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Ugi Ardimin Hadi</div>
                                <div class="person-details">Teknik Kelautan '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Azzam Thoriqul Haq</li>
                                <li>Galuh Padma Andini</li>
                                <li>Anggun Apriliana</li>
                            </div>
                        </div>
                        
                        <div class="subdept-card">
                            <h4><i class="fas fa-globe"></i> EKSTRA KAMPUS</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Ghozy Waliyuddin</div>
                                <div class="person-details">Teknologi Industri Pertanian '23</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>M Esa Fauzan Akbar</li>
                                <li>Ahmad Heri Siswanto</li>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SYIAR Structure -->
            <div class="division-section">
                <div class="division-header">
                    <h3 class="division-name">SYIAR</h3>
                    <p class="division-description"> Islamic Knowledge & Dakwah Strategis</p>
                </div>
                
                <div class="division-layout">
                    <div class="kepala-row">
                        <div class="avatar-card" onclick="showPersonModal('Muhammad Kautsar Rahmatullah', 'Kepala Divisi SYIAR', 'Rekayasan Minyak & Gas 2022', 'MK')">
                            <div class="avatar kepala">MK</div>
                            <div class="person-name">Muhammad Kautsar Rahmatullah</div>
                            <div class="person-role">Kepala Divisi</div>
                            <div class="person-details">Rekayasan Minyak & Gas '22</div>
                        </div>
                    </div>
                    
                    <div class="admin-row">
                        <div class="avatar-card" onclick="showPersonModal('Naufal Raidy Wahyuda', 'Sekretaris SYIAR', 'Teknik Elektro 2022', 'NR')">
                            <div class="avatar admin">NR</div>
                            <div class="person-name">Naufal Raidy W</div>
                            <div class="person-role">Sekretaris</div>
                            <div class="person-details">Teknik Elektro '22</div>
                        </div>
                        
                        <div class="avatar-card" onclick="showPersonModal('Afrilia Khairunnisa', 'Bendahara SYIAR', 'Biomedical Engineering 2022', 'AK')">
                            <div class="avatar admin">AK</div>
                            <div class="person-name">Afrilia Khairunnisa</div>
                            <div class="person-role">Bendahara</div>
                            <div class="person-details">Teknik Biomedis '22</div>
                        </div>
                    </div>
                    
                    <div class="subdept-grid">
                        <div class="subdept-card">
                            <h4><i class="fas fa-book-quran"></i> ISLAMIC KNOWLEDGE</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Delfi Imelia Pitri</div>
                                <div class="person-details">Rekayasan Minyak & Gas '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Dinah Hisanah</li>
                                <li>Dwi Febriansyah</li>
                                <li>Ahmad Nurcholis</li>
                            </div>
                        </div>
                        
                        <div class="subdept-card">
                            <h4><i class="fas fa-bullhorn"></i> DAKWAH STRATEGIS</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Rahma Ramadhani Herliana</div>
                                <div class="person-details">Rekayasan Minyak & Gas '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Rifda Indah Cahyani</li>
                                <li>Nalsa Fathiya Rahman</li>
                                <li>Najwa Fitra Hanifah</li>
                                <li>Muhammad Naufal Alghani</li>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BUMM Structure -->
            <div class="division-section">
                <div class="division-header">
                    <h3 class="division-name">BUMM</h3>
                    <p class="division-description">Badan Usaha Milik MADANI</p>
                </div>
                
                <div class="division-layout">
                    <div class="kepala-row">
                        <div class="avatar-card" onclick="showPersonModal('Dewi Hotimatur Romdoni', 'Kepala Divisi BUMM', 'Fisika 2022', 'DH')">
                            <div class="avatar kepala">DH</div>
                            <div class="person-name">Dewi Hotimatur Romdoni</div>
                            <div class="person-role">Kepala Divisi</div>
                            <div class="person-details">Fisika '22</div>
                        </div>
                    </div>
                    
                    <div class="admin-row">
                        <div class="avatar-card" onclick="showPersonModal('Ferytha Ocha Dinata Putri', 'Sekretaris BUMM', 'Teknik Sipil 2022', 'FO')">
                            <div class="avatar admin">FO</div>
                            <div class="person-name">Ferytha Ocha</div>
                            <div class="person-role">Sekretaris</div>
                            <div class="person-details">Teknik Sipil '22</div>
                        </div>
                        
                        <div class="avatar-card" onclick="showPersonModal('Dio Rizky Pratama', 'Bendahara BUMM', 'Teknik Fisika 2022', 'DR')">
                            <div class="avatar admin">DR</div>
                            <div class="person-name">Dio Rizky Pratama</div>
                            <div class="person-role">Bendahara</div>
                            <div class="person-details">Teknik Fisika '22</div>
                        </div>
                    </div>
                    
                    <div class="subdept-grid">
                        <div class="subdept-card">
                            <h4><i class="fas fa-store"></i> MADANI STORE</h4>
                            <div class="koordinator-info">
                                <div class="person-name">A. Ibnu Hajar</div>
                                <div class="person-details">Teknologi Industri Pertanian '23</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Rifdah Dwi Nabillah</li>
                                <li>Muhammad Luthfi Aziz</li>
                                <li>Anjeli Dwi Safitri</li>
                            </div>
                        </div>
                        
                        <div class="subdept-card">
                            <h4><i class="fas fa-chart-line"></i> MADANI FINANCE</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Erisa</div>
                                <div class="person-details">Teknik Geofisika '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Rianda Khoirun Nisa</li>
                                <li>Rahmat Arifin Ilham</li>
                                <li>Urwah Abdul Manaf Panjaitan</li>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KRAMAD Structure -->
            <div class="division-section">
                <div class="division-header">
                    <h3 class="division-name">KRAMAD</h3>
                    <p class="division-description">Kreasi Dakwah MADANI</p>
                </div>
                
                <div class="division-layout">
                    <div class="kepala-row">
                        <div class="avatar-card" onclick="showPersonModal('Deffa Nurmalasari', 'Kepala Divisi KRAMAD', 'Desain Komunikasi Visual 2022', 'DN')">
                            <div class="avatar kepala">DN</div>
                            <div class="person-name">Deffa Nurmalasari</div>
                            <div class="person-role">Kepala Divisi</div>
                            <div class="person-details">Desain Komunikasi Visual '22</div>
                        </div>
                    </div>
                    
                    <div class="admin-row">
                        <div class="avatar-card" onclick="showPersonModal('Ayu Jannati Ali Putri', 'Sekretaris KRAMAD', 'Teknik Informatika 2022', 'AJ')">
                            <div class="avatar admin">AJ</div>
                            <div class="person-name">Ayu Jannati Ali Putri</div>
                            <div class="person-role">Sekretaris</div>
                            <div class="person-details">Teknik Informatika '22</div>
                        </div>
                        
                        <div class="avatar-card" onclick="showPersonModal('Hizba Jaisy Muhammad', 'Bendahara KRAMAD', 'Teknik Informatika 2022', 'HJ')">
                            <div class="avatar admin">HZ</div>
                            <div class="person-name">Hizba Jaisy Muhammad</div>
                            <div class="person-role">Bendahara</div>
                            <div class="person-details">Teknik Informatika '22</div>
                        </div>
                    </div>
                    
                    <div class="subdept-grid">
                        <div class="subdept-card">
                            <h4><i class="fas fa-laptop-code"></i> WEBSITE</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Aditya Wahyu Suhendar</div>
                                <div class="person-details">Teknik Informatika '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Sabda Arif</li>
                                <li>Alvin Saputra</li>
                            </div>
                        </div>
                        
                        <div class="subdept-card">
                            <h4><i class="fas fa-palette"></i> DESAIN</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Nabilah Shafa Marsa</div>
                                <div class="person-details">Desain Komunikasi Visual '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Nur Azizah Syahratul Jannah</li>
                                <li>Nia Amelia</li>
                                <li>Yulia Safari</li>
                            </div>
                        </div>
                        
                        <div class="subdept-card">
                            <h4><i class="fas fa-video"></i> VIDEOGRAFI</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Rahmat Sidiq</div>
                                <div class="person-details">Teknologi Pangan '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Hafizh Nabil Izzudin</li>
                                <li>Marhayani</li>
                                <li>Siti Khoirun Ni'mah</li>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AZZAHRA Structure -->
            <div class="division-section">
                <div class="division-header">
                    <h3 class="division-name">AZZAHRA</h3>
                    <p class="division-description">Divisi Kemuslimahan</p>
                </div>
                
                <div class="division-layout">
                    <div class="kepala-row">
                        <div class="avatar-card" onclick="showPersonModal('Cindi Dara Mardika', 'Kepala Divisi AZZAHRA', 'Kimia 2022', 'CD')">
                            <div class="avatar kepala">CD</div>
                            <div class="person-name">Cindi Dara Mardika</div>
                            <div class="person-role">Kepala Divisi</div>
                            <div class="person-details">Kimia '22</div>
                        </div>
                    </div>
                    
                    <div class="admin-row">
                        <div class="avatar-card" onclick="showPersonModal('Henni Lestari', 'Sekretaris AZZAHRA', 'Biologi 2022', 'HL')">
                            <div class="avatar admin">HL</div>
                            <div class="person-name">Henni Lestari</div>
                            <div class="person-role">Sekretaris</div>
                            <div class="person-details">Biologi '22</div>
                        </div>
                        
                        <div class="avatar-card" onclick="showPersonModal('Sela Fathimatuz Zahra', 'Bendahara AZZAHRA', 'Farmasi 2023', 'SF')">
                            <div class="avatar admin">SF</div>
                            <div class="person-name">Sela Fathimatuz Zahra</div>
                            <div class="person-role">Bendahara</div>
                            <div class="person-details">Farmasi '23</div>
                        </div>
                    </div>
                    
                    <div class="subdept-grid">
                        <div class="subdept-card">
                            <h4><i class="fas fa-female"></i> KEKELUARGAAN</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Fardita Ode Josan</div>
                                <div class="person-details">Farmasi '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Fatima Nayya Eldaini</li>
                                <li>Alya Novita Sari</li>
                                <li>Siti Nurqaidah</li>
                            </div>
                        </div>
                        
                        <div class="subdept-card">
                            <h4><i class="fas fa-broadcast-tower"></i> MEDIA</h4>
                            <div class="koordinator-info">
                                <div class="person-name">Siti Nur Fadillah</div>
                                <div class="person-details">Farmasi '22</div>
                            </div>
                            <div class="anggota-list">
                                <h5>Anggota:</h5>
                                <li>Lutfiya Marsha Hudayani</li>
                                <li>Lilis Ariska</li>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Enhanced -->
    <div class="modal" id="personModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
            <div id="modalContent">
                <!-- Content will be dynamically inserted here -->
            </div>
        </div>
    </div>

<!-- Floating Scroll to Top Button -->
<button class="fab" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" title="Scroll ke Atas">
    <i class="fas fa-arrow-up"></i>
</button>


    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="assets/js/mobile-nav.js"></script>
    <script src="assets/js/audit-fixes.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-out-cubic',
            once: true,
            offset: 100
        });

        // Progress Bar
        function updateProgressBar() {
            const scrollTop = document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrollPercent = (scrollTop / scrollHeight) * 100;
            document.querySelector('.progress-bar').style.width = scrollPercent + '%';
        }

        window.addEventListener('scroll', updateProgressBar);

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // FAB functionality
        const fabToggle = document.getElementById('fabToggle');
        const fabMenu = document.getElementById('fabMenu');
        let fabOpen = false;

        fabToggle.addEventListener('click', () => {
            fabOpen = !fabOpen;
            if (fabOpen) {
                fabMenu.classList.add('active');
                fabToggle.innerHTML = '<i class="fas fa-times"></i>';
                fabToggle.style.transform = 'rotate(45deg)';
            } else {
                fabMenu.classList.remove('active');
                fabToggle.innerHTML = '<i class="fas fa-plus"></i>';
                fabToggle.style.transform = 'rotate(0deg)';
            }
        });

        // Particles background
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 20;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.width = Math.random() * 4 + 2 + 'px';
                particle.style.height = particle.style.width;
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Search functionality enhanced
        function initSearch() {
            const searchBox = document.getElementById('searchBox');
            
            searchBox.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase();
                searchMembers(query);
            });
        }

        function searchMembers(query) {
            const avatarCards = document.querySelectorAll('.avatar-card');
            const divisionSections = document.querySelectorAll('.division-section');
            
            if (!query) {
                avatarCards.forEach(card => {
                    card.style.display = 'block';
                    card.style.animation = 'fadeIn 0.3s ease';
                });
                
                divisionSections.forEach(section => {
                    section.style.display = 'block';
                });
                return;
            }

            let hasResults = false;
            
            avatarCards.forEach(card => {
                const name = card.querySelector('.person-name')?.textContent.toLowerCase() || '';
                const role = card.querySelector('.person-role')?.textContent.toLowerCase() || '';
                const details = card.querySelector('.person-details')?.textContent.toLowerCase() || '';
                
                if (name.includes(query) || role.includes(query) || details.includes(query)) {
                    card.style.display = 'block';
                    card.style.animation = 'pulse 0.5s ease';
                    hasResults = true;
                    
                    // Show parent division section
                    let divisionSection = card.closest('.division-section');
                    if (divisionSection) {
                        divisionSection.style.display = 'block';
                    }
                } else {
                    card.style.display = 'none';
                }
            });

            // Hide division sections that have no visible cards
            divisionSections.forEach(section => {
                const visibleCards = section.querySelectorAll('.avatar-card[style*="display: block"], .avatar-card:not([style*="display: none"])');
                if (visibleCards.length === 0) {
                    section.style.display = 'none';
                }
            });
        }

        // Enhanced Modal functionality
        function showPersonModal(name, role, details, initial) {
            const modal = document.getElementById('personModal');
            const modalContent = document.getElementById('modalContent');
            
            // Generate avatar color based on role
            let avatarClass = 'koordinator';
            if (role.includes('Ketua') || role.includes('Wakil')) {
                avatarClass = 'presidium';
            } else if (role.includes('Kepala')) {
                avatarClass = 'kepala';
            } else if (role.includes('Sekretaris') || role.includes('Bendahara')) {
                avatarClass = 'admin';
            }

            modalContent.innerHTML = `
                <div style="text-align: center; margin-bottom: 40px;">
                    <div class="avatar ${avatarClass}" style="width: 120px; height: 120px; font-size: 3rem; margin: 0 auto 25px; box-shadow: 0 15px 35px rgba(0,0,0,0.2);">
                        ${initial}
                    </div>
                    <h2 style="color: var(--primary-color); margin-bottom: 12px; font-size: 1.8rem; font-weight: 800;">${name}</h2>
                    <p style="color: var(--secondary-color); font-weight: 600; font-size: 1.2rem; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">${role}</p>
                    <p style="color: var(--text-secondary); font-size: 1.1rem;">${details}</p>
                </div>
                
                <div style="background: var(--bg-glass); backdrop-filter: blur(20px); padding: 25px; border-radius: 20px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.2);">
                    <h4 style="color: var(--primary-color); margin-bottom: 18px; display: flex; align-items: center; gap: 12px; font-size: 1.2rem;">
                        <i class="fas fa-info-circle" style="color: var(--secondary-color);"></i> Informasi Tambahan
                    </h4>
                    <p style="color: var(--text-primary); line-height: 1.7; margin-bottom: 15px; font-size: 1rem;">
                        Untuk informasi lebih lanjut mengenai program kerja dan kegiatan, silakan hubungi melalui kontak resmi UKM Madani.
                    </p>
                    <div style="background: var(--gradient); padding: 15px; border-radius: 12px; margin-top: 15px;">
                        <p style="color: white; margin: 0; font-size: 0.9rem; text-align: center;">
                            <i class="fas fa-quote-left" style="margin-right: 8px;"></i>
                            "Barang siapa yang menempuh jalan untuk mencari ilmu, maka Allah akan mudahkan baginya jalan menuju surga"
                            <i class="fas fa-quote-right" style="margin-left: 8px;"></i>
                        </p>
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a href="https://wa.me/6287889452909" target="_blank" 
                       style="background: var(--gradient); color: white; padding: 15px 30px; border-radius: 50px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: 600; transition: all 0.3s ease; box-shadow: var(--shadow);">
                        <i class="fab fa-whatsapp"></i> Hubungi via WhatsApp
                    </a>
                    <a href="mailto:madani@lk.itera.ac.id" 
                       style="background: var(--gradient-gold); color: white; padding: 15px 30px; border-radius: 50px; text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: 600; transition: all 0.3s ease; box-shadow: var(--shadow);">
                        <i class="fas fa-envelope"></i> Email Resmi
                    </a>
                </div>
            `;
            
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('personModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Enhanced smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const headerHeight = document.querySelector('.header').offsetHeight;
                    const targetPosition = target.offsetTop - headerHeight - 30;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Enhanced hover effects
        function addEnhancedHoverEffects() {
            // Avatar cards 3D effect
            document.querySelectorAll('.avatar-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-15px) rotateY(5deg) scale(1.02)';
                    this.style.zIndex = '20';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.zIndex = '10';
                });

                // Mouse move parallax effect
                card.addEventListener('mousemove', function(e) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = (y - centerY) / 10;
                    const rotateY = (centerX - x) / 10;
                    
                    this.style.transform = `translateY(-15px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.02)`;
                });
            });

            // Subdepartment cards hover
            document.querySelectorAll('.subdept-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });
        }

        // Intersection Observer for animations
        function initScrollAnimations() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        
                        // Add stagger animation to children
                        const children = entry.target.querySelectorAll('.avatar-card, .subdept-card');
                        children.forEach((child, index) => {
                            setTimeout(() => {
                                child.style.opacity = '1';
                                child.style.transform = 'translateY(0)';
                            }, index * 100);
                        });
                    }
                });
            }, observerOptions);

            // Observe division sections
            document.querySelectorAll('.division-section').forEach(section => {
                observer.observe(section);
            });
        }

        // Keyboard navigation
        function initKeyboardNavigation() {
            document.addEventListener('keydown', function(e) {
                // Escape to close modal
                if (e.key === 'Escape') {
                    closeModal();
                    if (fabOpen) {
                        fabToggle.click();
                    }
                }
                
                // Space or Enter to open modal for focused card
                if ((e.key === ' ' || e.key === 'Enter') && document.activeElement.classList.contains('avatar-card')) {
                    e.preventDefault();
                    document.activeElement.click();
                }
                
                // Arrow keys for navigation
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    const focusableElements = Array.from(document.querySelectorAll('.avatar-card, .search-box'));
                    const currentIndex = focusableElements.indexOf(document.activeElement);
                    
                    if (currentIndex !== -1) {
                        e.preventDefault();
                        let nextIndex;
                        
                        if (e.key === 'ArrowDown') {
                            nextIndex = (currentIndex + 1) % focusableElements.length;
                        } else {
                            nextIndex = (currentIndex - 1 + focusableElements.length) % focusableElements.length;
                        }
                        
                        focusableElements[nextIndex].focus();
                    }
                }
            });
        }

        // Performance optimization
        function initPerformanceOptimizations() {
            // Lazy load images
            const images = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));

            // Throttle scroll events
            let ticking = false;
            function updateOnScroll() {
                updateProgressBar();
                ticking = false;
            }

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(updateOnScroll);
                    ticking = true;
                }
            });
        }

        // Initialize everything
        document.addEventListener('DOMContentLoaded', function() {
            // Basic initializations
            initSearch();
            createParticles();
            addEnhancedHoverEffects();
            initScrollAnimations();
            initKeyboardNavigation();
            initPerformanceOptimizations();

            // Modal event listeners
            document.getElementById('personModal').addEventListener('click', (e) => {
                if (e.target.id === 'personModal') {
                    closeModal();
                }
            });

            // Add loading animation to page
            document.body.style.opacity = '0';
            document.body.style.transition = 'opacity 0.5s ease';
            
            setTimeout(() => {
                document.body.style.opacity = '1';
            }, 100);

            console.log('🎉 Enhanced UKM Madani About page loaded successfully!');
            console.log('✨ Features: Animations, Dark Mode, Enhanced Search, 3D Effects, Keyboard Navigation');
        });

        // Window load optimizations
        window.addEventListener('load', function() {
            // Remove loading states
            document.querySelectorAll('.loading').forEach(el => {
                el.classList.remove('loading');
            });

            // Start particle animation
            setTimeout(createParticles, 500);

            // Performance monitoring
            if ('performance' in window) {
                setTimeout(() => {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    if (perfData && perfData.loadEventEnd - perfData.loadEventStart > 3000) {
                        console.warn('⚠️ Page load time is slow. Consider optimizing assets.');
                    } else {
                        console.log('🚀 Page loaded efficiently!');
                    }
                }, 1000);
            }
        });

        // Error handling
        window.addEventListener('error', function(e) {
            console.error('❌ Error occurred:', e.error);
        });

        // Unhandled promise rejection
        window.addEventListener('unhandledrejection', function(e) {
            console.error('❌ Unhandled promise rejection:', e.reason);
        });
    </script>
</body>
</html>
