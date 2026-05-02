<?php
// berita.php
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
        $path = "assets/uploads/berita/" . $filename;
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return generatePlaceholder($title);
}

function generatePlaceholder($title = '') {
    $initial = strtoupper(substr(trim($title), 0, 1)) ?: '📰';
    
    return "data:image/svg+xml;base64," . base64_encode('
    <svg width="400" height="250" xmlns="http://www.w3.org/2000/svg">
        <rect width="400" height="250" fill="#1a5f3f"/>
        <text x="200" y="125" text-anchor="middle" dominant-baseline="middle" 
              fill="#ffffff" font-family="Arial" font-size="60" font-weight="bold">
              ' . htmlspecialchars($initial) . '
        </text>
        <text x="200" y="170" text-anchor="middle" fill="#ffffff" 
              font-family="Arial" font-size="14" opacity="0.8">
              BERITA
        </text>
    </svg>');
}

// Pagination settings
$items_per_page = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Search and filter functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$bulan = isset($_GET['bulan']) ? trim($_GET['bulan']) : '';
$tahun = isset($_GET['tahun']) ? trim($_GET['tahun']) : '';

$search_condition = '';
$search_params = [];

// Build search conditions
$conditions = ["status = 'published'"];
if (!empty($search)) {
    $conditions[] = "(judul LIKE ? OR konten LIKE ? OR penulis LIKE ?)";
    $search_params = array_merge($search_params, ["%$search%", "%$search%", "%$search%"]);
}
if (!empty($bulan)) {
    $conditions[] = "MONTH(tanggal_publish) = ?";
    $search_params[] = $bulan;
}
if (!empty($tahun)) {
    $conditions[] = "YEAR(tanggal_publish) = ?";
    $search_params[] = $tahun;
}

$search_condition = implode(' AND ', $conditions);

// Get available years for filter
$years = [];
try {
    $year_result = $conn->query("SELECT DISTINCT YEAR(tanggal_publish) as tahun FROM berita WHERE status = 'published' AND tanggal_publish IS NOT NULL ORDER BY tahun DESC");
    while ($year = $year_result->fetch_assoc()) {
        $years[] = $year['tahun'];
    }
} catch (Exception $e) {
    error_log("Error fetching years: " . $e->getMessage());
}

// Get total count for pagination
try {
    $count_query = "SELECT COUNT(*) as total FROM berita WHERE $search_condition";
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

// Get berita data
$berita_data = [];
try {
    $berita_query = "SELECT * FROM berita 
                     WHERE $search_condition
                     ORDER BY tanggal_publish DESC, created_at DESC 
                     LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($berita_query);
    $params = array_merge($search_params, [$items_per_page, $offset]);
    $types = str_repeat('s', count($search_params)) . 'ii';
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $berita_data[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching berita: " . $e->getMessage());
}

// Array bulan untuk dropdown
$bulan_list = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link href="assets/css/mobile-nav.css" rel="stylesheet">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Terbaru - UKM Madani</title>
    
 <!-- Favicon - Logo Madani di Tab -->
    <link rel="icon" type="image/x-icon" href="assets/images/logo-madani.png">
    <link rel="shortcut icon" href="assets/images/logo-madani.png">
    <link rel="apple-touch-icon" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/logo-madani.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/logo-madani.png">
    
    <!-- Meta tags untuk SEO -->
    <meta name="description" content="Berita terbaru dan informasi terkini seputar kegiatan UKM Madani ITERA. Update program dakwah, kegiatan sosial, dan pengembangan mahasiswa muslim.">
    <meta name="keywords" content="berita UKM Madani, kegiatan dakwah, berita ITERA, program islam, kegiatan mahasiswa, LDK, berita kampus">
    <meta name="author" content="UKM Madani ITERA">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Berita Terbaru - UKM Madani ITERA">
    <meta property="og:description" content="Berita terbaru dan informasi terkini seputar kegiatan UKM Madani ITERA">
    <meta property="og:image" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/assets/images/logo-madani.png">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="UKM Madani ITERA">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Berita Terbaru - UKM Madani ITERA">
    <meta name="twitter:description" content="Berita terbaru dan informasi terkini seputar kegiatan UKM Madani ITERA">
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

        .date-filter {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .date-select {
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 25px;
            background: white;
            font-family: inherit;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }

        .date-select:focus {
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

        /* News Grid */
        .news-section {
            padding: 60px 0;
        }

        .news-stats {
            margin-bottom: 30px;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .news-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(350px, 100%), 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .news-card {
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

        .news-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .news-image {
            width: 100%;
            height: 250px;
            position: relative;
            overflow: hidden;
        }

        .news-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .news-card:hover .news-image img {
            transform: scale(1.05);
        }

        .news-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--primary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            z-index: 2;
        }

        .breaking-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #dc3545;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            animation: pulse 2s ease-in-out infinite;
            z-index: 2;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .news-content {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .news-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            flex-wrap: wrap;
        }

        .news-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
            background: var(--bg-secondary);
            padding: 4px 8px;
            border-radius: 12px;
        }

        .news-title {
            margin-bottom: 15px;
            flex-grow: 0;
        }

        .news-title a {
            text-decoration: none;
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.4;
            transition: color 0.3s ease;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .news-title a:hover {
            color: var(--primary-color);
        }

        .news-excerpt {
            color: var(--text-secondary);
            margin-bottom: 20px;
            line-height: 1.6;
            flex-grow: 1;
            font-size: 0.95rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
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

        /* Trending Section */
        .trending-section {
            background: var(--bg-secondary);
            padding: 50px 0;
            margin-bottom: 40px;
        }

        .trending-title {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .trending-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr));
            gap: 20px;
        }

        .trending-item {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .trending-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .trending-item h4 {
            color: var(--primary-color);
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .trending-item .meta {
            font-size: 0.8rem;
            color: var(--text-secondary);
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

       /* Responsive Design - VERSI BERSIH */
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
                padding: 30px 0;
            }

            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-container {
                min-width: 100%;
            }

            .date-filter {
                justify-content: center;
                flex-wrap: wrap;
            }

            .news-section {
                padding: 40px 0;
            }

            .news-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .trending-grid {
                grid-template-columns: 1fr;
            }

            .pagination {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 480px) {
                .hero-title {
                    font-size: 2rem !important; /* Harus sama persis */
                }

                .hero-subtitle {
                    font-size: 1rem !important; /* Harus sama persis */
                }
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
                .container {
                    padding: 0 15px;
                }

                .news-content {
                    padding: 20px;
                }

                .date-select {
                    min-width: 100px;
                    font-size: 0.8rem;
                }
            }
    </style>
</head>
<body>
    <!-- Header -->
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
                <li><a href="berita.php" class="nav-link" style="color: var(--primary-color);">Berita</a></li>
                <li><a href="artikel.php" class="nav-link">Artikel</a></li>
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
                <li><a href="berita.php" class="nav-link" style="color: var(--primary-color); background: rgba(26, 95, 63, 0.1);">
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
                <h1 class="hero-title">Berita Terbaru</h1>
                <p class="hero-subtitle">
                    Informasi terkini seputar kegiatan dan perkembangan UKM Madani
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
                           placeholder="Cari berita berdasarkan judul, konten, atau penulis..." 
                           class="search-box">
                    <i class="fas fa-search search-icon"></i>
                </div>
                
                <div class="date-filter">
                    <select name="bulan" class="date-select">
                        <option value="">Semua Bulan</option>
                        <?php foreach ($bulan_list as $num => $nama): ?>
                            <option value="<?= $num ?>" <?= $bulan == $num ? 'selected' : '' ?>>
                                <?= $nama ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="tahun" class="date-select">
                        <option value="">Semua Tahun</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?= $year ?>" <?= $tahun == $year ? 'selected' : '' ?>>
                                <?= $year ?>
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

    <!-- News Section -->
    <section class="news-section">
        <div class="container">
            <div class="news-stats">
                Menampilkan <?= count($berita_data) ?> dari <?= $total_items ?> berita
                <?php if (!empty($search)): ?>
                    untuk pencarian "<strong><?= htmlspecialchars($search) ?></strong>"
                <?php endif; ?>
                <?php if (!empty($bulan)): ?>
                    pada bulan <strong><?= $bulan_list[$bulan] ?></strong>
                <?php endif; ?>
                <?php if (!empty($tahun)): ?>
                    tahun <strong><?= $tahun ?></strong>
                <?php endif; ?>
            </div>

            <?php if (count($berita_data) > 0): ?>
                <div class="news-grid">
                    <?php foreach ($berita_data as $berita): ?>
                    <article class="news-card" onclick="location.href='berita-detail.php?slug=<?= $berita['slug'] ?>'">
                        <div class="news-image">
                            <img src="<?= getImagePath($berita['gambar'], $berita['media_id'], $berita['judul']) ?>" 
                                 alt="<?= htmlspecialchars($berita['judul']) ?>"
                                 loading="lazy">
                            <div class="news-badge">Berita</div>
                            <?php 
                            // Check if news is recent (within 24 hours)
                            $published_time = strtotime($berita['tanggal_publish']);
                            $current_time = time();
                            if (($current_time - $published_time) < 86400): // 24 hours
                            ?>
                                <div class="breaking-badge">
                                    <i class="fas fa-bolt"></i> Hot
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="news-content">
                            <div class="news-meta">
                                <span>
                                    <i class="fas fa-calendar"></i>
                                    <?= formatTanggalIndonesia($berita['tanggal_publish']) ?>
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
                            <h3 class="news-title">
                                <a href="berita-detail.php?slug=<?= $berita['slug'] ?>">
                                    <?= htmlspecialchars($berita['judul']) ?>
                                </a>
                            </h3>
                            <p class="news-excerpt">
                                <?php 
                                $excerpt = substr(strip_tags($berita['konten']), 0, 150) . '...';
                                echo htmlspecialchars($excerpt);
                                ?>
                            </p>
                            <a href="berita-detail.php?slug=<?= $berita['slug'] ?>" class="read-more">
                                <i class="fas fa-newspaper"></i> Baca Selengkapnya
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($bulan) ? '&bulan=' . urlencode($bulan) : '' ?><?= !empty($tahun) ? '&tahun=' . urlencode($tahun) : '' ?>">
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
                            <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($bulan) ? '&bulan=' . urlencode($bulan) : '' ?><?= !empty($tahun) ? '&tahun=' . urlencode($tahun) : '' ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($bulan) ? '&bulan=' . urlencode($bulan) : '' ?><?= !empty($tahun) ? '&tahun=' . urlencode($tahun) : '' ?>">
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
                    <div class="empty-icon">📰</div>
                    <h3><?= !empty($search) || !empty($bulan) || !empty($tahun) ? 'Berita Tidak Ditemukan' : 'Belum Ada Berita' ?></h3>
                    <p>
                        <?php if (!empty($search) || !empty($bulan) || !empty($tahun)): ?>
                            Coba kata kunci lain atau <a href="berita.php" style="color: var(--primary-color);">lihat semua berita</a>
                        <?php else: ?>
                            Berita terbaru akan segera hadir. Pantau terus update dari kami!
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="assets/js/mobile-nav.js"></script>

    <script>
        // Auto-submit form on Enter key
        document.querySelector('.search-box').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });

        // Auto-submit form on filter change
        document.querySelectorAll('.date-select').forEach(select => {
            select.addEventListener('change', function() {
                this.closest('form').submit();
            });
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
        document.querySelectorAll('.news-card').forEach(card => {
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
        document.querySelectorAll('.news-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease-out';
            card.style.transitionDelay = `${index * 0.1}s`;
            observer.observe(card);
        });

        // Clear filters functionality
        function clearFilters() {
            window.location.href = 'berita.php';
        }

        // Add clear filters button if there are active filters
        <?php if (!empty($search) || !empty($bulan) || !empty($tahun)): ?>
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
            'Cari berita berdasarkan judul...',
            'Cari berdasarkan penulis...',
            'Cari berdasarkan konten...',
            'Cari berita terbaru...'
        ];
        
        let placeholderIndex = 0;
        setInterval(() => {
            if (!searchBox.value && document.activeElement !== searchBox) {
                placeholderIndex = (placeholderIndex + 1) % placeholders.length;
                searchBox.placeholder = placeholders[placeholderIndex];
            }
        }, 3000);

        // Improved card hover effects
        document.querySelectorAll('.news-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
            });
        });

        // Performance optimization: Lazy load images
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

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Add reading time estimation
        document.querySelectorAll('.news-content').forEach(content => {
            const excerpt = content.querySelector('.news-excerpt');
            if (excerpt) {
                const wordCount = excerpt.textContent.split(' ').length;
                const readingTime = Math.ceil(wordCount / 50); // Estimate based on excerpt
                
                const metaContainer = content.querySelector('.news-meta');
                const timeElement = document.createElement('span');
                timeElement.innerHTML = `<i class="fas fa-clock"></i> ${readingTime} min baca`;
                metaContainer.appendChild(timeElement);
            }
        });

        // Add smooth transitions for all elements
        document.addEventListener('DOMContentLoaded', function() {
            // Stagger animation for cards
            const cards = document.querySelectorAll('.news-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });

            // Focus search box on page load if there's a search parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('search')) {
                document.querySelector('.search-box').focus();
            }
        });

        // Add CSS animation class
        const style = document.createElement('style');
        style.textContent = `
            .fade-in {
                opacity: 0;
                transform: translateY(30px);
                animation: fadeInUp 0.6s ease-out forwards;
            }
            
            @keyframes fadeInUp {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);

        // Enhanced search functionality
        let searchTimeout;
        document.querySelector('.search-box').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Could implement live search here if needed
                console.log('Search for:', this.value);
            }, 500);
        });

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
                        <rect width="400" height="250" fill="#1a5f3f"/>
                        <text x="200" y="125" text-anchor="middle" dominant-baseline="middle" 
                              fill="#ffffff" font-family="Arial" font-size="60" font-weight="bold">
                              📰
                        </text>
                        <text x="200" y="170" text-anchor="middle" fill="#ffffff" 
                              font-family="Arial" font-size="14" opacity="0.8">
                              BERITA
                        </text>
                    </svg>
                `);
            });
        });

        // Progressive enhancement for older browsers
        if (!window.IntersectionObserver) {
            // Fallback for browsers without IntersectionObserver
            document.querySelectorAll('.news-card').forEach(card => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            });
        }

        // Add real-time news indicator
        function updateNewsIndicators() {
            const newsCards = document.querySelectorAll('.news-card');
            newsCards.forEach(card => {
                const badge = card.querySelector('.breaking-badge');
                if (badge) {
                    // Add extra visual emphasis for hot news
                    setInterval(() => {
                        badge.style.transform = badge.style.transform === 'scale(1.1)' ? 'scale(1)' : 'scale(1.1)';
                    }, 1500);
                }
            });
        }

        // Initialize news indicators
        setTimeout(updateNewsIndicators, 1000);

        // Add service worker for offline functionality (optional)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').catch(() => {
                // Service worker registration failed, but continue normally
            });
        }

        // News sharing functionality
        function shareNews(title, url) {
            if (navigator.share) {
                navigator.share({
                    title: title,
                    url: url
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(url).then(() => {
                    // Show notification
                    const notification = document.createElement('div');
                    notification.innerHTML = 'Link berhasil disalin!';
                    notification.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: var(--primary-color);
                        color: white;
                        padding: 10px 20px;
                        border-radius: 10px;
                        z-index: 9999;
                        animation: slideIn 0.3s ease-out;
                    `;
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 3000);
                });
            }
        }

        // Add share buttons to news cards (optional enhancement)
        document.querySelectorAll('.news-card').forEach(card => {
            card.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                const title = this.querySelector('.news-title a').textContent;
                const url = this.querySelector('.news-title a').href;
                shareNews(title, url);
            });
        });
    </script>
</body>
</html>