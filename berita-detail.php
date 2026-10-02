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

// Get slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: berita.php');
    exit;
}

// Function untuk format tanggal Indonesia
function formatTanggalIndonesia($date) {
    if (empty($date) || $date === '0000-00-00') {
        return 'Tanggal tidak tersedia';
    }
    
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
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

// Function untuk time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit yang lalu';
    if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
    return formatTanggalIndonesia($datetime);
}

// Function untuk generate gambar placeholder
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
        'artikel' => ['bg' => '#d4af37', 'text' => '#ffffff']
    ];
    
    $color = $colors[$type] ?? $colors['berita'];
    $initial = strtoupper(substr(trim($title), 0, 1)) ?: '📰';
    
    return "data:image/svg+xml;base64," . base64_encode('
    <svg width="800" height="400" xmlns="http://www.w3.org/2000/svg">
        <rect width="800" height="400" fill="' . $color['bg'] . '"/>
        <text x="400" y="200" text-anchor="middle" dominant-baseline="middle" 
              fill="' . $color['text'] . '" font-family="Arial" font-size="120" font-weight="bold">
              ' . htmlspecialchars($initial) . '
        </text>
        <text x="400" y="300" text-anchor="middle" fill="' . $color['text'] . '" 
              font-family="Arial" font-size="24" opacity="0.8">
              BERITA
        </text>
    </svg>');
}

// Get berita data
$berita = null;
try {
    $stmt = $conn->prepare("SELECT * FROM berita WHERE slug = ? AND status = 'published'");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $berita = $result->fetch_assoc();
        
        // Update views
        $update_stmt = $conn->prepare("UPDATE berita SET views = COALESCE(views, 0) + 1 WHERE id = ?");
        $update_stmt->bind_param("i", $berita['id']);
        $update_stmt->execute();
        $berita['views'] = ($berita['views'] ?? 0) + 1;
    }
} catch (Exception $e) {
    error_log("Error fetching berita: " . $e->getMessage());
}

if (!$berita) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Get related news
$related_news = [];
try {
    $stmt = $conn->prepare("SELECT * FROM berita WHERE slug != ? AND status = 'published' ORDER BY created_at DESC LIMIT 3");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $related_news[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching related news: " . $e->getMessage());
}

// Get latest news for sidebar
$latest_news = [];
try {
    $stmt = $conn->prepare("SELECT * FROM berita WHERE slug != ? AND status = 'published' ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $latest_news[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching latest news: " . $e->getMessage());
}

// Meta tags for SEO
$meta_title = htmlspecialchars($berita['judul']) . ' - UKM Madani';
$meta_description = htmlspecialchars(substr(strip_tags($berita['konten']), 0, 160));
$meta_image = !empty($berita['gambar']) ? 'https://' . $_SERVER['HTTP_HOST'] . '/' . getImagePath($berita['gambar'], 'berita') : '';
$canonical_url = 'https://' . $_SERVER['HTTP_HOST'] . '/berita-detail.php?slug=' . urlencode($berita['slug']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon - Logo Madani di Tab -->
    <link rel="icon" type="image/x-icon" href="assets/images/logo-madani.png">
    <link rel="shortcut icon" href="assets/images/logo-madani.png">
    <link rel="apple-touch-icon" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/logo-madani.png">

    <!-- SEO Meta Tags -->
    <title><?= $meta_title ?></title>
    <meta name="description" content="<?= $meta_description ?>">
    <meta name="keywords" content="UKM Madani, berita, <?= htmlspecialchars($berita['judul']) ?>">
    <meta name="author" content="<?= htmlspecialchars($berita['penulis']) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $canonical_url ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= $canonical_url ?>">
    <meta property="og:title" content="<?= $meta_title ?>">
    <meta property="og:description" content="<?= $meta_description ?>">
    <meta property="og:image" content="<?= $meta_image ?>">
    <meta property="og:site_name" content="UKM Madani">
    <meta property="article:author" content="<?= htmlspecialchars($berita['penulis']) ?>">
    <meta property="article:published_time" content="<?= date('c', strtotime($berita['tanggal_publish'] ?? $berita['created_at'])) ?>">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $canonical_url ?>">
    <meta property="twitter:title" content="<?= $meta_title ?>">
    <meta property="twitter:description" content="<?= $meta_description ?>">
    <meta property="twitter:image" content="<?= $meta_image ?>">
    
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
            line-height: 1.7;
            color: var(--text-primary);
            background: var(--bg-primary);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* HEADER */
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
            font-size: 1.5rem;
            font-weight: 700;
            transition: transform 0.3s ease;
        }

        .nav-brand:hover {
            transform: scale(1.05);
        }

        .nav-brand img {
            width: 35px;
            height: 35px;
            object-fit: contain;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 30px;
            align-items: center;
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
        }

        .nav-link:hover {
            color: var(--primary-color);
            background: rgba(26, 95, 63, 0.05);
            transform: translateY(-2px);
        }

        .back-btn {
            background: var(--primary-color);
            color: white !important;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
        }

        .back-btn:hover {
            background: #145a3a;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(26, 95, 63, 0.4);
        }

        /* HAMBURGER MENU */
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            padding: 8px;
            border: none;
            background: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .hamburger:hover {
            background: rgba(26, 95, 63, 0.1);
        }

        .hamburger-line {
            width: 25px;
            height: 3px;
            background: var(--primary-color);
            margin: 3px 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .hamburger.active .hamburger-line:nth-child(1) {
            transform: rotate(45deg) translate(7px, 7px);
        }

        .hamburger.active .hamburger-line:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active .hamburger-line:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        /* MOBILE MENU */
        .mobile-menu {
            position: fixed;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100vh;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            z-index: 1002;
            transition: left 0.3s ease;
            padding-top: 80px;
        }

        .mobile-menu.active {
            left: 0;
        }

        .mobile-menu-content {
            padding: 20px;
            max-width: 400px;
            margin: 0 auto;
        }

        .mobile-menu-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }

        .mobile-menu-header h3 {
            color: var(--primary-color);
            font-family: 'Amiri', serif;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .mobile-nav-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .mobile-nav-item {
            margin-bottom: 15px;
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .mobile-nav-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .mobile-nav-link i {
            font-size: 1.2rem;
            width: 25px;
            text-align: center;
        }

        .mobile-close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--danger-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .mobile-close-btn:hover {
            background: #c82333;
            transform: scale(1.1);
        }

        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .mobile-menu-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* NEWS CONTENT */
        .news-content {
            margin-top: 80px;
            padding: 50px 0;
        }

        .news-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 50px;
        }

        /* NEWS HEADER */
        .news-header {
            text-align: center;
            margin-bottom: 50px;
            padding: 40px 0;
            background: linear-gradient(135deg, rgba(26, 95, 63, 0.05), rgba(212, 175, 55, 0.05));
            border-radius: 20px;
        }

        .news-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 25px;
            line-height: 1.2;
            font-family: 'Amiri', serif;
        }

        .news-meta {
            display: flex;
            justify-content: center;
            gap: 30px;
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .meta-item:hover {
            transform: translateY(-2px);
        }

        .meta-item i {
            color: var(--primary-color);
        }

        /* FEATURED IMAGE */
        .featured-image {
            margin: 40px 0;
            text-align: center;
        }

        .featured-image img {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 20px;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .featured-image img:hover {
            transform: scale(1.02);
        }

        .image-caption {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-style: italic;
            margin-top: 15px;
            text-align: center;
        }

        /* NEWS BODY */
        .news-main {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .news-body {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-primary);
        }

        .news-body h1,
        .news-body h2,
        .news-body h3,
        .news-body h4,
        .news-body h5,
        .news-body h6 {
            color: var(--primary-color);
            margin: 30px 0 20px 0;
            font-family: 'Amiri', serif;
            font-weight: 700;
            line-height: 1.3;
        }

        .news-body h1 { font-size: 2.5rem; }
        .news-body h2 { font-size: 2rem; }
        .news-body h3 { font-size: 1.7rem; }
        .news-body h4 { font-size: 1.4rem; }
        .news-body h5 { font-size: 1.2rem; }
        .news-body h6 { font-size: 1rem; }

        .news-body p {
            margin-bottom: 20px;
            text-align: justify;
        }

        .news-body blockquote {
            background: rgba(26, 95, 63, 0.05);
            border-left: 5px solid var(--secondary-color);
            padding: 25px 30px;
            margin: 30px 0;
            border-radius: 0 15px 15px 0;
            font-style: italic;
            position: relative;
        }

        .news-body blockquote::before {
            content: '"';
            font-size: 4rem;
            color: var(--secondary-color);
            position: absolute;
            top: -10px;
            left: 15px;
            font-family: 'Amiri', serif;
        }

        .news-body ul,
        .news-body ol {
            margin: 20px 0;
            padding-left: 30px;
        }

        .news-body li {
            margin-bottom: 10px;
        }

        .news-body a {
            color: var(--primary-color);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .news-body a:hover {
            border-bottom-color: var(--secondary-color);
        }

        .news-body img {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            margin: 30px 0;
            box-shadow: var(--shadow);
        }

        .news-body pre {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            overflow-x: auto;
            margin: 20px 0;
        }

        .news-body code {
            background: rgba(26, 95, 63, 0.1);
            color: var(--primary-color);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }

        /* SIDEBAR */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .sidebar-widget {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .widget-title {
            color: var(--primary-color);
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .widget-news {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .widget-news:hover {
            transform: translateX(5px);
        }

        .widget-news:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .widget-news-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .widget-news-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .widget-news:hover .widget-news-image img {
            transform: scale(1.1);
        }

        .widget-news-content h4 {
            font-size: 0.95rem;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .widget-news-content h4 a {
            color: var(--text-primary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .widget-news-content h4 a:hover {
            color: var(--primary-color);
        }

        .widget-news-meta {
            font-size: 0.8rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* SOCIAL SHARE */
        .social-share {
            background: var(--bg-secondary);
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: center;
        }

        .social-share h4 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .share-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .share-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .share-btn:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .share-btn.facebook { background: #3b5998; }
        .share-btn.twitter { background: #1da1f2; }
        .share-btn.whatsapp { background: #25d366; }
        .share-btn.telegram { background: #0088cc; }
        .share-btn.copy { background: var(--text-secondary); }

        /* RELATED NEWS */
        .related-news {
            margin: 60px 0;
            padding: 50px 0;
            background: var(--bg-secondary);
            border-radius: 20px;
        }

        .related-title {
            text-align: center;
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 40px;
            font-family: 'Amiri', serif;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .related-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .related-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .related-card-image {
            height: 200px;
            overflow: hidden;
        }

        .related-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .related-card:hover .related-card-image img {
            transform: scale(1.05);
        }

        .related-card-content {
            padding: 25px;
        }

        .related-card-meta {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 10px;
            display: flex;
            gap: 15px;
        }

        .related-card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .related-card-title a {
            color: var(--text-primary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .related-card-title a:hover {
            color: var(--primary-color);
        }

        .related-card-excerpt {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .read-more-btn {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .read-more-btn:hover {
            color: var(--secondary-color);
            transform: translateX(5px);
        }

        /* NEWS NAVIGATION */
        .news-navigation {
            display: flex;
            justify-content: space-between;
            margin: 40px 0;
            gap: 20px;
        }

        .nav-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 25px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
            max-width: 300px;
        }

        .nav-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-3px);
        }

        .nav-btn.center {
            justify-content: center;
            max-width: none;
        }

        /* BREADCRUMB */
        .breadcrumb {
            background: var(--bg-secondary);
            padding: 15px 0;
            margin-top: 80px;
            border-radius: 10px;
        }

        .breadcrumb-list {
            display: flex;
            align-items: center;
            gap: 10px;
            list-style: none;
            font-size: 0.9rem;
        }

        .breadcrumb-list a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb-list a:hover {
            color: var(--secondary-color);
        }

        .breadcrumb-separator {
            color: var(--text-secondary);
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .news-layout {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .sidebar {
                order: -1;
            }

            .related-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            .news-main {
                padding: 30px 25px;
            }

            .news-meta {
                flex-direction: column;
                gap: 15px;
            }

            .news-navigation {
                flex-direction: column;
            }

            .nav-btn {
                max-width: none;
            }

            .share-buttons {
                gap: 10px;
            }

            .navbar .container {
                position: relative;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }

            .news-content {
                padding: 30px 0;
            }

            .news-header {
                padding: 25px 20px;
            }

            .featured-image {
                margin: 25px 0;
            }

            .related-grid {
                grid-template-columns: 1fr;
            }

            .news-body {
                font-size: 1rem;
            }
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
        }

        /* PRINT STYLES */
        @media print {
            .header,
            .sidebar,
            .social-share,
            .related-news,
            .news-navigation,
            .scroll-top {
                display: none !important;
            }

            .news-content {
                margin-top: 0;
            }

            .news-layout {
                grid-template-columns: 1fr;
            }

            .news-main {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            body {
                font-size: 12pt;
                line-height: 1.4;
            }
        }
    </style>
    <link href="assets/css/audit-fixes.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="container">
                <a href="index.php" class="nav-brand">
                    <?php if (file_exists('assets/images/logo-madani.png')): ?>
                        <img src="assets/images/logo-madani.png" alt="UKM Madani Logo">
                    <?php else: ?>
                        <div style="width: 35px; height: 35px; background: var(--secondary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">M</div>
                    <?php endif; ?>
                    UKM MADANI
                </a>
                
                <ul class="nav-menu">
                    <li><a href="index.php" class="nav-link">Beranda</a></li>
                    <li><a href="index.php#about" class="nav-link">Tentang</a></li>
                    <li><a href="berita.php" class="nav-link">Berita</a></li>
                    <li><a href="artikel.php" class="nav-link">Artikel</a></li>
                    <li><a href="galeri.php" class="nav-link">Gallery</a></li>
                    <li><a href="index.php#donation" class="nav-link">Infaq</a></li>
                    <li><a href="index.php#contact" class="nav-link">Kontak</a></li>
                    <li><a href="berita.php" class="nav-link back-btn">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a></li>
                </ul>

                <!-- Hamburger Menu Button -->
                <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </div>
        </nav>
    </header>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    <div class="mobile-menu" id="mobileMenu">
        <button class="mobile-close-btn" id="mobileCloseBtn" aria-label="Close Menu">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="mobile-menu-content">
            <div class="mobile-menu-header">
                <h3>UKM MADANI</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 5px;">Menu Navigasi</p>
            </div>
            
            <ul class="mobile-nav-list">
                <li class="mobile-nav-item">
                    <a href="index.php" class="mobile-nav-link">
                        <i class="fas fa-home"></i>
                        <span>Beranda</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="index.php#about" class="mobile-nav-link">
                        <i class="fas fa-info-circle"></i>
                        <span>Tentang Kami</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="berita.php" class="mobile-nav-link">
                        <i class="fas fa-newspaper"></i>
                        <span>Berita</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="artikel.php" class="mobile-nav-link">
                        <i class="fas fa-pen-fancy"></i>
                        <span>Artikel</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="galeri.php" class="mobile-nav-link">
                        <i class="fas fa-images"></i>
                        <span>Gallery</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="index.php#donation" class="mobile-nav-link">
                        <i class="fas fa-hand-holding-heart"></i>
                        <span>Infaq</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="index.php#contact" class="mobile-nav-link">
                        <i class="fas fa-envelope"></i>
                        <span>Kontak</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="berita.php" class="mobile-nav-link" style="background: var(--primary-color); color: white;">
                        <i class="fas fa-arrow-left"></i>
                        <span>Kembali ke Berita</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <ul class="breadcrumb-list">
                <li><a href="index.php">Beranda</a></li>
                <li class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></li>
                <li><a href="berita.php">Berita</a></li>
                <li class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></li>
                <li><span aria-current="page"><?= htmlspecialchars(substr($berita['judul'], 0, 50)) ?><?= strlen($berita['judul']) > 50 ? '...' : '' ?></span></li>
            </ul>
        </div>
    </div>

    <!-- News Content -->
    <main class="news-content">
        <div class="container">
            <!-- News Header -->
            <header class="news-header">
                <h1 class="news-title"><?= htmlspecialchars($berita['judul']) ?></h1>
                
                <div class="news-meta">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span><?= htmlspecialchars($berita['penulis']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span><?= formatTanggalIndonesia($berita['tanggal_publish'] ?? $berita['created_at']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?= timeAgo($berita['created_at']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-eye"></i>
                        <span><?= number_format($berita['views']) ?> views</span>
                    </div>
                </div>
            </header>

            <div class="news-layout">
                <!-- Main News -->
                <article class="news-main">
                    <!-- Featured Image -->
                    <?php if (!empty($berita['gambar'])): ?>
                        <div class="featured-image">
                            <?php 
                            $imagePath = getImagePath($berita['gambar'], 'berita', $berita['judul']);
                            ?>
                            <img src="<?= $imagePath ?>" 
                                 alt="<?= htmlspecialchars($berita['gambar_alt'] ?? $berita['judul']) ?>"
                                 loading="lazy">
                            <?php if (!empty($berita['gambar_alt'])): ?>
                                <p class="image-caption"><?= htmlspecialchars($berita['gambar_alt']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- News Body -->
                    <div class="news-body">
                        <?= $berita['konten'] ?>
                    </div>

                    <!-- Social Share -->
                    <div class="social-share">
                        <h4>
                            <i class="fas fa-share-alt"></i>
                            Bagikan Berita
                        </h4>
                        <div class="share-buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonical_url) ?>" 
                               target="_blank" 
                               class="share-btn facebook"
                               title="Bagikan ke Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?text=<?= urlencode($berita['judul']) ?>&url=<?= urlencode($canonical_url) ?>" 
                               target="_blank" 
                               class="share-btn twitter"
                               title="Bagikan ke Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://wa.me/?text=<?= urlencode($berita['judul'] . ' - ' . $canonical_url) ?>" 
                               target="_blank" 
                               class="share-btn whatsapp"
                               title="Bagikan ke WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="https://t.me/share/url?url=<?= urlencode($canonical_url) ?>&text=<?= urlencode($berita['judul']) ?>" 
                               target="_blank" 
                               class="share-btn telegram"
                               title="Bagikan ke Telegram">
                                <i class="fab fa-telegram-plane"></i>
                            </a>
                            <button onclick="copyToClipboard('<?= $canonical_url ?>')" 
                                    class="share-btn copy"
                                    title="Salin Link">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </article>

                <!-- Sidebar -->
                <aside class="sidebar">
                    <!-- Latest News -->
                    <?php if (count($latest_news) > 0): ?>
                        <div class="sidebar-widget">
                            <h3 class="widget-title">
                                <i class="fas fa-newspaper"></i>
                                Berita Terbaru
                            </h3>
                            <?php foreach ($latest_news as $latest): ?>
                                <div class="widget-news">
                                    <div class="widget-news-image">
                                        <?php 
                                        $latestImagePath = getImagePath($latest['gambar'] ?? '', 'berita', $latest['judul']);
                                        ?>
                                        <img src="<?= $latestImagePath ?>" 
                                             alt="<?= htmlspecialchars($latest['judul']) ?>"
                                             loading="lazy">
                                    </div>
                                    <div class="widget-news-content">
                                        <h4>
                                            <a href="berita-detail.php?slug=<?= $latest['slug'] ?>">
                                                <?= htmlspecialchars(substr($latest['judul'], 0, 80)) ?><?= strlen($latest['judul']) > 80 ? '...' : '' ?>
                                            </a>
                                        </h4>
                                        <div class="widget-news-meta">
                                            <span><i class="fas fa-calendar"></i> <?= timeAgo($latest['created_at']) ?></span>
                                            <span><i class="fas fa-eye"></i> <?= $latest['views'] ?? 0 ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Archive Widget -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-archive"></i>
                            Arsip Berita
                        </h3>
                        <?php
                        // Get archive by month
                        $archives = [];
                        try {
                            $result = $conn->query("
                                SELECT DATE_FORMAT(created_at, '%Y-%m') as month_year,
                                       DATE_FORMAT(created_at, '%M %Y') as month_name,
                                       COUNT(*) as count
                                FROM berita 
                                WHERE status = 'published'
                                GROUP BY month_year 
                                ORDER BY month_year DESC 
                                LIMIT 12
                            ");
                            if ($result) {
                                while ($row = $result->fetch_assoc()) {
                                    $archives[] = $row;
                                }
                            }
                        } catch (Exception $e) {
                            // Silent error
                        }
                        ?>
                        
                        <?php if (count($archives) > 0): ?>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <?php foreach ($archives as $archive): ?>
                                    <a href="berita.php?archive=<?= $archive['month_year'] ?>" 
                                       style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; color: var(--text-primary); text-decoration: none; border-radius: 6px; transition: all 0.3s ease;"
                                       onmouseover="this.style.background='var(--bg-secondary)'; this.style.color='var(--primary-color)';"
                                       onmouseout="this.style.background='transparent'; this.style.color='var(--text-primary)';">
                                        <span><?= $archive['month_name'] ?></span>
                                        <span style="font-size: 0.85rem; color: var(--text-secondary);">(<?= $archive['count'] ?>)</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Popular News Widget -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-fire"></i>
                            Berita Populer
                        </h3>
                        <?php
                        // Get popular news
                        $popular_news = [];
                        try {
                            $result = $conn->query("
                                SELECT * FROM berita 
                                WHERE status = 'published' AND slug != '$slug'
                                ORDER BY views DESC 
                                LIMIT 5
                            ");
                            if ($result) {
                                while ($row = $result->fetch_assoc()) {
                                    $popular_news[] = $row;
                                }
                            }
                        } catch (Exception $e) {
                            // Silent error
                        }
                        ?>
                        
                        <?php if (count($popular_news) > 0): ?>
                            <?php foreach ($popular_news as $index => $popular): ?>
                                <div class="widget-news">
                                    <div style="display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; background: var(--secondary-color); color: white; border-radius: 50%; font-weight: bold; font-size: 0.9rem; flex-shrink: 0;">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div class="widget-news-content" style="flex: 1;">
                                        <h4>
                                            <a href="berita-detail.php?slug=<?= $popular['slug'] ?>">
                                                <?= htmlspecialchars(substr($popular['judul'], 0, 70)) ?><?= strlen($popular['judul']) > 70 ? '...' : '' ?>
                                            </a>
                                        </h4>
                                        <div class="widget-news-meta">
                                            <span><i class="fas fa-eye"></i> <?= number_format($popular['views'] ?? 0) ?> views</span>
                                            <span><i class="fas fa-calendar"></i> <?= timeAgo($popular['created_at']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <!-- Related News -->
    <?php if (count($related_news) > 0): ?>
        <section class="related-news">
            <div class="container">
                <h2 class="related-title">
                    <i class="fas fa-newspaper"></i>
                    Berita Terkait
                </h2>
                
                <div class="related-grid">
                    <?php foreach ($related_news as $related): ?>
                        <article class="related-card">
                            <div class="related-card-image">
                                <?php 
                                $relatedImagePath = getImagePath($related['gambar'] ?? '', 'berita', $related['judul']);
                                ?>
                                <img src="<?= $relatedImagePath ?>" 
                                     alt="<?= htmlspecialchars($related['judul']) ?>"
                                     loading="lazy">
                            </div>
                            <div class="related-card-content">
                                <div class="related-card-meta">
                                    <span><i class="fas fa-calendar"></i> <?= timeAgo($related['created_at']) ?></span>
                                    <span><i class="fas fa-eye"></i> <?= $related['views'] ?? 0 ?> views</span>
                                </div>
                                <h3 class="related-card-title">
                                    <a href="berita-detail.php?slug=<?= $related['slug'] ?>">
                                        <?= htmlspecialchars($related['judul']) ?>
                                    </a>
                                </h3>
                                <p class="related-card-excerpt">
                                    <?php 
                                    $excerpt = substr(strip_tags($related['konten']), 0, 120) . '...';
                                    echo htmlspecialchars($excerpt);
                                    ?>
                                </p>
                                <a href="berita-detail.php?slug=<?= $related['slug'] ?>" class="read-more-btn">
                                    Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Navigation -->
    <div class="news-navigation">
        <div class="container">
            <div style="display: flex; justify-content: center;">
                <a href="berita.php" class="nav-btn center">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Berita
                </a>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop">
        <i class="fas fa-arrow-up"></i>
    </button>

        <!-- Scripts -->
        <script src="assets/js/audit-fixes.js"></script>
        <script>
            // Mobile menu functionality
            const hamburgerBtn = document.getElementById('hamburgerBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
            const mobileCloseBtn = document.getElementById('mobileCloseBtn');

            function openMobileMenu() {
                hamburgerBtn.classList.add('active');
                mobileMenu.classList.add('active');
                mobileMenuOverlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function closeMobileMenu() {
                hamburgerBtn.classList.remove('active');
                mobileMenu.classList.remove('active');
                mobileMenuOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }

            hamburgerBtn.addEventListener('click', openMobileMenu);
            mobileCloseBtn.addEventListener('click', closeMobileMenu);
            mobileMenuOverlay.addEventListener('click', closeMobileMenu);

            // Close mobile menu when clicking on menu links
            document.querySelectorAll('.mobile-nav-link').forEach(link => {
                link.addEventListener('click', closeMobileMenu);
            });

            // Close mobile menu with escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                    closeMobileMenu();
                }
            });

            // Scroll to top functionality
            const scrollTopBtn = document.getElementById('scrollTop');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 100) {
                    scrollTopBtn.classList.add('visible');
                } else {
                    scrollTopBtn.classList.remove('visible');
                }
            });
            
            scrollTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // Copy to clipboard function
            function copyToClipboard(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(function() {
                        showCopySuccess();
                    }).catch(function(err) {
                        fallbackCopyToClipboard(text);
                    });
                } else {
                    fallbackCopyToClipboard(text);
                }
            }

            function fallbackCopyToClipboard(text) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                
                try {
                    document.execCommand('copy');
                    showCopySuccess();
                } catch (err) {
                    console.error('Failed to copy: ', err);
                    prompt('Copy this link:', text);
                }
                
                document.body.removeChild(textArea);
            }

            function showCopySuccess() {
                const copyBtn = document.querySelector('.share-btn.copy');
                const originalIcon = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                copyBtn.style.background = '#28a745';
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalIcon;
                    copyBtn.style.background = '';
                }, 2000);
            }

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Reading progress indicator
            function updateReadingProgress() {
                const article = document.querySelector('.news-body');
                if (!article) return;
                
                const articleTop = article.offsetTop;
                const articleHeight = article.offsetHeight;
                const windowHeight = window.innerHeight;
                const scrollTop = window.pageYOffset;
                
                const progress = Math.min(
                    Math.max((scrollTop - articleTop + windowHeight * 0.1) / articleHeight, 0),
                    1
                );
                
                // Create progress bar if not exists
                let progressBar = document.querySelector('.reading-progress');
                if (!progressBar) {
                    progressBar = document.createElement('div');
                    progressBar.className = 'reading-progress';
                    progressBar.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 0%;
                        height: 4px;
                        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
                        z-index: 1001;
                        transition: width 0.1s ease;
                    `;
                    document.body.appendChild(progressBar);
                }
                
                progressBar.style.width = (progress * 100) + '%';
            }

            window.addEventListener('scroll', updateReadingProgress);

            // Lazy loading for images
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src || img.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                    imageObserver.observe(img);
                });
            }

            // Print functionality
            function printNews() {
                window.print();
            }

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl+P for print
                if (e.ctrlKey && e.key === 'p') {
                    e.preventDefault();
                    printNews();
                }
                
                // Escape to scroll to top
                if (e.key === 'Escape') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });

            // Share tracking (optional analytics)
            document.querySelectorAll('.share-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const platform = this.classList[1]; // facebook, twitter, etc.
                    // Track share event
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'share', {
                            method: platform,
                            content_type: 'news',
                            item_id: '<?= $berita['slug'] ?>'
                        });
                    }
                });
            });

            // Estimated reading time
            function calculateReadingTime() {
                const text = document.querySelector('.news-body').textContent;
                const wordsPerMinute = 200;
                const words = text.trim().split(/\s+/).length;
                const readingTime = Math.ceil(words / wordsPerMinute);
                
                // Add reading time to meta
                const readingTimeElement = document.createElement('div');
                readingTimeElement.className = 'meta-item';
                readingTimeElement.innerHTML = `<i class="fas fa-book-reader"></i><span>${readingTime} menit baca</span>`;
                document.querySelector('.news-meta').appendChild(readingTimeElement);
            }

            // Calculate reading time on load
            document.addEventListener('DOMContentLoaded', calculateReadingTime);

            // Image zoom functionality
            document.querySelectorAll('.news-body img, .featured-image img').forEach(img => {
                img.style.cursor = 'zoom-in';
                img.addEventListener('click', () => {
                    openImageModal(img);
                });
            });

            function openImageModal(img) {
                const modal = document.createElement('div');
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    cursor: zoom-out;
                    padding: 20px;
                `;
                
                const modalImg = document.createElement('img');
                modalImg.src = img.src;
                modalImg.alt = img.alt;
                modalImg.style.cssText = `
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                    border-radius: 10px;
                    box-shadow: 0 0 50px rgba(0,0,0,0.5);
                `;
                
                const closeBtn = document.createElement('button');
                closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                closeBtn.style.cssText = `
                    position: absolute;
                    top: 20px;
                    right: 20px;
                    background: rgba(255,255,255,0.2);
                    color: white;
                    border: none;
                    padding: 10px;
                    border-radius: 50%;
                    cursor: pointer;
                    font-size: 1.2rem;
                    width: 40px;
                    height: 40px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                `;
                
                modal.appendChild(modalImg);
                modal.appendChild(closeBtn);
                document.body.appendChild(modal);
                
                // Close modal events
                modal.addEventListener('click', (e) => {
                    if (e.target === modal || e.target === closeBtn || e.target.closest('button') === closeBtn) {
                        document.body.removeChild(modal);
                    }
                });
                
                // ESC key to close
                const escHandler = (e) => {
                    if (e.key === 'Escape') {
                        document.body.removeChild(modal);
                        document.removeEventListener('keydown', escHandler);
                    }
                };
                document.addEventListener('keydown', escHandler);
            }

            // Mobile menu toggle (if needed)
            function toggleMobileMenu() {
                const navMenu = document.querySelector('.nav-menu');
                navMenu.classList.toggle('active');
            }

            // Prevent scroll when mobile menu is open
            function preventScroll(e) {
                if (mobileMenu.classList.contains('active')) {
                    e.preventDefault();
                }
            }

            // Add touch event listeners for mobile
            let touchStartY = 0;
            document.addEventListener('touchstart', (e) => {
                touchStartY = e.touches[0].clientY;
            });

            document.addEventListener('touchmove', (e) => {
                if (mobileMenu.classList.contains('active')) {
                    const touchY = e.touches[0].clientY;
                    const touchDiff = touchStartY - touchY;
                    
                    // Allow scrolling within mobile menu content
                    const menuContent = document.querySelector('.mobile-menu-content');
                    const isScrollable = menuContent.scrollHeight > menuContent.clientHeight;
                    
                    if (!isScrollable || 
                        (touchDiff > 0 && menuContent.scrollTop === 0) ||
                        (touchDiff < 0 && menuContent.scrollTop >= menuContent.scrollHeight - menuContent.clientHeight)) {
                        e.preventDefault();
                    }
                }
            });

            // Close mobile menu on window resize
            window.addEventListener('resize', () => {
                if (window.innerWidth > 768 && mobileMenu.classList.contains('active')) {
                    closeMobileMenu();
                }
            });

            // Smooth scroll to top when mobile menu closes
            function smoothCloseMobileMenu() {
                closeMobileMenu();
                setTimeout(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }, 300);
            }

            // Add animation for mobile menu items
            function animateMobileMenuItems() {
                const menuItems = document.querySelectorAll('.mobile-nav-item');
                menuItems.forEach((item, index) => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-20px)';
                    item.style.transition = `all 0.3s ease ${index * 0.1}s`;
                    
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateX(0)';
                    }, 100);
                });
            }

            // Trigger animation when mobile menu opens
            hamburgerBtn.addEventListener('click', () => {
                setTimeout(animateMobileMenuItems, 100);
            });
        </script>

        <!-- Structured Data for SEO -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "NewsArticle",
            "headline": "<?= htmlspecialchars($berita['judul']) ?>",
            "description": "<?= htmlspecialchars($meta_description) ?>",
            "image": "<?= $meta_image ?>",
            "author": {
                "@type": "Person",
                "name": "<?= htmlspecialchars($berita['penulis']) ?>"
            },
            "publisher": {
                "@type": "Organization",
                "name": "UKM Madani",
                "logo": {
                    "@type": "ImageObject",
                    "url": "<?= 'https://' . $_SERVER['HTTP_HOST'] . '/assets/images/logo-madani.png' ?>"
                }
            },
            "datePublished": "<?= date('c', strtotime($berita['tanggal_publish'] ?? $berita['created_at'])) ?>",
            "dateModified": "<?= date('c', strtotime($berita['updated_at'] ?? $berita['created_at'])) ?>",
            "mainEntityOfPage": {
                "@type": "WebPage",
                "@id": "<?= $canonical_url ?>"
            },
            "url": "<?= $canonical_url ?>"
        }
        </script>
    </body>
    </html>
