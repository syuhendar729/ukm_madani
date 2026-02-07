<?php
// galeri.php
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

// Function untuk generate placeholder gambar
function getImagePath($filename, $title = '') {
    if (empty($filename)) {
        return generatePlaceholder($title);
    }
    
    $path = "assets/uploads/galeri/" . $filename;
    if (file_exists($path)) {
        return $path;
    }
    
    return generatePlaceholder($title);
}

function generatePlaceholder($title = '') {
    $colors = ['#1a5f3f', '#d4af37', '#2196F3', '#4CAF50', '#FF9800', '#9C27B0', '#E91E63'];
    $bg_color = $colors[array_rand($colors)];
    $initial = strtoupper(substr(trim($title), 0, 1)) ?: '📸';
    
    return "data:image/svg+xml;base64," . base64_encode('
    <svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:' . $bg_color . ';stop-opacity:1" />
                <stop offset="100%" style="stop-color:' . $bg_color . '80;stop-opacity:1" />
            </linearGradient>
        </defs>
        <rect width="400" height="300" fill="url(#grad1)"/>
        <text x="200" y="150" text-anchor="middle" dominant-baseline="middle" 
              fill="#ffffff" font-family="Arial" font-size="60" font-weight="bold">
              ' . htmlspecialchars($initial) . '
        </text>
        <text x="200" y="200" text-anchor="middle" fill="#ffffff" 
              font-family="Arial" font-size="14" opacity="0.9">
              DOKUMENTASI
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

// Build search conditions
$conditions = ["status = 'published'"];
$search_params = [];

if (!empty($search)) {
    $conditions[] = "(judul LIKE ? OR deskripsi LIKE ? OR tags LIKE ? OR lokasi LIKE ?)";
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
    $cat_result = $conn->query("SELECT DISTINCT kategori FROM galeri WHERE status = 'published' AND kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat['kategori'];
    }
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// Get total count for pagination
try {
    $count_query = "SELECT COUNT(*) as total FROM galeri WHERE $search_condition";
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

// Get galeri data
$galeri_data = [];
try {
    $galeri_query = "SELECT * FROM galeri 
                     WHERE $search_condition
                     ORDER BY tanggal_kegiatan DESC, created_at DESC 
                     LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($galeri_query);
    $params = array_merge($search_params, [$items_per_page, $offset]);
    $types = str_repeat('s', count($search_params)) . 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $galeri_data[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching galeri: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link href="assets/css/mobile-nav.css" rel="stylesheet">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi Kegiatan - UKM Madani</title>
    
 <!-- Favicon - Logo Madani di Tab -->
    <link rel="icon" type="image/x-icon" href="assets/images/logo-madani.png">
    <link rel="shortcut icon" href="assets/images/logo-madani.png">
    <link rel="apple-touch-icon" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/logo-madani.png">
    
    <!-- Meta tags untuk SEO -->
    <meta name="description" content="Galeri foto dan dokumentasi kegiatan UKM Madani ITERA. Lihat momen-momen berharga dari program dakwah, seminar, dan kegiatan sosial kami.">
    <meta name="keywords" content="galeri UKM Madani, foto kegiatan, dokumentasi dakwah, galeri ITERA, foto seminar, kegiatan sosial, LDK foto">
    <meta name="author" content="UKM Madani ITERA">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Galeri Kegiatan - UKM Madani ITERA">
    <meta property="og:description" content="Galeri foto dan dokumentasi kegiatan UKM Madani ITERA">
    <meta property="og:image" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/assets/images/logo-madani.png">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="UKM Madani ITERA">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Galeri Kegiatan - UKM Madani ITERA">
    <meta name="twitter:description" content="Galeri foto dan dokumentasi kegiatan UKM Madani ITERA">
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

        /* Search and Filter Section */
        .filter-section {
            padding: 40px 0;
            background: var(--bg-secondary);
        }

        .filter-container {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }

        .search-container {
            position: relative;
            flex: 1;
            min-width: 300px;
            max-width: 500px;
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
            cursor: pointer;
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

        /* Gallery Grid */
        .gallery-section {
            padding: 60px 0;
        }

        .section-stats {
            text-align: center;
            margin-bottom: 40px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(350px, 100%), 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .gallery-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
        }

        .gallery-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .gallery-image {
            width: 100%;
            height: 250px;
            position: relative;
            overflow: hidden;
        }

        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-card:hover .gallery-image img {
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

        .gallery-card:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-overlay i {
            font-size: 3rem;
            margin-bottom: 10px;
            animation: pulse 2s ease-in-out infinite;
        }

        .gallery-overlay-text {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .category-badge {
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

        .photo-count {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .gallery-content {
            padding: 25px;
        }

        .gallery-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .gallery-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .gallery-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .gallery-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .gallery-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            padding: 10px 20px;
            border: 2px solid var(--primary-color);
            border-radius: 25px;
            background: transparent;
        }

        .gallery-button:hover {
            background: var(--primary-color);
            color: white;
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

            .hero {
                padding: 80px 0 60px;
            }

            .filter-section {
                padding: 30px 0;
            }

            .filter-container {
                flex-direction: column;
                gap: 15px;
            }

            .search-container {
                min-width: 100%;
            }

            .gallery-section {
                padding: 40px 0;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .gallery-image {
                height: 200px;
            }

            .gallery-content {
                padding: 20px;
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
                font-size: 1.3rem !important; /* Ini yang kurang! */
            }

            .nav-brand img {
                width: 30px !important;
                height: 30px !important;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1rem;
            }

            .gallery-content {
                padding: 15px;
            }

            .gallery-title {
                font-size: 1.1rem;
            }

            .gallery-meta {
                font-size: 0.8rem;
                gap: 10px;
            }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    </style>
</head>
<body>
    <!-- Navigation Overlay untuk Mobile -->
    <div class="nav-overlay" id="navOverlay"></div>

    <!-- Header -->
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
                    <li><a href="galeri.php" class="nav-link" style="color: var(--primary-color);">Galeri</a></li>
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
                    <li><a href="artikel.php" class="nav-link">
                        <i class="fas fa-pen-fancy"></i> Artikel
                    </a></li>
                    <li><a href="galeri.php" class="nav-link" style="color: var(--primary-color); background: rgba(26, 95, 63, 0.1);">
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Dokumentasi Kegiatan</h1>
                <p class="hero-subtitle">
                    Kumpulan dokumentasi dari berbagai kegiatan UKM Madani
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
                           placeholder="Cari dokumentasi..." 
                           class="search-box">
                    <i class="fas fa-search search-icon"></i>
                </div>
                
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
            </form>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="gallery-section">
        <div class="container">
            <div class="section-stats">
                Menampilkan <?= count($galeri_data) ?> dari <?= $total_items ?> dokumentasi
                <?php if (!empty($search)): ?>
                    untuk pencarian "<strong><?= htmlspecialchars($search) ?></strong>"
                <?php endif; ?>
                <?php if (!empty($kategori)): ?>
                    dalam kategori "<strong><?= htmlspecialchars($kategori) ?></strong>"
                <?php endif; ?>
            </div>

            <?php if (count($galeri_data) > 0): ?>
                <div class="gallery-grid">
                    <?php foreach ($galeri_data as $galeri): ?>
                    <div class="gallery-card" onclick="openGallery('<?= htmlspecialchars($galeri['google_drive_link']) ?>')">
                        <div class="gallery-image">
                            <img src="<?= getImagePath($galeri['cover_image'], $galeri['judul']) ?>" 
                                 alt="<?= htmlspecialchars($galeri['judul']) ?>"
                                 loading="lazy">
                            <div class="gallery-overlay">
                                <i class="fas fa-external-link-alt"></i>
                                <div class="gallery-overlay-text">Lihat Dokumentasi</div>
                            </div>
                            
                            <?php if (!empty($galeri['kategori'])): ?>
                                <div class="category-badge"><?= htmlspecialchars($galeri['kategori']) ?></div>
                            <?php endif; ?>
                            
                            <?php if ($galeri['total_foto'] > 0): ?>
                                <div class="photo-count">
                                    <i class="fas fa-images"></i>
                                    <?= $galeri['total_foto'] ?> foto
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="gallery-content">
                            <h3 class="gallery-title"><?= htmlspecialchars($galeri['judul']) ?></h3>
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
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $base_url = "galeri.php?search=" . urlencode($search) . "&kategori=" . urlencode($kategori);
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="<?= $base_url ?>&page=<?= $page - 1 ?>">
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
                            <a href="<?= $base_url ?>&page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?= $base_url ?>&page=<?= $page + 1 ?>">
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
                    <div class="empty-icon">📸</div>
                    <h3><?= !empty($search) || !empty($kategori) ? 'Dokumentasi Tidak Ditemukan' : 'Belum Ada Dokumentasi' ?></h3>
                    <p>
                        <?php if (!empty($search) || !empty($kategori)): ?>
                            Coba kata kunci lain atau <a href="galeri.php" style="color: var(--primary-color);">lihat semua dokumentasi</a>
                        <?php else: ?>
                            Dokumentasi kegiatan akan segera hadir. Pantau terus update dari kami!
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

  <!-- GANTI SEMUA <script> dengan ini -->

<!-- Mobile Navigation JavaScript -->
<script src="assets/js/mobile-nav.js"></script>

<script>
    // Gallery functions
    function openGallery(driveLink) {
        if (driveLink && driveLink !== '#' && driveLink !== '') {
            window.open(driveLink, '_blank');
        }
    }

    // Search form auto-submit on enter
    document.querySelector('.search-box').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.closest('form').submit();
        }
    });

    // Auto-submit category filter
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

    // Image error handling
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
            const title = this.alt || 'Dokumentasi';
            this.src = generatePlaceholderURL(title);
        });
    });

    function generatePlaceholderURL(title) {
        const colors = ['#1a5f3f', '#d4af37', '#2196F3', '#4CAF50', '#FF9800', '#9C27B0', '#E91E63'];
        const bg_color = colors[Math.floor(Math.random() * colors.length)];
        const initial = title.charAt(0).toUpperCase() || '📸';
        
        const svg = `
        <svg width="400" height="300" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" style="stop-color:${bg_color};stop-opacity:1" />
                    <stop offset="100%" style="stop-color:${bg_color}80;stop-opacity:1" />
                </linearGradient>
            </defs>
            <rect width="400" height="300" fill="url(#grad1)"/>
            <text x="200" y="150" text-anchor="middle" dominant-baseline="middle" 
                  fill="#ffffff" font-family="Arial" font-size="60" font-weight="bold">
                  ${initial}
            </text>
            <text x="200" y="200" text-anchor="middle" fill="#ffffff" 
                  font-family="Arial" font-size="14" opacity="0.9">
                  DOKUMENTASI
            </text>
        </svg>`;
        
        return "data:image/svg+xml;base64," + btoa(svg);
    }

    console.log('Gallery page loaded successfully! 📸✨');
</script>
</body>
</html>