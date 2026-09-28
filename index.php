<?php
try {
    require_once 'config/database.php';
} catch (Exception $e) {
    echo "
    <div style='font-family: Arial; padding: 20px; background: #f8f9fa; margin: 20px;'>
        <h2 style='color: #dc3545;'>❌ Error Database</h2>
        <p>Gagal koneksi ke database: " . $e->getMessage() . "</p>
        <p><a href='setup-database.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>⚙️ Setup Database</a></p>
    </div>
    ";
    exit;
}

// Function untuk format tanggal Indonesia
function formatTanggalIndonesia($date) {
    if (empty($date) || $date === '0000-00-00') {
        return 'Tanggal tidak tersedia';
    }
    
    $bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return 'Format tanggal tidak valid';
    }
    
    $hari = date('d', $timestamp);
    $bulan_num = (int)date('m', $timestamp);
    $tahun = date('Y', $timestamp);
    
    return $hari . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
}

function getImagePath($filename, $type = 'berita', $title = '') {
    if (empty($filename)) {
        return generatePlaceholder($type, $title);
    }
    
    $path = "assets/uploads/{$type}/" . $filename;
    if (file_exists($path)) {
        return $path;
    }
    
    return generatePlaceholder($type, $title);
}

function generatePlaceholder($type = 'berita', $title = '') {
    $colors = [
        'berita' => ['bg' => '#1a5f3f', 'text' => '#ffffff'],
        'artikel' => ['bg' => '#d4af37', 'text' => '#ffffff'],
        'galeri' => ['bg' => '#2d8659', 'text' => '#ffffff']
    ];
    
    $color = $colors[$type] ?? $colors['berita'];
    $initial = strtoupper(substr(trim($title), 0, 1)) ?: ($type === 'artikel' ? '📄' : ($type === 'galeri' ? '📸' : '📰'));
    
    return "data:image/svg+xml;base64," . base64_encode('
    <svg width="400" height="250" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:' . $color['bg'] . ';stop-opacity:1" />
                <stop offset="100%" style="stop-color:' . $color['bg'] . '80;stop-opacity:1" />
            </linearGradient>
        </defs>
        <rect width="400" height="250" fill="url(#grad1)"/>
        <text x="200" y="125" text-anchor="middle" dominant-baseline="middle" 
              fill="' . $color['text'] . '" font-family="Arial" font-size="60" font-weight="bold">
              ' . htmlspecialchars($initial) . '
        </text>
        <text x="200" y="170" text-anchor="middle" fill="' . $color['text'] . '" 
              font-family="Arial" font-size="14" opacity="0.8">
              ' . strtoupper($type) . '
        </text>
    </svg>');
}

// Ambil data dengan error handling yang lebih baik
$berita_terbaru = [];
$artikel_terbaru = [];
$galeri_terbaru = [];

try {
    // Query berita dengan handling tanggal yang lebih fleksibel
    $berita_query = "SELECT * FROM berita 
                     WHERE status = 'published' 
                     ORDER BY 
                         CASE 
                             WHEN tanggal_publish IS NOT NULL AND tanggal_publish != '0000-00-00' 
                             THEN tanggal_publish 
                             ELSE created_at 
                         END DESC 
                     LIMIT 3";
    
    $result = $conn->query($berita_query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $berita_terbaru[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching berita: " . $e->getMessage());
}

try {
    // Query artikel dengan handling tanggal yang lebih fleksibel
    $artikel_query = "SELECT * FROM artikel 
                      WHERE status = 'published' 
                      ORDER BY 
                          CASE 
                              WHEN tanggal_publish IS NOT NULL AND tanggal_publish != '0000-00-00' 
                              THEN tanggal_publish 
                              ELSE created_at 
                          END DESC 
                      LIMIT 3";
    
    $result = $conn->query($artikel_query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $artikel_terbaru[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching artikel: " . $e->getMessage());
}

try {
    $galeri_query = "SELECT * FROM galeri 
                     WHERE status = 'published' 
                     ORDER BY 
                         CASE 
                             WHEN tanggal_kegiatan IS NOT NULL AND tanggal_kegiatan != '0000-00-00' 
                             THEN tanggal_kegiatan 
                             ELSE created_at 
                         END DESC 
                     LIMIT 3";
    
    $result = $conn->query($galeri_query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $galeri_terbaru[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching galeri: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UKM Madani - Mahasiswa Peradaban Islam</title>
    <link rel="icon" type="image/x-icon" href="assets/images/logo-madani.png">
    <link rel="shortcut icon" href="assets/images/logo-madani.png">
    <link rel="apple-touch-icon" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/logo-madani.png">
    
    <!-- Meta tags untuk SEO -->
    <meta name="description" content="UKM Madani ITERA - Lembaga Dakwah Kampus yang membangun generasi muslim berakhlak mulia, berilmu, dan berperadaban">
    <meta name="keywords" content="UKM Madani, ITERA, Lembaga Dakwah Kampus, Islam, Mahasiswa">
    <meta name="author" content="UKM Madani ITERA">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="UKM Madani - Mahasiswa Peradaban Islam">
    <meta property="og:description" content="Membangun generasi muslim yang berakhlak mulia, berilmu, dan berperadaban">
    <meta property="og:image" content="assets/images/logo-madani.png">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:type" content="website">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        
        /* Reset & Variables */
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

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-primary);
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* IMPROVED RESPONSIVE DESIGN */
        @media (max-width: 1200px) {
            .container {
                max-width: 95%;
                padding: 0 15px;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
        }

        /* Scroll Progress Bar */
        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary-color), #e6c659);
            z-index: 1001;
            transition: width 0.1s ease-out;
        }

        /* IMPROVED HEADER - FULLY RESPONSIVE */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            z-index: 1000;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .header.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-bottom: 2px solid var(--primary-color);
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
            font-size: clamp(1.2rem, 4vw, 1.8rem);
            font-weight: 700;
            transition: transform 0.3s ease;
            z-index: 1001;
        }

        .nav-brand:hover {
            transform: scale(1.05);
        }

        .nav-brand img {
            width: clamp(30px, 6vw, 40px);
            height: clamp(30px, 6vw, 40px);
            object-fit: contain;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
            align-items: center;
            transition: all 0.3s ease;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 25px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            position: relative;
            white-space: nowrap;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--secondary-color);
            transform: translateX(-50%);
            transition: width 0.3s ease;
            border-radius: 2px;
        }

        .nav-link:hover::after,
        .nav-link.active::after {
            width: 80%;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
            background: rgba(26, 95, 63, 0.05);
            transform: translateY(-2px);
        }

        .nav-admin {
            background: var(--secondary-color);
            color: white !important;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
        }

        .nav-admin:hover {
            background: #b8941f;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        }

        .nav-admin::after {
            display: none;
        }

        .mobile-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 4px;
            padding: 10px;
            border-radius: 8px;
            background: var(--primary-color);
            border: none;
            z-index: 1001;
            transition: all 0.3s ease;
        }

        .mobile-toggle span {
            width: 25px;
            height: 3px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .mobile-toggle.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .mobile-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-toggle.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }

        /* MOBILE NAVIGATION - IMPROVED */
        @media (max-width: 1024px) {
            .mobile-toggle {
                display: flex;
            }
            
            .nav-menu {
                position: fixed;
                top: 0;
                left: -100%;
                width: 80%;
                max-width: 350px;
                height: 100vh;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                flex-direction: column;
                justify-content: center;
                gap: 30px;
                box-shadow: 0 0 50px rgba(0, 0, 0, 0.2);
                transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                z-index: 999;
            }
            
            .nav-menu.active {
                left: 0;
            }
            
            .nav-link {
                font-size: 1.1rem;
                padding: 15px 25px;
                width: 100%;
                text-align: center;
                border-radius: 15px;
                margin: 0 20px;
            }
            
            .nav-admin {
                margin: 20px;
                padding: 15px 25px;
                font-size: 1.1rem;
            }
        }

        /* HERO SECTION - FULLY RESPONSIVE */
        .hero {
            min-height: 100vh;
                background: 
                    linear-gradient(135deg, rgba(26, 95, 63, 0.9) 0%, rgba(45, 134, 89, 0.8) 100%),
                    url('assets/images/anggota-ukm.jpg') center/cover;
            display: flex;
            align-items: center;
            color: white;
            position: relative;
            overflow: hidden;
            padding: 100px 0 50px;
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

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .arabic {
            font-family: 'Amiri', serif;
            font-size: clamp(1.2rem, 4vw, 2rem);
            font-style: italic;
            margin-bottom: 20px;
            color: var(--secondary-color);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            animation: fadeInDown 1s ease-out;
        }

        .hero-title {
            font-size: clamp(2.5rem, 8vw, 6rem);
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            line-height: 1.1;
            animation: fadeInUp 1s ease-out 0.2s both;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 4vw, 2rem);
            font-weight: 600;
            margin-bottom: 20px;
            color: #e6c659;
            animation: fadeInUp 1s ease-out 0.4s both;
        }

        .hero-description {
            font-size: clamp(0.9rem, 3vw, 1.3rem);
            margin-bottom: 40px;
            opacity: 0.95;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeInUp 1s ease-out 0.6s both;
            line-height: 1.8;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease-out 0.8s both;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: clamp(12px, 3vw, 16px) clamp(20px, 5vw, 32px);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            border: 2px solid;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            text-align: center;
            white-space: nowrap;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
        }

        .btn-primary:hover {
            background: #e6c659;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.6);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border-color: white;
        }

        .btn-outline:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: clamp(20px, 8vw, 60px);
            margin-top: 40px;
            animation: fadeInUp 1s ease-out 1s both;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
            transition: transform 0.3s ease;
            min-width: 120px;
        }

        .stat-item:hover {
            transform: translateY(-5px);
        }

        /* SECTION STYLES - IMPROVED RESPONSIVE */
        .section {
            padding: clamp(40px, 10vw, 80px) 0;
        }

        .bg-light {
            background: var(--bg-secondary);
        }

        .section-header {
            text-align: center;
            margin-bottom: clamp(40px, 8vw, 60px);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .section-title {
            font-size: clamp(2rem, 6vw, 3.5rem);
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, var(--secondary-color), #e6c659);
            border-radius: 2px;
        }

        .section-subtitle {
            font-size: clamp(1rem, 3vw, 1.2rem);
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* ABOUT SECTION - FULLY RESPONSIVE */
        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: clamp(30px, 8vw, 60px);
            align-items: center;
            overflow-x: clip;
        }

        .about-text h3 {
            color: var(--primary-color);
            font-size: clamp(1.3rem, 4vw, 1.8rem);
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .about-text h3 i {
            color: var(--secondary-color);
            font-size: clamp(1.2rem, 3vw, 1.5rem);
        }

        .about-text p {
            margin-bottom: 20px;
            color: var(--text-secondary);
            font-size: clamp(1rem, 2.5vw, 1.1rem);
            line-height: 1.8;
        }

        .about-text ul {
            list-style: none;
            margin-bottom: 20px;
        }

        .about-text li {
            padding: 8px 0;
            color: var(--text-secondary);
            position: relative;
            padding-left: 30px;
            font-size: clamp(0.95rem, 2.5vw, 1.05rem);
            transition: all 0.3s ease;
        }

        .about-text li:hover {
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .about-text li::before {
            content: '✦';
            position: absolute;
            left: 0;
            color: var(--secondary-color);
            font-weight: bold;
            font-size: 1.2rem;
        }

        .about-visual {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .about-image {
            width: clamp(200px, 40vw, 300px);
            height: clamp(200px, 40vw, 300px);
            background: var(--gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(40px, 8vw, 60px);
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .about-image:hover {
            transform: scale(1.05);
        }

        .about-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            animation: pulse 4s ease-in-out infinite;
        }

        /* ABOUT CTA BUTTON */
        .about-cta {
            margin-top: 30px;
            grid-column: 1 / -1;
            text-align: center;
        }

        .btn-about {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(26, 95, 63, 0.3);
            border: 2px solid var(--primary-color);
        }

        .btn-about:hover {
            background: #2d8659;
            border-color: #2d8659;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 95, 63, 0.4);
        }

        .btn-about i {
            transition: transform 0.3s ease;
        }

        .btn-about:hover i {
            transform: translateX(5px);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* CARDS - IMPROVED RESPONSIVE */
        .grid {
            display: grid;
            gap: clamp(20px, 5vw, 30px);
        }

        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(min(350px, 100%), 1fr));
        }

        .grid-gallery {
            grid-template-columns: repeat(auto-fit, minmax(min(250px, 100%), 1fr));
        }

        .card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.4s ease;
            border: 1px solid var(--border-color);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .card-image {
            height: clamp(150px, 30vw, 200px);
            position: relative;
            overflow: hidden;
            border-radius: 15px 15px 0 0;
            background: var(--bg-secondary);
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .card:hover .card-image img {
            transform: scale(1.05);
        }

        .card-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-size: clamp(2rem, 5vw, 3rem);
            opacity: 0.8;
        }

        .card-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--primary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: clamp(0.7rem, 2vw, 0.75rem);
            font-weight: 600;
            text-transform: uppercase;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 2;
        }

        .card-badge.artikel {
            background: var(--secondary-color);
        }

        .card-content {
            padding: clamp(20px, 5vw, 25px);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            font-size: clamp(0.75rem, 2vw, 0.85rem);
            color: var(--text-secondary);
            flex-wrap: wrap;
        }

        .card-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
            background: var(--bg-secondary);
            padding: 4px 8px;
            border-radius: 12px;
            white-space: nowrap;
        }

        .card-title {
            margin-bottom: 15px;
            flex-grow: 0;
        }

        .card-title a {
            text-decoration: none;
            color: var(--text-primary);
            font-size: clamp(1.1rem, 3vw, 1.25rem);
            font-weight: 700;
            line-height: 1.4;
            transition: color 0.3s ease;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-title a:hover {
            color: var(--primary-color);
        }

        .card-excerpt {
            color: var(--text-secondary);
            margin-bottom: 20px;
            line-height: 1.6;
            flex-grow: 1;
            font-size: clamp(0.9rem, 2.5vw, 0.95rem);
        }

        .read-more {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-top: auto;
            font-size: clamp(0.85rem, 2vw, 0.9rem);
        }

        .read-more:hover {
            color: var(--secondary-color);
            transform: translateX(5px);
        }

        .read-more i {
            transition: transform 0.3s ease;
        }

        .read-more:hover i {
            transform: translateX(3px);
        }

        .section-footer {
            text-align: center;
            margin-top: clamp(40px, 8vw, 60px);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: clamp(40px, 8vw, 60px);
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: clamp(3rem, 8vw, 4rem);
            margin-bottom: 20px;
            opacity: 0.5;
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

        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: clamp(1.2rem, 4vw, 1.5rem);
        }

        .empty-state p {
            font-size: clamp(0.9rem, 2.5vw, 1rem);
        }

        /* GALLERY SECTION - DESKTOP HORIZONTAL, MOBILE VERTICAL */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(350px, 100%), 1fr));
            gap: clamp(20px, 5vw, 30px);
        }

        .gallery-item {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
            border: 1px solid var(--border-color);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .gallery-item:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .gallery-image {
            width: 100%;
            height: clamp(200px, 30vw, 250px);
            position: relative;
            overflow: hidden;
            border-radius: 15px 15px 0 0;
            background: var(--bg-secondary);
        }

        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-item:hover .gallery-image img {
            transform: scale(1.05);
        }

        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(26, 95, 63, 0.8), rgba(212, 175, 55, 0.8));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-overlay i {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 10px;
            animation: pulse 2s ease-in-out infinite;
        }

        .gallery-overlay-text {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .gallery-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--secondary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: clamp(0.7rem, 2vw, 0.75rem);
            font-weight: 600;
            text-transform: uppercase;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 2;
        }

        .gallery-photo-count {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: clamp(0.7rem, 2vw, 0.8rem);
            font-weight: 600;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 5px;
            backdrop-filter: blur(10px);
        }

        .gallery-content {
            padding: clamp(20px, 5vw, 25px);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .gallery-title {
            margin-bottom: 15px;
            flex-grow: 0;
        }

        .gallery-title h3 {
            color: var(--text-primary);
            font-size: clamp(1.1rem, 3vw, 1.25rem);
            font-weight: 700;
            line-height: 1.4;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .gallery-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            font-size: clamp(0.75rem, 2vw, 0.85rem);
            color: var(--text-secondary);
            flex-wrap: wrap;
        }

        .gallery-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
            background: var(--bg-secondary);
            padding: 4px 8px;
            border-radius: 12px;
            white-space: nowrap;
        }

        .gallery-description {
            color: var(--text-secondary);
            margin-bottom: 20px;
            line-height: 1.6;
            flex-grow: 1;
            font-size: clamp(0.9rem, 2.5vw, 0.95rem);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .gallery-button {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            margin-top: auto;
            font-size: clamp(0.85rem, 2vw, 0.9rem);
            padding: 10px 20px;
            border: 2px solid var(--primary-color);
            border-radius: 25px;
            background: transparent;
            text-align: center;
            justify-content: center;
        }

        .gallery-button:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(26, 95, 63, 0.3);
        }

        .gallery-button i {
            transition: transform 0.3s ease;
        }

        .gallery-button:hover i {
            transform: translateX(3px);
        }

        /* DONATION SECTION */
        .donation-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: clamp(30px, 8vw, 60px);
            align-items: stretch;
            overflow-x: clip;
        }

        .donation-form,
        .donation-info {
            background: white;
            border-radius: 20px;
            padding: clamp(25px, 6vw, 40px);
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .form-header,
        .info-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--bg-secondary);
        }

        .form-header h3,
        .info-header h3 {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .form-header p,
        .info-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* QR Code Section */
        .qris-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: 15px;
        }

        .qris-section h4 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 600;
        }

        .qris-container {
            margin: 20px 0;
        }

        .qris-placeholder {
            width: 150px;
            height: 150px;
            background: white;
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            transition: all 0.3s ease;
        }

        .qris-placeholder:hover {
            border-color: var(--primary-color);
            transform: scale(1.05);
        }

        .qris-placeholder i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .qris-placeholder p {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: center;
            margin: 0;
        }

        .qris-text {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* Bank Accounts */
        .bank-accounts {
            margin-bottom: 30px;
        }

        .bank-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            border: 1px solid var(--border-color);
            border-radius: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .bank-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(26, 95, 63, 0.1);
            transform: translateY(-2px);
        }

        .bank-logo {
            width: 50px;
            height: 50px;
            background: var(--bg-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .bank-logo i {
            font-size: 1.5rem;
        }

        .bank-details {
            flex: 1;
        }

        .bank-details h4 {
            color: var(--primary-color);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .account-number {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 3px;
            font-family: 'Courier New', monospace;
        }

        .account-name {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 0;
        }

        .copy-btn {
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .copy-btn:hover {
            background: #b8941f;
            transform: scale(1.05);
        }

        /* Donation Notes */
        .donation-notes {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 15px;
            border-left: 4px solid var(--primary-color);
        }

        .donation-notes h4 {
            color: var(--primary-color);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .donation-notes ul {
            list-style: none;
            margin: 0;
        }

        .donation-notes li {
            padding: 8px 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
            position: relative;
            padding-left: 20px;
        }

        .donation-notes li::before {
            content: '•';
            color: var(--secondary-color);
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        /* Form Styling */
        #donationForm .form-group {
            margin-bottom: 20px;
        }

        #donationForm label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.95rem;
        }

        /* #donationForm input,
        #donationForm select,
        #donationForm textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        } */

        #donationForm input:focus,
        #donationForm select:focus,
        #donationForm textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
        }

        #donationForm textarea {
            resize: vertical;
            min-height: 80px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .donation-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .donation-form {
                order: 2;
            }
            
            .donation-info {
                order: 1;
            }
        }

        @media (max-width: 768px) {
            .donation-form,
            .donation-info {
                padding: 20px;
            }
            
            .bank-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .qris-placeholder {
                width: 120px;
                height: 120px;
            }
        }

        /* CONTACT SECTION - IMPROVED RESPONSIVE */
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: clamp(30px, 8vw, 60px);
            align-items: flex-start;
        }

        .contact-item {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding: clamp(20px, 5vw, 30px);
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .contact-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--secondary-color), #e6c659);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .contact-details h4 {
            color: var(--primary-color);
            margin-bottom: 8px;
            font-size: clamp(1rem, 3vw, 1.1rem);
        }

        .contact-details p {
            color: var(--text-secondary);
            line-height: 1.6;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
        }

        .contact-form {
            background: white;
            padding: clamp(25px, 6vw, 40px);
            border-radius: 20px;
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 500;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* FOOTER - IMPROVED RESPONSIVE & CENTERED */
        .footer {
            background: var(--text-primary);
            color: white;
            padding: clamp(50px, 8vw, 80px) 0 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: clamp(30px, 6vw, 50px);
            margin-bottom: 50px;
            align-items: start;
        }

        .footer-section {
            width: 100%;
        }

        .footer-section:first-child {
            text-align: center;
        }

        .footer-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .footer-logo img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .footer-section h3 {
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-weight: 700;
            font-size: clamp(1.3rem, 3vw, 1.6rem);
            text-align: center;
        }

        .footer-section h4 {
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-weight: 700;
            font-size: clamp(1.1rem, 3vw, 1.3rem);
            text-align: left;
        }

        .footer-section p {
            line-height: 1.8;
            opacity: 0.9;
            margin-bottom: 25px;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
        }

        .footer-section ul {
            list-style: none;
            text-align: left;
        }

        .footer-section ul li {
            padding: 8px 0;
            text-align: left;
        }

        .footer-section ul li a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            padding: 5px 0;
        }

        .footer-section ul li a:hover {
            opacity: 1;
            color: var(--secondary-color);
            transform: translateX(5px);
        }

        .footer-section ul li a i {
            width: 16px;
            text-align: center;
            color: var(--secondary-color);
        }

        .social-links {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            justify-content: center;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.3rem;
        }

        .social-links a:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .footer-bottom {
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            opacity: 0.8;
        }

        .footer-bottom-content {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            gap: 15px;
        }

        .footer-bottom-content p {
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            text-align: center;
            margin-bottom: 10px;
        }

        .footer-links {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            opacity: 0.8;
            transition: all 0.3s ease;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            padding: 5px 10px;
            border-radius: 5px;
        }

        .footer-links a:hover {
            opacity: 1;
            color: var(--secondary-color);
            background: rgba(212, 175, 55, 0.1);
        }

        /* Contact Info Styling */
        .contact-info-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }

        .contact-info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            transition: all 0.3s ease;
            width: 100%;
        }

        .contact-info-item:hover {
            transform: translateX(5px);
        }

        .contact-info-item i {
            color: var(--secondary-color);
            width: 18px;
            text-align: center;
            flex-shrink: 0;
            font-size: 1rem;
        }

        .contact-info-item span {
            color: white;
            opacity: 0.9;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            line-height: 1.5;
        }

        .contact-info-item:hover span {
            opacity: 1;
            color: var(--secondary-color);
        }

        /* SCROLL TO TOP */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
            z-index: 999;
        }

        .scroll-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top:hover {
            background: #b8941f;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.6);
        }

        /* ANIMATION CLASSES */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease-out;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .slide-in-left {
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.8s ease-out;
        }

        .slide-in-left.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .slide-in-right {
            opacity: 0;
            transform: translateX(50px);
            transition: all 0.8s ease-out;
        }

        .slide-in-right.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .scale-in {
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.8s ease-out;
        }

        .scale-in.visible {
            opacity: 1;
            transform: scale(1);
        }

        /* KEYFRAMES */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* RESPONSIVE BREAKPOINTS */
        @media (max-width: 1024px) {
            .about-content,
            .contact-content {
                grid-template-columns: 1fr;
                gap: clamp(30px, 6vw, 40px);
            }
            
            .about-visual {
                order: -1;
            }
            
            .hero-stats {
                flex-direction: column;
                gap: 30px;
            }
            
            .nav-menu {
                gap: 20px;
            }
            
            .footer-content {
                grid-template-columns: 1fr 1fr;
                gap: clamp(25px, 6vw, 40px);
            }
            
            .footer-section:first-child {
                grid-column: 1 / -1;
                text-align: center;
                max-width: 500px;
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            .hero-buttons {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .btn {
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }
            
            .hero-stats {
                flex-direction: column;
                gap: 30px;
            }
            
            .grid-3 {
                grid-template-columns: 1fr;
            }
            
            .gallery-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
                text-align: center;
            }
            
            .footer-section {
                max-width: 100%;
            }
            
            .footer-section:first-child {
                grid-column: 1;
            }
            
            .footer-section h4 {
                text-align: center;
            }
            
            .footer-section ul {
                text-align: center;
            }
            
            .footer-section ul li a {
                justify-content: center;
            }
            
            /* Mobile Contact Info Centered */
            .contact-info-list {
                align-items: center;
                text-align: center;
            }
            
            .contact-info-item {
                justify-content: center;
                max-width: 300px;
            }
            
            .contact-info-item:hover {
                transform: translateY(-2px);
            }
            
            .contact-item {
                flex-direction: column;
                text-align: center;
            }
            
            .contact-icon {
                align-self: center;
            }
        }

        @media (max-width: 480px) {
            .section {
                padding: clamp(30px, 8vw, 40px) 0;
            }
            
            .about-image {
                padding: clamp(30px, 6vw, 40px);
            }
            
            .card-content {
                padding: clamp(15px, 4vw, 20px);
            }
            
            .contact-form {
                padding: clamp(20px, 5vw, 25px);
            }
            
            .scroll-top {
                width: 45px;
                height: 45px;
                bottom: 20px;
                right: 20px;
            }
            
            .hero {
                padding: 80px 0 30px;
            }
        }

        /* MOBILE LANDSCAPE */
        @media (max-width: 768px) and (orientation: landscape) {
            .hero {
                min-height: 120vh;
            }
            
            .hero-content {
                padding: 20px;
            }
        }

        /* PRINT STYLES */
        @media print {
            .header,
            .scroll-top,
            .mobile-toggle,
            .hero-buttons,
            .btn {
                display: none !important;
            }
            
            .hero {
                min-height: auto;
                padding: 40px 0;
            }
            
            body {
                font-size: 12pt;
                line-height: 1.4;
            }
        }
    </style>
</head>
<body>
    <!-- Scroll Progress Bar -->
    <div class="scroll-progress"></div>

    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <a href="#home" class="nav-brand">
                    <?php if (file_exists('assets/images/logo-madani.png')): ?>
                        <img src="assets/images/logo-madani.png" alt="UKM Madani Logo">
                    <?php else: ?>
                        <div style="width: 40px; height: 40px; background: var(--secondary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">M</div>
                    <?php endif; ?>
                    UKM MADANI
                </a>
                
                <ul class="nav-menu" id="nav-menu">
                    <li><a href="#home" class="nav-link active">Beranda</a></li>
                    <li><a href="#about" class="nav-link">Tentang</a></li>
                    <li><a href="#news" class="nav-link">Berita</a></li>
                    <li><a href="#articles" class="nav-link">Artikel</a></li>
                    <li><a href="#gallery" class="nav-link">Galeri</a></li>
                    <li><a href="#donation" class="nav-link">Infaq</a></li>
                </ul>
                
                <button class="mobile-toggle" id="mobile-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    </header>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scroll-top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="arabic">بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيم</div>
                <h1 class="hero-title">MADANI ITERA</h1>
                <p class="hero-subtitle">Mahasiswa Peradaban Islam</p>
                <p class="hero-description">
                    Membangun generasi muslim yang berakhlak mulia, berilmu, dan berperadaban melalui pendidikan, dakwah, dan pemberdayaan masyarakat
                </p>
                <div class="hero-buttons">
                    <a href=tentang.php class="btn btn-primary">
                        <i class="fas fa-info-circle"></i>
                        Tentang Kami
                    </a>
                    <a href="#footer" class="btn btn-outline">
                        <i class="fas fa-envelope"></i>
                        Hubungi Kami
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section bg-light">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Tentang UKM Madani</h2>
                <p class="section-subtitle">
                    <!-- Lembaga Dakwah Kampus (LDK) Madani ITERA -->
                </p>
            </div>
            
            <div class="about-content">
                <div class="about-text slide-in-left">
                    <div class="about-item">
                        <h3><i class="fas fa-eye"></i> Visi</h3>
                        <p>Menjadikan LDK Madani itera sebagai rumah dan sarana dakwah berkelanjutan yang aktif, inovatif, dan inklusif untuk mewujudkan kader yang berkualitas dan berintegritas berlandaskan Al-Quran dan Sunnah.</p>
                    </div>
                    
                    <div class="about-item">
                        <h3><i class="fas fa-bullseye"></i> Misi</h3>
                        <ul>
                            <li>Menjadikan Alquran dan Sunnah sebagai landasan utama LDK Madani ITERA dalam kehidupan sehari-hari.</li>
                            <li>Menguatkan ukhuwah islamiyah sehingga tercipta rasa komitmen dalam setiap kader untuk menjalankan perannya</li>
                            <li>Meningkatkan sistem pembinaan yang berkualitas dan terstruktur. </li>
                            <li>Mengembangkan metode dakwah melalui proses penuntutan ilmu, pengamalan konsisten dan penyampaian ilmu yang tepat sasaran.</li>
                            <li>Memperluas jaringan kolaborasi dakwah baik internal maupun eksternal.</li>
                            <li>Memberikan kontribusi terhadap isu permasalahan Islam baik dari cakupan lokal maupun global.</li>
                        </ul>
                    </div>
                </div>
                
                <div class="about-visual slide-in-right">
                    <div class="about-image scale-in">
                        <?php if (file_exists('assets/images/logo-madani.png')): ?>
                            <img src="assets/images/logo-madani.png" alt="UKM Madani Logo">
                        <?php else: ?>
                            <div style="font-size: 4rem; color: white;">🕌</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="about-cta">
                    <a href="tentang.php" class="btn-about">
                        <i class="fas fa-users"></i>
                        Kenalan Lebih Dalam
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- News Section -->
    <section id="news" class="section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Berita Terbaru</h2>
                <p class="section-subtitle">
                    Informasi terkini seputar kegiatan dan perkembangan UKM Madani
                </p>
            </div>
            
            <div class="grid grid-3">
                <?php if (count($berita_terbaru) > 0): ?>
                    <?php foreach ($berita_terbaru as $index => $berita): ?>
                    <article class="card fade-in" style="animation-delay: <?= $index * 0.2 ?>s;">
                        <div class="card-image">
                            <?php 
                            $imagePath = getImagePath($berita['gambar'] ?? '', 'berita', $berita['judul']);
                            ?>
                            <?php if (filter_var($imagePath, FILTER_VALIDATE_URL) || (is_string($imagePath) && !str_starts_with($imagePath, 'data:'))): ?>
                                <img src="<?= $imagePath ?>" 
                                     alt="<?= htmlspecialchars($berita['gambar_alt'] ?? $berita['judul']) ?>"
                                     loading="lazy"
                                     onerror="this.parentElement.innerHTML='<div class=\'card-image-placeholder\'><i class=\'fas fa-newspaper\'></i></div>'">
                            <?php else: ?>
                                <img src="<?= $imagePath ?>" alt="Berita <?= htmlspecialchars($berita['judul']) ?>" loading="lazy">
                            <?php endif; ?>
                            <div class="card-badge">Berita</div>
                        </div>
                        <div class="card-content">
                            <div class="card-meta">
                                <span>
                                    <i class="fas fa-calendar"></i> 
                                    <?= formatTanggalIndonesia($berita['tanggal_publish'] ?? $berita['created_at']) ?>
                                </span>
                                <span>
                                    <i class="fas fa-user"></i> 
                                    <?= htmlspecialchars($berita['penulis']) ?>
                                </span>
                                <?php if (!empty($berita['views'])): ?>
                                <span>
                                    <i class="fas fa-eye"></i> 
                                    <?= number_format($berita['views']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="card-title">
                                <a href="berita-detail.php?slug=<?= $berita['slug'] ?>">
                                    <?= htmlspecialchars($berita['judul']) ?>
                                </a>
                            </h3>
                            <p class="card-excerpt">
                                <?php 
                                $excerpt = !empty($berita['excerpt']) ? $berita['excerpt'] : substr(strip_tags($berita['konten']), 0, 150) . '...';
                                echo htmlspecialchars($excerpt);
                                ?>
                            </p>
                            <a href="berita-detail.php?slug=<?= $berita['slug'] ?>" class="read-more">
                                Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state fade-in">
                        <div class="empty-icon">📰</div>
                        <h3>Belum Ada Berita</h3>
                        <p>Berita terbaru akan segera hadir. Pantau terus website kami!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section-footer fade-in">
                <a href="berita.php" class="btn btn-primary">
                    <i class="fas fa-newspaper"></i>
                    Lihat Semua Berita
                </a>
            </div>
        </div>
    </section>

    <!-- Articles Section -->
    <section id="articles" class="section bg-light">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Artikel Pilihan</h2>
                <p class="section-subtitle">
                    Tulisan-tulisan inspiratif seputar Islam, pendidikan, dan kehidupan
                </p>
            </div>
            
            <div class="grid grid-3">
                <?php if (count($artikel_terbaru) > 0): ?>
                    <?php foreach ($artikel_terbaru as $index => $artikel): ?>
                    <article class="card fade-in" style="animation-delay: <?= $index * 0.2 ?>s;">
                        <div class="card-image">
                            <?php 
                            $imagePath = getImagePath($artikel['gambar'] ?? '', 'artikel', $artikel['judul']);
                            ?>
                            <?php if (filter_var($imagePath, FILTER_VALIDATE_URL) || (is_string($imagePath) && !str_starts_with($imagePath, 'data:'))): ?>
                                <img src="<?= $imagePath ?>" 
                                     alt="<?= htmlspecialchars($artikel['gambar_alt'] ?? $artikel['judul']) ?>"
                                     loading="lazy"
                                     onerror="this.parentElement.innerHTML='<div class=\'card-image-placeholder\'><i class=\'fas fa-pen-fancy\'></i></div>'">
                            <?php else: ?>
                                <img src="<?= $imagePath ?>" alt="Artikel <?= htmlspecialchars($artikel['judul']) ?>" loading="lazy">
                            <?php endif; ?>
                            <div class="card-badge artikel">Artikel</div>
                            <?php if ($artikel['featured'] ?? false): ?>
                                <div class="card-badge" style="top: 15px; right: 15px; left: auto; background: rgba(212, 175, 55, 0.9);">
                                    <i class="fas fa-star"></i> Featured
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                            <div class="card-meta">
                                <span>
                                    <i class="fas fa-calendar"></i> 
                                    <?= formatTanggalIndonesia($artikel['tanggal_publish'] ?? $artikel['created_at']) ?>
                                </span>
                                <span>
                                    <i class="fas fa-user"></i> 
                                    <?= htmlspecialchars($artikel['penulis']) ?>
                                </span>
                                <?php if (!empty($artikel['kategori'])): ?>
                                <span style="background: var(--primary-color); color: white; padding: 2px 8px; border-radius: 10px;">
                                    <i class="fas fa-tag"></i> 
                                    <?= htmlspecialchars($artikel['kategori']) ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($artikel['views'])): ?>
                                <span>
                                    <i class="fas fa-eye"></i> 
                                    <?= number_format($artikel['views']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <h3 class="card-title">
                                <a href="artikel-detail.php?slug=<?= $artikel['slug'] ?>">
                                    <?= htmlspecialchars($artikel['judul']) ?>
                                </a>
                            </h3>
                            <p class="card-excerpt">
                                <?php 
                                $excerpt = !empty($artikel['excerpt']) ? $artikel['excerpt'] : substr(strip_tags($artikel['konten']), 0, 150) . '...';
                                echo htmlspecialchars($excerpt);
                                ?>
                            </p>
                            <a href="artikel-detail.php?slug=<?= $artikel['slug'] ?>" class="read-more">
                                Baca Artikel <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state fade-in">
                        <div class="empty-icon">📝</div>
                        <h3>Belum Ada Artikel</h3>
                        <p>Artikel inspiratif akan segera hadir. Nantikan tulisan terbaru kami!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section-footer fade-in">
                <a href="artikel.php" class="btn btn-primary">
                    <i class="fas fa-book-open"></i>
                    Lihat Semua Artikel
                </a>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="section">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Galeri Kegiatan</h2>
                <p class="section-subtitle">
                    Dokumentasi kegiatan dan momen-momen berharga UKM Madani
                </p>
            </div>
            
            <div class="gallery-grid">
                <?php if (count($galeri_terbaru) > 0): ?>
                    <?php foreach ($galeri_terbaru as $index => $galeri): ?>
                    <div class="gallery-item fade-in" style="animation-delay: <?= $index * 0.1 ?>s;" onclick="openGallery('<?= htmlspecialchars($galeri['google_drive_link']) ?>')">
                        <div class="gallery-image">
                            <?php 
                            $imagePath = getImagePath($galeri['cover_image'] ?? '', 'galeri', $galeri['judul']);
                            ?>
                            <?php if (filter_var($imagePath, FILTER_VALIDATE_URL) || (is_string($imagePath) && !str_starts_with($imagePath, 'data:'))): ?>
                                <img src="<?= $imagePath ?>" 
                                     alt="<?= htmlspecialchars($galeri['judul']) ?>"
                                     loading="lazy"
                                     onerror="this.parentElement.innerHTML='<div class=\'card-image-placeholder\'><i class=\'fas fa-camera\'></i></div>'">
                            <?php else: ?>
                                <img src="<?= $imagePath ?>" alt="<?= htmlspecialchars($galeri['judul']) ?>" loading="lazy">
                            <?php endif; ?>
                            <div class="gallery-overlay">
                                <i class="fas fa-external-link-alt"></i>
                                <div class="gallery-overlay-text">Lihat Dokumentasi</div>
                            </div>
                            
                            <?php if (!empty($galeri['kategori'])): ?>
                                <div class="gallery-badge"><?= htmlspecialchars($galeri['kategori']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($galeri['total_foto'] > 0): ?>
                                <div class="gallery-photo-count">
                                    <i class="fas fa-images"></i>
                                    <?= $galeri['total_foto'] ?> foto
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="gallery-content">
                            <div class="gallery-title">
                                <h3><?= htmlspecialchars($galeri['judul']) ?></h3>
                            </div>
                            <div class="gallery-meta">
                                <span>
                                    <i class="fas fa-calendar"></i>
                                    <?= formatTanggalIndonesia($galeri['tanggal_kegiatan']) ?>
                                </span>
                                <?php if (!empty($galeri['lokasi'])): ?>
                                <span>
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($galeri['lokasi']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($galeri['deskripsi'])): ?>
                            <p class="gallery-description">
                                <?= htmlspecialchars($galeri['deskripsi']) ?>
                            </p>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars($galeri['google_drive_link']) ?>" target="_blank" class="gallery-button" onclick="event.stopPropagation()">
                                <i class="fas fa-external-link-alt"></i> Lihat Dokumentasi
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state fade-in" style="grid-column: 1 / -1;">
                        <div class="empty-icon">📸</div>
                        <h3>Belum Ada Galeri</h3>
                        <p>Galeri kegiatan akan segera hadir. Pantau terus dokumentasi kegiatan kami!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section-footer fade-in">
                <a href="galeri.php" class="btn btn-primary">
                    <i class="fas fa-images"></i>
                    Lihat Semua Galeri
                </a>
            </div>
        </div>
    </section>

<!-- Donation Section -->
    <section id="donation" class="section bg-light">
        <div class="container">
            <div class="section-header fade-in">
                <h2 class="section-title">Infaq Madani</h2>
                <p class="section-subtitle">
                    <!-- Mari berkontribusi melalui donasi untuk mendukung kegiatan dakwah dan pengembangan UKM Madani -->
                </p>
            </div>
            
            <div class="donation-content">
                <div class="donation-form slide-in-left">
                    <div class="form-header">
                        <h3>Kritik & Saran Website</h3>
                        <p>Masukan Anda sangat berharga bagi perkembangan website kami</p>
                    </div>
                    
                    <!-- Google Form Embed -->
                    <div class="google-form-container">
                        <iframe 
                            src="https://forms.gle/QwkuV5RcX61eqcN2A" 
                            width="100%" 
                            height="800" 
                            frameborder="0" 
                            marginheight="0" 
                            marginwidth="0"
                            style="border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            Loading…
                        </iframe>
                    </div>
                </div>
                
                <div class="donation-info slide-in-right">
                    <div class="info-header">
                        <h3>Informasi Rekening</h3>
                        <p>Transfer ke salah satu rekening berikut:</p>
                    </div>
                    
                    <div class="qris-section">
                        <h4>Scan QRIS</h4>
                        <div class="qris-container">
                            <div class="qris-placeholder">
                                <i class="fas fa-qrcode"></i>
                                <p>QR Code<br>UKM Madani</p>
                            </div>
                        </div>
                        <p class="qris-text">QR Code akan segera tersedia</p>
                    </div>
                    
                    <div class="bank-accounts">
                        <div class="bank-item">
                            <div class="bank-logo">
                                <i class="fas fa-university" style="color: #0066cc;"></i>
                            </div>
                            <div class="bank-details">
                                <h4>Bank BRI</h4>
                                <p class="account-number">3267 0105 1866 539</p>
                                <p class="account-name">a.n. Dewi Hotimatur Romdoni</p>
                            </div>
                            <button class="copy-btn" onclick="copyToClipboard('3267 0105 1866 539')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        
                        <div class="bank-item">
                            <div class="bank-logo">
                                <i class="fas fa-university" style="color: #28a745;"></i>
                            </div>
                            <div class="bank-details">
                                <h4>DANA</h4>
                                <p class="account-number">+62 815-3986-0169</p>
                                <p class="account-name">a.n. Dio Rizky Pratama</p>
                            </div>
                            <button class="copy-btn" onclick="copyToClipboard('+62 815-3986-0169')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    
                     <div class="donation-notes">
        <h4><i class="fas fa-info-circle"></i> Catatan Penting</h4>
        <ul>
            <li>Setelah melakukan transfer, kirimkan bukti transfer ke nomor WhatsApp berikut:
                <div class="contact-container">
                    <span class="contact-label">Ikhwan:</span>
                    <a href="https://wa.me/6281539860169?text=Assalamualaikum%2C%20saya%20ingin%20mengirim%20bukti%20transfer%20infaq%20madani" 
                       class="whatsapp-link" target="_blank">
                        <i class="fab fa-whatsapp"></i>
                        +62 815-3986-0169
                    </a>
                </div>
                <div class="contact-container">
                    <span class="contact-label">Akhwat:</span>
                    <a href="https://wa.me/6285269359166?text=Assalamualaikum%2C%20saya%20ingin%20mengirim%20bukti%20transfer%20infaq%20madani" 
                       class="whatsapp-link" target="_blank">
                        <i class="fab fa-whatsapp"></i>
                        +62 852-6935-9166
                    </a>
                </div>
            </li>
        </ul>
    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="footer" class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section fade-in">
                    <div class="footer-logo">
                        <?php if (file_exists('assets/images/logo-madani.png')): ?>
                            <img src="assets/images/logo-madani.png" alt="UKM Madani Logo">
                        <?php else: ?>
                            <div style="width: 60px; height: 60px; background: var(--secondary-color); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.8rem; margin-bottom: 10px;">
                                <i class="fas fa-mosque"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3 style="color: var(--secondary-color); font-size: 1.8rem; margin-bottom: 15px;">UKM Madani</h3>
                    <p style="max-width: 400px; margin: 0 auto; line-height: 1.8; font-size: 1rem;">
                        Mahasiswa Peradaban Islam yang berkomitmen membangun generasi muslim yang berakhlak mulia, berilmu, dan berperadaban.
                    </p>
                    
                    <div class="social-links">
                        <a href="https://www.facebook.com/MadaniItera/" aria-label="Facebook" style="background: #1877f2;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://www.instagram.com/madaniitera/" aria-label="Instagram" style="background: #E4405F;">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://x.com/madaniitera" aria-label="Twitter" style="background: #1da1f2;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.youtube.com/c/MadaniItera" aria-label="YouTube" style="background: #ff0000;">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="https://wa.me/6287889452909" aria-label="WhatsApp" style="background: #25d366;">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-section fade-in">
                    <h4>Link Cepat</h4>
                    <ul>
                        <li><a href="#home"><i class="fas fa-home"></i> Beranda</a></li>
                        <li><a href="#about"><i class="fas fa-info-circle"></i> Tentang</a></li>
                        <li><a href="#news"><i class="fas fa-newspaper"></i> Berita</a></li>
                        <li><a href="#articles"><i class="fas fa-book-open"></i> Artikel</a></li>
                        <li><a href="#gallery"><i class="fas fa-images"></i> Galeri</a></li>
                        <li><a href="#donation"><i class="fas fa-envelope"></i> Infaq</a></li>
                    </ul>
                </div>
                
               <div class="footer-section fade-in">
                    <h4>Program Kami</h4>
                    <ul>
                        <li><a href="#"><i class="fas fa-fire"></i> ONFIRE</a></li>
                        <li><a href="#"><i class="fas fa-school"></i> ABATA (Aksi Bina TPA)</a></li>
                        <li><a href="#"><i class="fas fa-user-check"></i> GEMAR (Gerakan Menutup Aurat)</a></li>
                        <li><a href="#"><i class="fas fa-graduation-cap"></i> Madani Goes To School</a></li>
                        <li><a href="#"><i class="fas fa-users"></i> SAKURA (Seminar Kemuslimahan)</a></li>
                        <li><a href="#"><i class="fas fa-ellipsis-h"></i> Dan masih banyak lagi</a></li>
                    </ul>
                </div>
                
                <div class="footer-section fade-in">
                    <h4>Kontak Info</h4>
                    <div class="contact-info-list">
                        <div class="contact-info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Institut Teknologi Sumatera</span>
                        </div>
                        <div class="contact-info-item">
                            <i class="fas fa-phone"></i>
                            <span>+6287889452909</span>
                        </div>
                        <div class="contact-info-item">
                            <i class="fas fa-envelope"></i>
                            <span>madani@lk.itera.ac.id</span>
                        </div>
                        <div class="contact-info-item">
                            <i class="fas fa-clock"></i>
                            <span>Sen-Jum: 08:00-17:00</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; 2025 UKM Madani ITERA. All rights reserved. Created with ❤️ by Madani Web Division</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-toggle').addEventListener('click', function() {
            this.classList.toggle('active');
            document.getElementById('nav-menu').classList.toggle('active');
            document.body.style.overflow = this.classList.contains('active') ? 'hidden' : '';
        });

        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                const mobileToggle = document.getElementById('mobile-toggle');
                const navMenu = document.getElementById('nav-menu');
                mobileToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const mobileToggle = document.getElementById('mobile-toggle');
            const navMenu = document.getElementById('nav-menu');
            
            if (!mobileToggle.contains(e.target) && !navMenu.contains(e.target)) {
                mobileToggle.classList.remove('active');
                navMenu.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Scroll Progress Bar
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = (scrolled / documentHeight) * 100;
            document.querySelector('.scroll-progress').style.width = `${Math.min(progress, 100)}%`;
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            const scrollTop = document.querySelector('.scroll-top');
            
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
                scrollTop.classList.add('visible');
            } else {
                header.classList.remove('scrolled');
                scrollTop.classList.remove('visible');
            }
        });

        // Scroll to top
        document.getElementById('scroll-top').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const headerHeight = document.querySelector('.header').offsetHeight;
                    const targetPosition = target.offsetTop - headerHeight - 20;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Active nav link highlighting
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section[id]');
            const navLinks = document.querySelectorAll('.nav-link');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= (sectionTop - 200)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });

        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe all animated elements
        document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right, .scale-in').forEach(el => {
            observer.observe(el);
        });

        // Hero stats hover effect
        document.querySelectorAll('.stat-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.05)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-5px)';
            });
        });

        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification(`Nomor rekening ${text} berhasil disalin!`, 'success');
            }).catch(function() {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification(`Nomor rekening ${text} berhasil disalin!`, 'success');
            });
        }

        // Form submission with animation
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Button loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
                submitBtn.disabled = true;
                
                // Simulate form submission
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Terkirim!';
                    submitBtn.style.background = '#28a745';
                    
                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.innerHTML = '<div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-top: 20px; border: 1px solid #c3e6cb;"><i class="fas fa-check-circle"></i> Terima kasih! Pesan Anda telah dikirim. Kami akan segera menghubungi Anda.</div>';
                    this.appendChild(successMsg.firstChild);
                    
                    // Reset form
                    this.reset();
                    
                    // Reset button after 3 seconds
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        submitBtn.style.background = '';
                        const successElement = this.querySelector('div[style*="background: #d4edda"]');
                        if (successElement) {
                            successElement.remove();
                        }
                    }, 3000);
                }, 2000);
            });
        }

        // Enhanced card interactions
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });

        // Gallery functions
        function openGallery(driveLink) {
            if (driveLink && driveLink !== '#' && driveLink !== '') {
                window.open(driveLink, '_blank');
            }
        }

        // Gallery lightbox effect
        document.querySelectorAll('.gallery-item').forEach(item => {
            item.addEventListener('click', function() {
                const link = this.getAttribute('onclick');
                if (link) {
                    eval(link);
                }
            });
        });

        // Add stagger animation for cards
        document.querySelectorAll('.grid .card').forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });

        // Smooth reveal for footer sections
        document.querySelectorAll('.footer-section').forEach((section, index) => {
            section.style.animationDelay = `${index * 0.2}s`;
        });

        // Image loading enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                img.addEventListener('load', function() {
                    this.style.opacity = '1';
                });
                
                img.addEventListener('error', function() {
                    console.warn('Failed to load image:', this.src);
                    // Create placeholder when image fails to load
                    const placeholder = document.createElement('div');
                    placeholder.className = 'card-image-placeholder';
                    placeholder.innerHTML = '<i class="fas fa-image"></i>';
                    this.parentElement.replaceChild(placeholder, this);
                });
            });
        });

        // Responsive text adjustment
        function adjustTextSize() {
            const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
            
            if (vw < 480) {
                document.documentElement.style.fontSize = '14px';
            } else if (vw < 768) {
                document.documentElement.style.fontSize = '15px';
            } else {
                document.documentElement.style.fontSize = '16px';
            }
        }

        // Call on load and resize
        window.addEventListener('load', adjustTextSize);
        window.addEventListener('resize', adjustTextSize);

        // Add loading animation to cards on page load
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.querySelectorAll('.card').forEach((card, index) => {
                    setTimeout(() => {
                        card.classList.add('visible');
                    }, index * 100);
                });
            }, 500);
        });
        
        // Prevent horizontal scroll on mobile
        document.addEventListener('touchmove', function(e) {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false });

        // Add performance monitoring
        if ('performance' in window) {
            window.addEventListener('load', function() {
                setTimeout(() => {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    if (perfData.loadEventEnd - perfData.loadEventStart > 3000) {
                        console.warn('Page load time is slow. Consider optimizing images and assets.');
                    }
                }, 1000);
            });
        }
    </script>
</body>
</html>
