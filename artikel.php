<?php
// artikel.php
try {
    require_once 'config/database.php';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
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

// Function untuk generate gambar placeholder
function getImagePath($filename, $media_id = null, $title = '') {
    // Jika ada media_id, cek di tabel media_files
    if (!empty($media_id)) {
        try {
            global $conn;
            $stmt = $conn->prepare("SELECT file_path FROM media_files WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $media_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $media = $result->fetch_assoc();
                if (file_exists($media['file_path'])) {
                    return $media['file_path'];
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching media: " . $e->getMessage());
        }
    }
    
    // Fallback ke filename biasa
    if (!empty($filename)) {
        $path = "assets/uploads/artikel/" . $filename;
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return generatePlaceholder($title);
}

function generatePlaceholder($title = '') {
    $initial = strtoupper(substr(trim($title), 0, 1)) ?: '📄';
    
    return "data:image/svg+xml;base64," . base64_encode('
    <svg width="400" height="250" xmlns="http://www.w3.org/2000/svg">
        <rect width="400" height="250" fill="#d4af37"/>
        <text x="200" y="125" text-anchor="middle" dominant-baseline="middle" 
              fill="#ffffff" font-family="Arial" font-size="60" font-weight="bold">
              ' . htmlspecialchars($initial) . '
        </text>
        <text x="200" y="170" text-anchor="middle" fill="#ffffff" 
              font-family="Arial" font-size="14" opacity="0.8">
              ARTIKEL
        </text>
    </svg>');
}

// Pagination settings
$items_per_page = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Search and filter functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';

$search_condition = '';
$search_params = [];

// Build search conditions
$conditions = ["status = 'published'"];
if (!empty($search)) {
    $conditions[] = "(judul LIKE ? OR konten LIKE ? OR tags LIKE ? OR penulis LIKE ?)";
    $search_params = array_merge($search_params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if (!empty($kategori)) {
    $conditions[] = "kategori = ?";
    $search_params[] = $kategori;
}

$search_condition = implode(' AND ', $conditions);

// Get categories for filter
$categories = [];
try {
    $cat_result = $conn->query("SELECT DISTINCT kategori FROM artikel WHERE status = 'published' AND kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat['kategori'];
    }
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// Get total count for pagination
try {
    $count_query = "SELECT COUNT(*) as total FROM artikel WHERE $search_condition";
    $count_stmt = $conn->prepare($count_query);
    if (!empty($search_params)) {
        $types = str_repeat('s', count($search_params));
        $count_stmt->bind_param($types, ...$search_params);
    }
    $count_stmt->execute();
    $total_items = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_items / $items_per_page);
} catch (Exception $e) {
    $total_items = 0;
    $total_pages = 0;
}

// Get artikel data
$artikel_data = [];
try {
    $artikel_query = "SELECT * FROM artikel 
                      WHERE $search_condition
                      ORDER BY 
                          CASE 
                              WHEN featured = 1 THEN 0 
                              ELSE 1 
                          END,
                          tanggal_publish DESC,
                          created_at DESC 
                      LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($artikel_query);
    $params = array_merge($search_params, [$items_per_page, $offset]);
    $types = str_repeat('s', count($search_params)) . 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $artikel_data[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching artikel: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link href="assets/css/mobile-nav.css" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artikel Pilihan - UKM Madani</title>
    
<!-- Favicon - Logo Madani di Tab -->
    <link rel="icon" type="image/x-icon" href="assets/images/logo-madani.png">
    <link rel="shortcut icon" href="assets/images/logo-madani.png">
    <link rel="apple-touch-icon" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/logo-madani.png">
    
    <!-- Meta tags untuk SEO -->
    <meta name="description" content="Kumpulan artikel inspiratif seputar Islam, pendidikan, dan kehidupan dari UKM Madani ITERA. Tulisan-tulisan yang menginspirasi untuk membangun generasi muslim yang berakhlak mulia.">
    <meta name="keywords" content="artikel islam, tulisan inspiratif, pendidikan islam, UKM Madani, ITERA, dakwah, motivasi islam, kehidupan islami">
    <meta name="author" content="UKM Madani ITERA">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Artikel Pilihan - UKM Madani ITERA">
    <meta property="og:description" content="Kumpulan artikel inspiratif seputar Islam, pendidikan, dan kehidupan yang menginspirasi">
    <meta property="og:image" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/assets/images/logo-madani.png">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="UKM Madani ITERA">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Artikel Pilihan - UKM Madani ITERA">
    <meta name="twitter:description" content="Kumpulan artikel inspiratif seputar Islam, pendidikan, dan kehidupan">
    <meta name="twitter:image" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/assets/images/logo-madani.png">
    
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
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
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
            align-items: center !important;
        }

        .nav-brand {
            display: flex;
            align-items: center !important; /* Pastikan center */
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
            align-items: center !important; /* Pastikan center */
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

        /* Hero Section */
        .hero {
            background: var(--gradient);
            color: white;
            padding: 80px 0 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
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

        /* Filter Section */
        .filter-section {
            padding: 40px 0;
            background: var(--bg-secondary);
        }

        .filter-container {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-container {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-box {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid var(--border-color);
            border-radius: 50px;
            font-size: 1rem;
            font-family: inherit;
            background: white;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

        .category-filter {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .category-select {
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 25px;
            background: white;
            font-family: inherit;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .category-select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .filter-btn {
            padding: 12px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 25px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        /* Articles Grid */
        .articles-section {
            padding: 60px 0;
        }

        .articles-stats {
            margin-bottom: 30px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(350px, 100%), 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .article-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.4s ease;
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .article-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .article-image {
            width: 100%;
            height: 250px;
            position: relative;
            overflow: hidden;
        }

        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .article-card:hover .article-image img {
            transform: scale(1.05);
        }

        .article-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--secondary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            z-index: 2;
        }

        .featured-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(212, 175, 55, 0.9);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
            z-index: 2;
        }

        .article-content {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .article-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            flex-wrap: wrap;
        }

        .article-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
            background: var(--bg-secondary);
            padding: 4px 8px;
            border-radius: 12px;
        }

        .category-tag {
            background: var(--primary-color) !important;
            color: white !important;
            padding: 4px 10px !important;
            border-radius: 15px !important;
            font-size: 0.8rem !important;
        }

        .article-title {
            margin-bottom: 15px;
            flex-grow: 0;
        }

        .article-title a {
            text-decoration: none;
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.4;
            transition: color 0.3s ease;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .article-title a:hover {
            color: var(--primary-color);
        }

        .article-excerpt {
            color: var(--text-secondary);
            margin-bottom: 20px;
            line-height: 1.6;
            flex-grow: 1;
            font-size: 0.95rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
            font-size: 0.9rem;
        }

        .read-more:hover {
            color: var(--secondary-color);
            transform: translateX(5px);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 40px;
        }

        .pagination a,
        .pagination span {
            padding: 12px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination a {
            background: white;
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }

        .pagination a:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination .current {
            background: var(--primary-color);
            color: white;
            border: 2px solid var(--primary-color);
        }

        .pagination .disabled {
            background: var(--bg-secondary);
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-secondary);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
      
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }

            .hero {
                padding: 60px 0 40px;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .filter-section {
                padding: 25px 0;
            }

            .filter-container {
                flex-direction: column;
                gap: 15px;
            }

            .search-container {
                min-width: 100%;
            }

            .category-filter {
                width: 100%;
                justify-content: stretch;
                gap: 10px;
            }

            .category-select {
                flex: 1;
                min-width: 0;
            }

            .filter-btn {
                flex-shrink: 0;
                padding: 12px 20px;
            }

            .articles-section {
                padding: 40px 0;
            }

            .articles-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .article-image {
                height: 200px;
            }

            .article-content {
                padding: 20px;
            }

            .article-meta {
                gap: 8px;
                font-size: 0.8rem;
            }

            .article-meta span {
                padding: 3px 6px;
                font-size: 0.75rem;
            }

            .article-title a {
                font-size: 1.1rem;
            }

            .article-excerpt {
                font-size: 0.9rem;
                -webkit-line-clamp: 2;
                line-clamp: 2;
            }

            .pagination {
                flex-wrap: wrap;
                gap: 8px;
            }

            .pagination a,
            .pagination span {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }

            .navbar {
                padding: 10px 0;
            }

            .nav-brand {
                font-size: 1.3rem;
            }

            .nav-brand img {
                width: 30px;
                height: 30px;
            }


            .hero {
                padding: 50px 0 30px;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .filter-section {
                padding: 20px 0;
            }

            .search-box {
                padding: 12px 40px 12px 15px;
                font-size: 0.9rem;
            }

            .search-icon {
                right: 15px;
                font-size: 1rem;
            }

            .category-select,
            .filter-btn {
                padding: 10px 12px;
                font-size: 0.85rem;
            }

            .articles-section {
                padding: 30px 0;
            }

            .article-image {
                height: 180px;
            }

            .article-content {
                padding: 15px;
            }

            .article-meta {
                flex-wrap: wrap;
                gap: 6px;
            }

            .article-meta span {
                padding: 2px 5px;
                font-size: 0.7rem;
                border-radius: 8px;
            }

            .category-tag {
                padding: 3px 6px !important;
                font-size: 0.7rem !important;
            }

            .article-title a {
                font-size: 1rem;
                line-height: 1.3;
            }

            .article-excerpt {
                font-size: 0.85rem;
                line-height: 1.5;
                margin-bottom: 15px;
            }

            .read-more {
                font-size: 0.85rem;
            }

            .pagination a,
            .pagination span {
                padding: 8px 10px;
                font-size: 0.8rem;
                min-width: 40px;
            }

            .empty-state {
                padding: 60px 15px;
            }

            .empty-icon {
                font-size: 3rem;
            }

            .empty-state h3 {
                font-size: 1.2rem;
            }

            .empty-state p {
                font-size: 0.9rem;
            }

        }

        @media (max-width: 360px) {
            .hero-title {
                font-size: 1.8rem;
            }

            .hero-subtitle {
                font-size: 0.9rem;
            }

            .article-image {
                height: 160px;
            }

            .article-content {
                padding: 12px;
            }

            .article-title a {
                font-size: 0.95rem;
            }

            .article-excerpt {
                font-size: 0.8rem;
            }

            .pagination a,
            .pagination span {
                padding: 6px 8px;
                font-size: 0.75rem;
            }

            .search-box {
                padding: 10px 35px 10px 12px;
                font-size: 0.85rem;
            }

            .nav-links.mobile .nav-link {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
        }

        /* LANDSCAPE ORIENTATION */
        @media (max-width: 768px) and (orientation: landscape) {
            .hero {
                padding: 40px 0 30px;
            }

            .hero-title {
                font-size: 2rem;
            }

            .articles-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .article-image {
                height: 150px;
            }
        }

        /* ULTRA WIDE SCREENS */
        @media (min-width: 1400px) {
            .container {
                max-width: 1400px;
            }

            .articles-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* HIGH DENSITY DISPLAYS */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .article-image img {
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
        }

        /* REDUCED MOTION */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* HOVER IMPROVEMENTS FOR TOUCH DEVICES */
        @media (hover: none) and (pointer: coarse) {
            .article-card:hover {
                transform: none;
            }

            .article-card:active {
                transform: scale(0.98);
                transition: transform 0.1s ease;
            }

            .nav-link:hover {
                transform: none;
                background: rgba(26, 95, 63, 0.1);
            }

            .filter-btn:hover,
            .back-link:hover {
                transform: none;
            }
        }

        /* FOCUS STYLES FOR ACCESSIBILITY */
        .nav-link:focus,
        .search-box:focus,
        .category-select:focus,
        .filter-btn:focus,
        .mobile-toggle:focus {
            outline: 2px solid var(--secondary-color);
            outline-offset: 2px;
        }

        /* IMPROVED LOADING STATES */
        .article-card.loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .article-card.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 30px;
            height: 30px;
            margin: -15px 0 0 -15px;
            border: 3px solid var(--border-color);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ENHANCED SCROLL BEHAVIOR */
        html {
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }

        /* SAFE AREA INSETS FOR NOTCHED DEVICES */
        @supports (padding-top: env(safe-area-inset-top)) {
            .header {
                padding-top: env(safe-area-inset-top);
            }
        }

        /* PRINT STYLES */
        @media print {
            .header,
            .filter-section,
            .pagination,
            .mobile-toggle,
            .nav-overlay {
                display: none !important;
            }

            .hero {
                padding: 20px 0;
                background: none !important;
            }

            .articles-grid {
                grid-template-columns: 1fr !important;
                gap: 20px;
            }

            .article-card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .article-image {
                height: auto;
                max-height: 200px;
            }

            body {
                font-size: 12pt;
                line-height: 1.4;
            }
        }
    </style>
</head>
<body>
  <!-- Header dengan Mobile Support -->
    <header class="header">
        <!-- Navigation Overlay untuk Mobile -->
        <div class="nav-overlay" id="navOverlay"></div>
        
        <nav class="navbar">
            <div class="container">
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
                    <li><a href="artikel.php" class="nav-link" style="color: var(--primary-color);">Artikel</a></li>
                    <li><a href="galeri.php" class="nav-link">Galeri</a></li>
                    <li><a href="index.php#contact" class="nav-link back-link">
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
                    <li><a href="artikel.php" class="nav-link" style="color: var(--primary-color); background: rgba(26, 95, 63, 0.1);">
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
                
                <!-- Mobile Toggle Button -->
                <button class="mobile-toggle" id="mobileToggle" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Artikel Pilihan</h1>
                <p class="hero-subtitle">
                    Tulisan-tulisan inspiratif seputar Islam, pendidikan, dan kehidupan yang menginspirasi
                </p>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="container">
            <form method="GET" class="filter-container">
                <div class="search-container">
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Cari artikel berdasarkan judul, konten, atau penulis..." 
                           class="search-box">
                    <i class="fas fa-search search-icon"></i>
                </div>
                
                <div class="category-filter">
                    <select name="kategori" class="category-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $kategori === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Articles Section -->
    <section class="articles-section">
        <div class="container">
            <div class="articles-stats">
                Menampilkan <?= count($artikel_data) ?> dari <?= $total_items ?> artikel
                <?php if (!empty($search)): ?>
                    untuk pencarian "<strong><?= htmlspecialchars($search) ?></strong>"
                <?php endif; ?>
                <?php if (!empty($kategori)): ?>
                    dalam kategori "<strong><?= htmlspecialchars($kategori) ?></strong>"
                <?php endif; ?>
            </div>

            <?php if (count($artikel_data) > 0): ?>
                <div class="articles-grid">
                    <?php foreach ($artikel_data as $artikel): ?>
                    <article class="article-card" onclick="location.href='artikel-detail.php?slug=<?= $artikel['slug'] ?>'">
                        <div class="article-image">
                            <img src="<?= getImagePath($artikel['gambar'], $artikel['media_id'], $artikel['judul']) ?>" 
                                 alt="<?= htmlspecialchars($artikel['judul']) ?>"
                                 loading="lazy">
                            <div class="article-badge">Artikel</div>
                            <?php if ($artikel['featured']): ?>
                                <div class="featured-badge">
                                    <i class="fas fa-star"></i> Featured
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="article-content">
                            <div class="article-meta">
                                <span>
                                    <i class="fas fa-calendar"></i>
                                    <?= formatTanggalIndonesia($artikel['tanggal_publish']) ?>
                                </span>
                                <span>
                                    <i class="fas fa-user"></i>
                                    <?= htmlspecialchars($artikel['penulis']) ?>
                                </span>
                                <?php if (!empty($artikel['kategori'])): ?>
                                    <span class="category-tag">
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
                            <h3 class="article-title">
                                <a href="artikel-detail.php?slug=<?= $artikel['slug'] ?>">
                                    <?= htmlspecialchars($artikel['judul']) ?>
                                </a>
                            </h3>
                            <p class="article-excerpt">
                                <?php 
                                $excerpt = !empty($artikel['excerpt']) ? $artikel['excerpt'] : substr(strip_tags($artikel['konten']), 0, 150) . '...';
                                echo htmlspecialchars($excerpt);
                                ?>
                            </p>
                            <a href="artikel-detail.php?slug=<?= $artikel['slug'] ?>" class="read-more">
                                <i class="fas fa-book-open"></i> Baca Artikel
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($kategori) ? '&kategori=' . urlencode($kategori) : '' ?>">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                    <?php else: ?>
                        <span class="disabled">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </span>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($kategori) ? '&kategori=' . urlencode($kategori) : '' ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($kategori) ? '&kategori=' . urlencode($kategori) : '' ?>">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="disabled">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3><?= !empty($search) || !empty($kategori) ? 'Artikel Tidak Ditemukan' : 'Belum Ada Artikel' ?></h3>
                    <p>
                        <?php if (!empty($search) || !empty($kategori)): ?>
                            Coba kata kunci lain atau <a href="artikel.php" style="color: var(--primary-color);">lihat semua artikel</a>
                        <?php else: ?>
                            Artikel inspiratif akan segera hadir. Nantikan tulisan terbaru dari kami!
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

 <!-- Mobile Navigation JavaScript -->
    <script src="assets/js/mobile-nav.js"></script>
    
    <script>
        // Auto-submit form on Enter key
        document.querySelector('.search-box').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });

        // Auto-submit form on category change
        document.querySelector('.category-select').addEventListener('change', function() {
            this.closest('form').submit();
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

        // Add loading state to cards
        document.querySelectorAll('.article-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.tagName !== 'A') {
                    this.style.opacity = '0.7';
                    this.style.transform = 'scale(0.98)';
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
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Animate cards on scroll
        document.querySelectorAll('.article-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease-out';
            card.style.transitionDelay = `${index * 0.1}s`;
            observer.observe(card);
        });

        // Clear filters functionality
        function clearFilters() {
            window.location.href = 'artikel.php';
        }

        // Add clear filters button if there are active filters
        <?php if (!empty($search) || !empty($kategori)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const filterContainer = document.querySelector('.filter-container');
            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'filter-btn';
            clearBtn.style.background = '#dc3545';
            clearBtn.innerHTML = '<i class="fas fa-times"></i> Reset';
            clearBtn.onclick = clearFilters;
            filterContainer.appendChild(clearBtn);
        });
        <?php endif; ?>

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + K to focus search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                document.querySelector('.search-box').focus();
            }
        });

        // Add search placeholder animation
        const searchBox = document.querySelector('.search-box');
        const placeholders = [
            'Cari artikel berdasarkan judul...',
            'Cari berdasarkan penulis...',
            'Cari berdasarkan konten...',
            'Cari artikel inspiratif...'
        ];
        
        let placeholderIndex = 0;
        setInterval(() => {
            if (!searchBox.value && document.activeElement !== searchBox) {
                placeholderIndex = (placeholderIndex + 1) % placeholders.length;
                searchBox.placeholder = placeholders[placeholderIndex];
            }
        }, 3000);

        // Back to top functionality
        let backToTopBtn;
        function createBackToTop() {
            backToTopBtn = document.createElement('button');
            backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            backToTopBtn.className = 'back-to-top';
            backToTopBtn.style.cssText = `
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
                font-size: 1.2rem;
                box-shadow: var(--shadow);
                transition: all 0.3s ease;
                opacity: 0;
                visibility: hidden;
                z-index: 999;
            `;
            
            backToTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            
            document.body.appendChild(backToTopBtn);
        }

        // Show/hide back to top button
        window.addEventListener('scroll', () => {
            if (!backToTopBtn) createBackToTop();
            
            if (window.scrollY > 300) {
                backToTopBtn.style.opacity = '1';
                backToTopBtn.style.visibility = 'visible';
            } else {
                backToTopBtn.style.opacity = '0';
                backToTopBtn.style.visibility = 'hidden';
            }
        });

        // Error handling for images
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('error', function() {
                this.src = 'data:image/svg+xml;base64,' + btoa(`
                    <svg width="400" height="250" xmlns="http://www.w3.org/2000/svg">
                        <rect width="400" height="250" fill="#d4af37"/>
                        <text x="200" y="125" text-anchor="middle" dominant-baseline="middle" 
                              fill="#ffffff" font-family="Arial" font-size="60" font-weight="bold">
                              📄
                        </text>
                        <text x="200" y="170" text-anchor="middle" fill="#ffffff" 
                              font-family="Arial" font-size="14" opacity="0.8">
                              ARTIKEL
                        </text>
                    </svg>
                `);
            });
        });

        console.log('Artikel page loaded successfully! 📄✨');
    </script>
</body>
</html>