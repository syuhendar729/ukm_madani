<?php
// 404.php - Halaman Tidak Ditemukan UKM Madani
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | UKM Madani</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Halaman yang Anda cari tidak ditemukan. Kembali ke halaman utama UKM Madani Institut Teknologi Sumatera.">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Mobile Nav CSS -->
    <link href="assets/css/mobile-nav.css" rel="stylesheet">
    <link href="assets/css/audit-fixes.css" rel="stylesheet">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header - Sama seperti galeri.php */
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

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 20px 60px;
            background: var(--bg-secondary);
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(26, 95, 63, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(212, 175, 55, 0.05) 0%, transparent 50%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.1; }
        }

        .error-container {
            max-width: 800px;
            width: 100%;
            text-align: center;
            background: var(--bg-primary);
            border-radius: 30px;
            padding: 60px 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            position: relative;
            z-index: 1;
        }

        .error-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--gradient);
            border-radius: 30px 30px 0 0;
        }

        .error-number {
            font-size: 8rem;
            font-weight: 900;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        .error-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-family: 'Amiri', serif;
        }

        .error-subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .error-description {
            font-size: 1rem;
            color: var(--text-primary);
            margin-bottom: 40px;
            line-height: 1.8;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .error-icon {
            font-size: 4rem;
            color: var(--secondary-color);
            margin-bottom: 30px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            text-align: center;
            min-width: 160px;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(26, 95, 63, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(26, 95, 63, 0.4);
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: white;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);
        }

        .btn-secondary:hover {
            background: #b8941f;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            box-shadow: none;
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(26, 95, 63, 0.3);
        }

        /* Search Section */
        .search-section {
            margin-top: 50px;
            padding-top: 40px;
            border-top: 1px solid var(--border-color);
        }

        .search-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 15px;
            max-width: 400px;
            margin: 0 auto;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid var(--border-color);
            border-radius: 25px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--bg-secondary);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
            background: white;
        }

        .search-btn {
            padding: 12px 25px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .search-btn:hover {
            background: #2d8659;
            transform: translateY(-2px);
        }

        /* Quick Links */
        .quick-links {
            margin-top: 40px;
        }

        .quick-links-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .quick-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            max-width: 500px;
            margin: 0 auto;
        }

        .quick-link {
            padding: 12px 20px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .quick-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Footer */
        .footer {
            background: var(--primary-color);
            color: white;
            text-align: center;
            padding: 30px 20px;
            margin-top: auto;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-text {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .footer-links {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .footer-link {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .footer-link:hover {
            opacity: 1;
        }

        /* Responsive Design - sama seperti galeri.php */
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

            .main-content {
                padding: 40px 15px;
            }

            .error-container {
                padding: 40px 25px;
                border-radius: 20px;
            }

            .error-number {
                font-size: 5rem;
            }

            .error-title {
                font-size: 1.8rem;
            }

            .error-subtitle {
                font-size: 1rem;
            }

            .error-description {
                font-size: 0.9rem;
            }

            .error-icon {
                font-size: 3rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .btn {
                width: 100%;
                max-width: 280px;
            }

            .search-form {
                flex-direction: column;
                max-width: 100%;
            }

            .search-input,
            .search-btn {
                width: 100%;
            }

            .quick-links-grid {
                grid-template-columns: 1fr;
                max-width: 280px;
            }

            .footer-links {
                flex-direction: column;
                gap: 10px;
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

            .error-container {
                padding: 30px 20px;
                margin: 0 10px;
            }

            .error-number {
                font-size: 4rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            .search-section {
                margin-top: 30px;
                padding-top: 30px;
            }

            .quick-links {
                margin-top: 30px;
            }
        }

        /* Loading Animation */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid var(--border-color);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Overlay untuk Mobile -->
    <div class="nav-overlay" id="navOverlay"></div>

    <!-- Header - Sama seperti galeri.php -->
    <header class="header">
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
                    <li><a href="index.php#about" class="nav-link">Tentang</a></li>
                    <li><a href="berita.php" class="nav-link">Berita</a></li>
                    <li><a href="artikel.php" class="nav-link">Artikel</a></li>
                    <li><a href="galeri.php" class="nav-link">Galeri</a></li>
                    <li><a href="index.php#donation" class="nav-link">Infaq</a></li>
                    <li><a href="index.php" class="nav-link back-link">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a></li>
                </ul>

                <!-- Mobile Navigation -->
                <ul class="nav-links mobile" id="mobileNav">
                    <li><a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i> Beranda
                    </a></li>
                    <li><a href="index.php#about" class="nav-link">
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
                    <li><a href="index.php" class="nav-link back-link">
                        <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                    </a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-search"></i>
            </div>
            
            <div class="error-number">404</div>
            
            <h1 class="error-title">Halaman Tidak Ditemukan</h1>
            <p class="error-subtitle">Maaf, halaman yang Anda cari tidak dapat ditemukan</p>
            
            <p class="error-description">
                Halaman yang Anda tuju mungkin telah dipindahkan, dihapus, atau alamat URL yang Anda masukkan salah. 
                Jangan khawatir, mari kita bantu Anda menemukan jalan kembali ke konten UKM Madani.
            </p>

            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Kembali ke Beranda
                </a>
                <a href="galeri.php" class="btn btn-secondary">
                    <i class="fas fa-images"></i>
                    Lihat Galeri
                </a>
                <a href="javascript:history.back()" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Halaman Sebelumnya
                </a>
            </div>

            <div class="search-section">
                <h3 class="search-title">Cari Konten Lainnya</h3>
                <form class="search-form" action="galeri.php" method="GET">
                    <input type="text" name="search" class="search-input" placeholder="Cari kegiatan, artikel, atau informasi..." required>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- <div class="quick-links">
                <h3 class="quick-links-title">Halaman Populer</h3>
                <div class="quick-links-grid">
                    <a href="index.php#about" class="quick-link">
                        <i class="fas fa-info-circle"></i>
                        Tentang Kami
                    </a>
                    <a href="berita.php" class="quick-link">
                        <i class="fas fa-newspaper"></i>
                        Berita
                    </a>
                    <a href="artikel.php" class="quick-link">
                        <i class="fas fa-pen-fancy"></i>
                        Artikel
                    </a>
                    <a href="index.php#contact" class="quick-link">
                        <i class="fas fa-envelope"></i>
                        Kontak
                    </a>
                </div>
            </div> -->
        </div>
    </main>

    <!-- Mobile Navigation JavaScript -->
    <script src="assets/js/mobile-nav.js"></script>
    <script src="assets/js/audit-fixes.js"></script>

    <script>
        // Search form enhancement
        const searchForm = document.querySelector('.search-form');
        const searchBtn = searchForm?.querySelector('.search-btn');

        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                const input = this.querySelector('.search-input');
                
                if (!input.value.trim()) {
                    e.preventDefault();
                    input.focus();
                    return;
                }

                // Show loading state
                const originalText = searchBtn.innerHTML;
                searchBtn.innerHTML = '<div class="spinner"></div>';
                searchBtn.disabled = true;

                // Reset button after timeout (in case form doesn't redirect)
                setTimeout(() => {
                    searchBtn.innerHTML = originalText;
                    searchBtn.disabled = false;
                }, 5000);
            });
        }

        // Keyboard Navigation
        document.addEventListener('keydown', function(e) {
            // ESC key to go back
            if (e.key === 'Escape') {
                history.back();
            }
            
            // Enter key on logo to go home
            if (e.key === 'Enter' && e.target.classList.contains('nav-brand')) {
                window.location.href = 'index.php';
            }
        });

        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Accessibility: Focus management
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on main content for screen readers
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.setAttribute('tabindex', '-1');
                mainContent.focus();
            }

            // Add ARIA labels
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.setAttribute('aria-label', 'Cari konten di website UKM Madani');
            }

            const quickLinks = document.querySelectorAll('.quick-link');
            quickLinks.forEach(link => {
                const icon = link.querySelector('i');
                if (icon) {
                    icon.setAttribute('aria-hidden', 'true');
                }
            });
        });

        // Auto-redirect timer (optional - uncomment if needed)
        /*
        let redirectTimer = 30; // seconds
        const timerElement = document.createElement('div');
        timerElement.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 0.9rem;
            z-index: 1000;
        `;
        document.body.appendChild(timerElement);

        const countdown = setInterval(() => {
            timerElement.textContent = `Auto redirect dalam ${redirectTimer}s`;
            redirectTimer--;

            if (redirectTimer < 0) {
                clearInterval(countdown);
                window.location.href = 'index.php';
            }
        }, 1000);

        // Cancel auto-redirect on user interaction
        document.addEventListener('click', () => {
            clearInterval(countdown);
            timerElement.remove();
        });
        */

        console.log('🚫 404 page loaded successfully!');
        console.log('💡 Tips: Gunakan ESC untuk kembali, atau klik tombol navigasi');
    </script>
</body>
</html>
