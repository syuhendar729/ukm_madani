<?php
try {
    require_once 'config/database.php';
    require_once 'config/sitemap-helper.php'; // Include helper
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
    header('Location: index.php');
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
function getImagePath($filename, $type = 'artikel', $title = '') {
    if (empty($filename)) {
        return generatePlaceholder($type, $title);
    }
    
    $path = "assets/uploads/{$type}/" . $filename;
    if (file_exists($path)) {
        return $path;
    }
    
    return generatePlaceholder($type, $title);
}

function generatePlaceholder($type = 'artikel', $title = '') {
    $colors = [
        'artikel' => ['bg' => '#d4af37', 'text' => '#ffffff'],
        'berita' => ['bg' => '#1a5f3f', 'text' => '#ffffff']
    ];
    
    $color = $colors[$type] ?? $colors['artikel'];
    $initial = strtoupper(substr(trim($title), 0, 1)) ?: '📄';
    
    return "data:image/svg+xml;base64," . base64_encode('
    <svg width="800" height="400" xmlns="http://www.w3.org/2000/svg">
        <rect width="800" height="400" fill="' . $color['bg'] . '"/>
        <text x="400" y="200" text-anchor="middle" dominant-baseline="middle" 
              fill="' . $color['text'] . '" font-family="Arial" font-size="120" font-weight="bold">
              ' . htmlspecialchars($initial) . '
        </text>
        <text x="400" y="300" text-anchor="middle" fill="' . $color['text'] . '" 
              font-family="Arial" font-size="24" opacity="0.8">
              ARTIKEL
        </text>
    </svg>');
}

// Get artikel data
$artikel = null;
try {
    $stmt = $conn->prepare("SELECT * FROM artikel WHERE slug = ? AND status = 'published'");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $artikel = $result->fetch_assoc();
        
        // Update views
        $update_stmt = $conn->prepare("UPDATE artikel SET views = views + 1 WHERE id = ?");
        $update_stmt->bind_param("i", $artikel['id']);
        $update_stmt->execute();
        $artikel['views']++;
    }
} catch (Exception $e) {
    error_log("Error fetching artikel: " . $e->getMessage());
}

if (!$artikel) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Get related articles
$related_articles = [];
try {
    $stmt = $conn->prepare("SELECT * FROM artikel WHERE kategori = ? AND slug != ? AND status = 'published' ORDER BY created_at DESC LIMIT 3");
    $stmt->bind_param("ss", $artikel['kategori'], $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $related_articles[] = $row;
    }
    
    // If not enough related articles, get latest articles
    if (count($related_articles) < 3) {
        $remaining = 3 - count($related_articles);
        $stmt = $conn->prepare("SELECT * FROM artikel WHERE slug != ? AND status = 'published' ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("si", $slug, $remaining);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $related_articles[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching related articles: " . $e->getMessage());
}

// Get latest articles for sidebar
$latest_articles = [];
try {
    $stmt = $conn->prepare("SELECT * FROM artikel WHERE slug != ? AND status = 'published' ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $latest_articles[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching latest articles: " . $e->getMessage());
}

// Meta tags for SEO
$meta_title = htmlspecialchars($artikel['judul']) . ' - UKM Madani';
$meta_description = htmlspecialchars($artikel['excerpt'] ?: substr(strip_tags($artikel['konten']), 0, 160));
$meta_image = !empty($artikel['gambar']) ? 'https://' . $_SERVER['HTTP_HOST'] . '/' . getImagePath($artikel['gambar'], 'artikel') : '';
$canonical_url = 'https://' . $_SERVER['HTTP_HOST'] . '/artikel-detail.php?slug=' . urlencode($artikel['slug']);
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
    <meta name="keywords" content="<?= htmlspecialchars($artikel['tags'] ?? '') ?>, UKM Madani, artikel islam, mahasiswa">
    <meta name="author" content="<?= htmlspecialchars($artikel['penulis']) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $canonical_url ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= $canonical_url ?>">
    <meta property="og:title" content="<?= $meta_title ?>">
    <meta property="og:description" content="<?= $meta_description ?>">
    <meta property="og:image" content="<?= $meta_image ?>">
    <meta property="og:site_name" content="UKM Madani">
    <meta property="article:author" content="<?= htmlspecialchars($artikel['penulis']) ?>">
    <meta property="article:published_time" content="<?= date('c', strtotime($artikel['tanggal_publish'] ?? $artikel['created_at'])) ?>">
    <meta property="article:section" content="<?= htmlspecialchars($artikel['kategori']) ?>">
    <?php if (!empty($artikel['tags'])): ?>
        <?php foreach (explode(',', $artikel['tags']) as $tag): ?>
            <meta property="article:tag" content="<?= htmlspecialchars(trim($tag)) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= $canonical_url ?>">
    <meta property="twitter:title" content="<?= $meta_title ?>">
    <meta property="twitter:description" content="<?= $meta_description ?>">
    <meta property="twitter:image" content="<?= $meta_image ?>">
    
     <!-- JSON-LD Structured Data untuk SEO yang lebih baik -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "<?= htmlspecialchars($artikel['judul']) ?>",
        "description": "<?= $meta_description ?>",
        "image": "<?= $meta_image ?>",
        "author": {
            "@type": "Person",
            "name": "<?= htmlspecialchars($artikel['penulis']) ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "UKM Madani ITERA",
            "logo": {
                "@type": "ImageObject",
                "url": "https://<?= $_SERVER['HTTP_HOST'] ?>/assets/images/logo-madani.png"
            }
        },
        "datePublished": "<?= date('c', strtotime($artikel['tanggal_publish'] ?? $artikel['created_at'])) ?>",
        "dateModified": "<?= date('c', strtotime($artikel['updated_at'] ?? $artikel['created_at'])) ?>",
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "<?= $canonical_url ?>"
        }
        <?php if (!empty($artikel['kategori'])): ?>
        ,"articleSection": "<?= htmlspecialchars($artikel['kategori']) ?>"
        <?php endif; ?>
        <?php if (!empty($artikel['tags'])): ?>
        ,"keywords": "<?= htmlspecialchars($artikel['tags']) ?>"
        <?php endif; ?>
    }
    </script>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Prism.js for code highlighting -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet">
    
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
            position: relative;
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
            background: #dc3545;
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

        /* ARTICLE CONTENT */
        .article-content {
            margin-top: 80px;
            padding: 50px 0;
        }

        .article-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 50px;
        }

        /* ARTICLE HEADER */
        .article-header {
            text-align: center;
            margin-bottom: 50px;
            padding: 40px 0;
            background: linear-gradient(135deg, rgba(26, 95, 63, 0.05), rgba(212, 175, 55, 0.05));
            border-radius: 20px;
        }

        .article-category {
            display: inline-block;
            background: var(--secondary-color);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .article-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 25px;
            line-height: 1.2;
            font-family: 'Amiri', serif;
        }

        .article-meta {
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

        .article-tags {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tag {
            background: rgba(26, 95, 63, 0.1);
            color: var(--primary-color);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tag:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
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

        /* ARTICLE BODY */
        .article-main {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .article-body {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-primary);
        }

        .article-body h1,
        .article-body h2,
        .article-body h3,
        .article-body h4,
        .article-body h5,
        .article-body h6 {
            color: var(--primary-color);
            margin: 30px 0 20px 0;
            font-family: 'Amiri', serif;
            font-weight: 700;
            line-height: 1.3;
        }

        .article-body h1 { font-size: 2.5rem; }
        .article-body h2 { font-size: 2rem; }
        .article-body h3 { font-size: 1.7rem; }
        .article-body h4 { font-size: 1.4rem; }
        .article-body h5 { font-size: 1.2rem; }
        .article-body h6 { font-size: 1rem; }

        .article-body p {
            margin-bottom: 20px;
            text-align: justify;
        }

        .article-body blockquote {
            background: rgba(26, 95, 63, 0.05);
            border-left: 5px solid var(--secondary-color);
            padding: 25px 30px;
            margin: 30px 0;
            border-radius: 0 15px 15px 0;
            font-style: italic;
            position: relative;
        }

        .article-body blockquote::before {
            content: '"';
            font-size: 4rem;
            color: var(--secondary-color);
            position: absolute;
            top: -10px;
            left: 15px;
            font-family: 'Amiri', serif;
        }

        .article-body ul,
        .article-body ol {
            margin: 20px 0;
            padding-left: 30px;
        }

        .article-body li {
            margin-bottom: 10px;
        }

        .article-body a {
            color: var(--primary-color);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .article-body a:hover {
            border-bottom-color: var(--secondary-color);
        }

        .article-body img {
            max-width: 100%;
            height: auto;
            border-radius: 15px;
            margin: 30px 0;
            box-shadow: var(--shadow);
        }

        .article-body pre {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            overflow-x: auto;
            margin: 20px 0;
        }

        .article-body code {
            background: rgba(26, 95, 63, 0.1);
            color: var(--primary-color);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }

        .article-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .article-body th,
        .article-body td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .article-body th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
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

        .widget-article {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .widget-article:hover {
            transform: translateX(5px);
        }

        .widget-article:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .widget-article-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .widget-article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .widget-article:hover .widget-article-image img {
            transform: scale(1.1);
        }

        .widget-article-content h4 {
            font-size: 0.95rem;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .widget-article-content h4 a {
            color: var(--text-primary);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .widget-article-content h4 a:hover {
            color: var(--primary-color);
        }

        .widget-article-meta {
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
            border: none;
            cursor: pointer;
        }

        .share-btn:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .share-btn.facebook { background: #3b5998; }
        .share-btn.twitter { background: #1da1f2; }
        .share-btn.whatsapp { background: #25d366; }
        .share-btn.linkedin { background: #0077b5; }
        .share-btn.telegram { background: #0088cc; }
        .share-btn.copy { background: var(--text-secondary); }

        /* RELATED ARTICLES */
        .related-articles {
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

        /* ARTICLE NAVIGATION */
        .article-navigation {
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

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .article-layout {
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

            .article-main {
                padding: 30px 25px;
            }

            .article-meta {
                flex-direction: column;
                gap: 15px;
            }

            .article-navigation {
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

            .article-content {
                padding: 30px 0;
            }

            .article-header {
                padding: 25px 20px;
            }

            .featured-image {
                margin: 25px 0;
            }

            .related-grid {
                grid-template-columns: 1fr;
            }

            .article-body {
                font-size: 1rem;
            }

            .mobile-menu-content {
                padding: 15px;
            }

            .mobile-nav-link {
                padding: 12px 15px;
                font-size: 0.9rem;
            }

            .hamburger-line {
                width: 22px;
            }
        }

        /* PRINT STYLES */
        @media print {
            .header,
            .sidebar,
            .social-share,
            .related-articles,
            .article-navigation,
            .scroll-top,
            .mobile-menu,
            .mobile-menu-overlay,
            .hamburger {
                display: none !important;
            }

            .article-content {
                margin-top: 0;
            }

            .article-layout {
                grid-template-columns: 1fr;
            }

            .article-main {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            body {
                font-size: 12pt;
                line-height: 1.4;
            }
        }
    </style>
</head>
<body>
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
                    <li><a href="index.php#contact" class="nav-link">Kontak</a></li>
                    <li><a href="artikel.php" class="nav-link back-btn">
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
                <li><a href="artikel.php">Artikel</a></li>
                <li class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></li>
                <li><?= htmlspecialchars($artikel['kategori'] ?? 'Artikel') ?></li>
                <li class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></li>
                <li><?= htmlspecialchars(substr($artikel['judul'], 0, 50)) ?><?= strlen($artikel['judul']) > 50 ? '...' : '' ?></li>
            </ul>
        </div>
    </div>

    <!-- Article Content -->
    <main class="article-content">
        <div class="container">
            <!-- Article Header -->
            <header class="article-header">
                <?php if (!empty($artikel['kategori'])): ?>
                    <div class="article-category">
                        <i class="fas fa-folder"></i>
                        <?= htmlspecialchars($artikel['kategori']) ?>
                    </div>
                <?php endif; ?>
                
                <h1 class="article-title"><?= htmlspecialchars($artikel['judul']) ?></h1>
                
                <div class="article-meta">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span><?= htmlspecialchars($artikel['penulis']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar"></i>
                        <span><?= formatTanggalIndonesia($artikel['tanggal_publish'] ?? $artikel['created_at']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?= timeAgo($artikel['created_at']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-eye"></i>
                        <span><?= number_format($artikel['views']) ?> views</span>
                    </div>
                    <?php if ($artikel['featured']): ?>
                        <div class="meta-item" style="background: var(--secondary-color); color: white;">
                            <i class="fas fa-star"></i>
                            <span>Unggulan</span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($artikel['tags'])): ?>
                    <div class="article-tags">
                        <?php foreach (explode(',', $artikel['tags']) as $tag): ?>
                            <span class="tag">
                                <i class="fas fa-tag"></i>
                                <?= htmlspecialchars(trim($tag)) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </header>

            <div class="article-layout">
                <!-- Main Article -->
                <article class="article-main">
                    <!-- Featured Image -->
                    <?php if (!empty($artikel['gambar'])): ?>
                        <div class="featured-image">
                            <?php 
                            $imagePath = getImagePath($artikel['gambar'], 'artikel', $artikel['judul']);
                            ?>
                            <img src="<?= $imagePath ?>" 
                                 alt="<?= htmlspecialchars($artikel['gambar_alt'] ?? $artikel['judul']) ?>"
                                 loading="lazy">
                            <?php if (!empty($artikel['gambar_alt'])): ?>
                                <p class="image-caption"><?= htmlspecialchars($artikel['gambar_alt']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Article Body -->
                    <div class="article-body">
                        <?= $artikel['konten'] ?>
                    </div>

                    <!-- Social Share -->
                    <div class="social-share">
                        <h4 style="margin-bottom: 20px; color: var(--primary-color); display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-share-alt"></i>
                            Bagikan Artikel
                        </h4>
                        <div class="share-buttons">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonical_url) ?>" 
                               target="_blank" 
                               class="share-btn facebook"
                               title="Bagikan ke Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?text=<?= urlencode($artikel['judul']) ?>&url=<?= urlencode($canonical_url) ?>" 
                               target="_blank" 
                               class="share-btn twitter"
                               title="Bagikan ke Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://wa.me/?text=<?= urlencode($artikel['judul'] . ' - ' . $canonical_url) ?>" 
                               target="_blank" 
                               class="share-btn whatsapp"
                               title="Bagikan ke WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($canonical_url) ?>" 
                               target="_blank" 
                               class="share-btn linkedin"
                               title="Bagikan ke LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="https://t.me/share/url?url=<?= urlencode($canonical_url) ?>&text=<?= urlencode($artikel['judul']) ?>" 
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
                    <!-- Latest Articles -->
                    <?php if (count($latest_articles) > 0): ?>
                        <div class="sidebar-widget">
                            <h3 class="widget-title">
                                <i class="fas fa-newspaper"></i>
                                Artikel Terbaru
                            </h3>
                            <?php foreach ($latest_articles as $latest): ?>
                                <div class="widget-article">
                                    <div class="widget-article-image">
                                        <?php 
                                        $latestImagePath = getImagePath($latest['gambar'] ?? '', 'artikel', $latest['judul']);
                                        ?>
                                        <img src="<?= $latestImagePath ?>" 
                                             alt="<?= htmlspecialchars($latest['judul']) ?>"
                                             loading="lazy">
                                    </div>
                                    <div class="widget-article-content">
                                        <h4>
                                            <a href="artikel-detail.php?slug=<?= $latest['slug'] ?>">
                                                <?= htmlspecialchars(substr($latest['judul'], 0, 80)) ?><?= strlen($latest['judul']) > 80 ? '...' : '' ?>
                                            </a>
                                        </h4>
                                        <div class="widget-article-meta">
                                            <span><i class="fas fa-calendar"></i> <?= timeAgo($latest['created_at']) ?></span>
                                            <span><i class="fas fa-eye"></i> <?= $latest['views'] ?? 0 ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Categories Widget -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-folder-open"></i>
                            Kategori Artikel
                        </h3>
                        <?php
                        // Get categories with article count
                        $categories_with_count = [];
                        try {
                            $result = $conn->query("
                                SELECT ak.*, COUNT(a.id) as article_count 
                                FROM artikel_kategori ak 
                                LEFT JOIN artikel a ON ak.nama = a.kategori AND a.status = 'published'
                                GROUP BY ak.id 
                                ORDER BY ak.nama ASC
                            ");
                            if ($result) {
                                while ($row = $result->fetch_assoc()) {
                                    $categories_with_count[] = $row;
                                }
                            }
                        } catch (Exception $e) {
                            // Silent error
                        }
                        ?>
                        
                        <?php if (count($categories_with_count) > 0): ?>
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php foreach ($categories_with_count as $cat): ?>
                                    <a href="artikel.php?kategori=<?= urlencode($cat['slug']) ?>" 
                                       style="display: flex; justify-content: space-between; align-items: center; padding: 10px 15px; background: rgba(26, 95, 63, 0.05); border-radius: 8px; text-decoration: none; color: var(--text-primary); transition: all 0.3s ease;"
                                       onmouseover="this.style.background='rgba(26, 95, 63, 0.1)'; this.style.transform='translateX(5px)';"
                                       onmouseout="this.style.background='rgba(26, 95, 63, 0.05)'; this.style.transform='translateX(0)';">
                                        <span style="color: <?= $cat['warna'] ?>; font-weight: 500;">
                                            <?= htmlspecialchars($cat['nama']) ?>
                                        </span>
                                        <span style="background: var(--primary-color); color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">
                                            <?= $cat['article_count'] ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Archive Widget -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title">
                            <i class="fas fa-archive"></i>
                            Arsip
                        </h3>
                        <?php
                        // Get archive by month
                        $archives = [];
                        try {
                            $result = $conn->query("
                                SELECT DATE_FORMAT(created_at, '%Y-%m') as month_year,
                                       DATE_FORMAT(created_at, '%M %Y') as month_name,
                                       COUNT(*) as count
                                FROM artikel 
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
                                    <a href="artikel.php?archive=<?= $archive['month_year'] ?>" 
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
                </aside>
            </div>
        </div>
    </main>

    <!-- Related Articles -->
    <?php if (count($related_articles) > 0): ?>
        <section class="related-articles">
            <div class="container">
                <h2 class="related-title">
                    <i class="fas fa-newspaper"></i>
                    Artikel Terkait
                </h2>
                
                <div class="related-grid">
                    <?php foreach ($related_articles as $related): ?>
                        <article class="related-card">
                            <div class="related-card-image">
                                <?php 
                                $relatedImagePath = getImagePath($related['gambar'] ?? '', 'artikel', $related['judul']);
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
                                    <a href="artikel-detail.php?slug=<?= $related['slug'] ?>">
                                        <?= htmlspecialchars($related['judul']) ?>
                                    </a>
                                </h3>
                                <p class="related-card-excerpt">
                                    <?php 
                                    $excerpt = !empty($related['excerpt']) ? $related['excerpt'] : substr(strip_tags($related['konten']), 0, 120) . '...';
                                    echo htmlspecialchars($excerpt);
                                    ?>
                                </p>
                                <a href="artikel-detail.php?slug=<?= $related['slug'] ?>" class="read-more-btn">
                                    Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop">
        <i class="fas fa-arrow-up"></i>
    </button>
    <!-- <button class="theme-toggle" id="themeToggle" data-tooltip="Switch to Dark Mode">
    <i class="fas fa-sun icon sun-icon"></i>
    <i class="fas fa-moon icon moon-icon"></i>
    </button> -->

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    
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
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const copyBtn = document.querySelector('.share-btn.copy');
                const originalIcon = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check"></i>';
                copyBtn.style.background = '#28a745';
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalIcon;
                    copyBtn.style.background = '';
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                alert('Gagal menyalin link. Silakan salin manual: ' + text);
            });
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
            const article = document.querySelector('.article-body');
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

        // Highlight current section in sidebar
        function highlightCurrentSection() {
            const headers = document.querySelectorAll('.article-body h1, .article-body h2, .article-body h3');
            const scrollPos = window.pageYOffset + 100;
            
            let current = '';
            headers.forEach(header => {
                if (header.offsetTop <= scrollPos) {
                    current = header.id;
                }
            });
            
            // Update active state if needed
        }

        window.addEventListener('scroll', highlightCurrentSection);

        // Print functionality
        function printArticle() {
            window.print();
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P for print
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                printArticle();
            }
            
            // Escape to scroll to top
            if (e.key === 'Escape') {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        // Font size adjustment
        let fontSize = 1.1;
        function adjustFontSize(delta) {
            fontSize += delta;
            fontSize = Math.max(0.8, Math.min(fontSize, 1.8));
            document.querySelector('.article-body').style.fontSize = fontSize + 'rem';
            localStorage.setItem('articleFontSize', fontSize);
        }

        // Restore saved font size
        const savedFontSize = localStorage.getItem('articleFontSize');
        if (savedFontSize) {
            fontSize = parseFloat(savedFontSize);
            document.querySelector('.article-body').style.fontSize = fontSize + 'rem';
        }

        // Add font size controls (optional)
        function addFontSizeControls() {
            const controls = document.createElement('div');
            controls.innerHTML = `
                <div style="position: fixed; right: 20px; top: 50%; transform: translateY(-50%); background: white; padding: 15px; border-radius: 10px; box-shadow: var(--shadow); display: flex; flex-direction: column; gap: 10px; z-index: 999;">
                    <button onclick="adjustFontSize(0.1)" style="background: var(--primary-color); color: white; border: none; padding: 8px; border-radius: 5px; cursor: pointer;">A+</button>
                    <button onclick="adjustFontSize(-0.1)" style="background: var(--text-muted); color: white; border: none; padding: 8px; border-radius: 5px; cursor: pointer;">A-</button>
                    <button onclick="printArticle()" style="background: var(--secondary-color); color: white; border: none; padding: 8px; border-radius: 5px; cursor: pointer;"><i class="fas fa-print"></i></button>
                </div>
            `;
            document.body.appendChild(controls);
        }

        // Uncomment to add font size controls
        // addFontSizeControls();

        // Share tracking (optional analytics)
        document.querySelectorAll('.share-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const platform = this.classList[1]; // facebook, twitter, etc.
                // Track share event
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'share', {
                        method: platform,
                        content_type: 'article',
                        item_id: '<?= $artikel['slug'] ?>'
                    });
                }
            });
        });

        // Estimated reading time
        function calculateReadingTime() {
            const text = document.querySelector('.article-body').textContent;
            const wordsPerMinute = 200;
            const words = text.trim().split(/\s+/).length;
            const readingTime = Math.ceil(words / wordsPerMinute);
            
            // Add reading time to meta
            const readingTimeElement = document.createElement('div');
            readingTimeElement.className = 'meta-item';
            readingTimeElement.innerHTML = `<i class="fas fa-book-reader"></i><span>${readingTime} menit baca</span>`;
            document.querySelector('.article-meta').appendChild(readingTimeElement);
        }

        // Calculate reading time on load
        document.addEventListener('DOMContentLoaded', calculateReadingTime);

        document.addEventListener('DOMContentLoaded', function() {
        // Ping sitemap via AJAX agar tidak mempengaruhi loading halaman
        fetch('ping-sitemap.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'ping_sitemap',
                page: 'artikel-detail',
                slug: '<?= $artikel['slug'] ?>'
            })
        }).catch(function(error) {
            console.log('Sitemap ping failed:', error);
        });
    });
</script>

    <!-- Structured Data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "<?= htmlspecialchars($artikel['judul']) ?>",
        "description": "<?= htmlspecialchars($meta_description) ?>",
        "image": "<?= $meta_image ?>",
        "author": {
            "@type": "Person",
            "name": "<?= htmlspecialchars($artikel['penulis']) ?>"
        },
        "publisher": {
            "@type": "Organization",
            "name": "UKM Madani",
            "logo": {
                "@type": "ImageObject",
                "url": "<?= 'https://' . $_SERVER['HTTP_HOST'] . '/assets/images/logo-madani.png' ?>"
            }
        },
        "datePublished": "<?= date('c', strtotime($artikel['tanggal_publish'] ?? $artikel['created_at'])) ?>",
        "dateModified": "<?= date('c', strtotime($artikel['updated_at'])) ?>",
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "<?= $canonical_url ?>"
        },
        "articleSection": "<?= htmlspecialchars($artikel['kategori']) ?>",
        "keywords": "<?= htmlspecialchars($artikel['tags'] ?? '') ?>",
        "wordCount": "<?= str_word_count(strip_tags($artikel['konten'])) ?>",
        "url": "<?= $canonical_url ?>"
    }
    </script>
</body>
</html>