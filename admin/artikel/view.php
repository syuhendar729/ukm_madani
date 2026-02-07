<?php
session_start();
require_once '../../config/database.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Session timeout check
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
    session_destroy();
    header('Location: ../login.php?message=session_expired');
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();

// Get article ID
$artikel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$artikel_id) {
    $_SESSION['error_message'] = "ID artikel tidak valid.";
    header('Location: index.php');
    exit;
}

// Initialize variables
$artikel = null;
$error_message = '';
$success_message = '';
$admin_name = $_SESSION['admin_nama'] ?? $_SESSION['admin_username'] ?? 'Admin';

// Handle quick actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'toggle_status':
                    $new_status = $_POST['status'] === 'published' ? 'draft' : 'published';
                    $stmt = $conn->prepare("UPDATE artikel SET status = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("si", $new_status, $artikel_id);
                    
                    if ($stmt->execute()) {
                        // Log activity
                        $description = "Mengubah status artikel menjadi: $new_status";
                        $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'update', ?, ?, NOW())");
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                        $log_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $description, $ip_address);
                        $log_stmt->execute();
                        
                        $success_message = "Status artikel berhasil diubah menjadi " . ucfirst($new_status);
                    } else {
                        throw new Exception("Gagal mengubah status artikel.");
                    }
                    break;
                    
                case 'toggle_featured':
                    $new_featured = $_POST['featured'] == '1' ? 0 : 1;
                    $stmt = $conn->prepare("UPDATE artikel SET featured = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("ii", $new_featured, $artikel_id);
                    
                    if ($stmt->execute()) {
                        // Log activity
                        $description = $new_featured ? "Menandai artikel sebagai unggulan" : "Menghapus artikel dari unggulan";
                        $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'update', ?, ?, NOW())");
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                        $log_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $description, $ip_address);
                        $log_stmt->execute();
                        
                        $success_message = $new_featured ? "Artikel berhasil ditandai sebagai unggulan" : "Artikel berhasil dihapus dari unggulan";
                    } else {
                        throw new Exception("Gagal mengubah status unggulan artikel.");
                    }
                    break;
                    
                case 'increment_views':
                    $stmt = $conn->prepare("UPDATE artikel SET views = views + 1 WHERE id = ?");
                    $stmt->bind_param("i", $artikel_id);
                    $stmt->execute();
                    break;
                    
                case 'delete':
                    // Delete image file if exists
                    $img_stmt = $conn->prepare("SELECT gambar FROM artikel WHERE id = ?");
                    $img_stmt->bind_param("i", $artikel_id);
                    $img_stmt->execute();
                    $img_result = $img_stmt->get_result();
                    $img_data = $img_result->fetch_assoc();
                    
                    if ($img_data && $img_data['gambar']) {
                        $image_path = '../../assets/uploads/artikel/' . $img_data['gambar'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    
                    // Delete article
                    $delete_stmt = $conn->prepare("DELETE FROM artikel WHERE id = ?");
                    $delete_stmt->bind_param("i", $artikel_id);
                    
                    if ($delete_stmt->execute()) {
                        // Log activity
                        $description = "Menghapus artikel: " . ($artikel['judul'] ?? 'Unknown');
                        $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'delete', ?, ?, NOW())");
                        $ip_address = $_SERVER['REMOTE_ADDR'];
                        $log_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $description, $ip_address);
                        $log_stmt->execute();
                        
                        $_SESSION['success_message'] = "Artikel berhasil dihapus.";
                        header('Location: index.php');
                        exit;
                    } else {
                        throw new Exception("Gagal menghapus artikel.");
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get article data
try {
    $stmt = $conn->prepare("SELECT * FROM artikel WHERE id = ?");
    $stmt->bind_param("i", $artikel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_message'] = "Artikel tidak ditemukan.";
        header('Location: index.php');
        exit;
    }
    
    $artikel = $result->fetch_assoc();
    
} catch (Exception $e) {
    $error_message = "Error mengambil data artikel: " . $e->getMessage();
}

// Helper functions
function formatTanggalIndonesia($date) {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
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
    $jam = date('H:i', $timestamp);
    
    return $hari . ' ' . $bulan[$bulan_num] . ' ' . $tahun . ' ' . $jam;
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit yang lalu';
    if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
    return date('M j, Y', strtotime($datetime));
}

function estimateReadingTime($content) {
    $word_count = str_word_count(strip_tags($content));
    $reading_time = ceil($word_count / 200); // Average reading speed 200 words per minute
    return $reading_time;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($artikel['judul'] ?? 'Artikel') ?> - UKM Madani Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1a5f3f;
            --secondary-color: #d4af37;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
            --white: #ffffff;
            --border-color: #dee2e6;
            --text-dark: #495057;
            --text-muted: #6c757d;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
            --gradient: linear-gradient(135deg, #1a5f3f 0%, #2d8659 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-color);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            background: white;
            padding: 20px 30px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left h1 {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 8px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Article Layout */
        .article-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        /* Article Content */
        .article-content {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .article-hero {
            position: relative;
            height: 300px;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .article-hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .article-hero .no-image {
            color: white;
            text-align: center;
        }

        .article-hero .no-image i {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.7;
        }

        .article-meta-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
            padding: 30px;
        }

        .article-badges {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-published {
            background: var(--success-color);
            color: white;
        }

        .badge-draft {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .badge-featured {
            background: var(--secondary-color);
            color: white;
        }

        .article-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .article-body {
            padding: 40px;
        }

        .article-excerpt {
            font-size: 1.1rem;
            color: var(--text-muted);
            font-style: italic;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--light-color);
            border-left: 4px solid var(--primary-color);
            border-radius: 8px;
        }

        .article-main-content {
            font-size: 1rem;
            line-height: 1.8;
            color: var(--text-dark);
        }

        .article-main-content h1,
        .article-main-content h2,
        .article-main-content h3,
        .article-main-content h4,
        .article-main-content h5,
        .article-main-content h6 {
            color: var(--primary-color);
            margin: 30px 0 15px 0;
            font-weight: 600;
        }

        .article-main-content p {
            margin-bottom: 20px;
        }

        .article-main-content img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: var(--shadow);
        }

        .article-main-content blockquote {
            border-left: 4px solid var(--secondary-color);
            padding: 20px;
            margin: 20px 0;
            background: rgba(212, 175, 55, 0.1);
            border-radius: 8px;
            font-style: italic;
        }

        .article-main-content ul,
        .article-main-content ol {
            margin: 20px 0;
            padding-left: 30px;
        }

        .article-main-content li {
            margin-bottom: 8px;
        }

        .article-tags {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
        }

        .tags-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tag-list {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tag {
            background: var(--light-color);
            color: var(--text-dark);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .tag:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Sidebar */
        .article-sidebar {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .sidebar-card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .sidebar-card-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-card-body {
            padding: 25px;
        }

        /* Article Stats */
        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 20px 15px;
            background: var(--light-color);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 5px;
        }

        .meta-list {
            list-style: none;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .meta-item:last-child {
            border-bottom: none;
        }

        .meta-icon {
            width: 35px;
            height: 35px;
            background: var(--light-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .meta-content {
            flex: 1;
        }

        .meta-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 2px;
        }

        .meta-value {
            font-weight: 500;
            color: var(--text-dark);
        }

        /* Action Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2d8659;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: var(--text-muted);
            border: 2px solid var(--border-color);
        }

        .btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.85rem;
        }

        .action-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .action-group {
            display: flex;
            gap: 10px;
        }

        /* Quick Actions Form */
        .quick-action-form {
            display: inline;
        }

        /* Toggle Switches */
        .toggle-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: var(--light-color);
            border-radius: 10px;
            margin: 10px 0;
        }

        .toggle-label {
            font-weight: 500;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toggle-switch {
            position: relative;
            width: 50px;
            height: 24px;
            background: var(--border-color);
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .toggle-switch.active {
            background: var(--success-color);
        }

        .toggle-switch::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s ease;
        }

        .toggle-switch.active::after {
            transform: translateX(26px);
        }

        /* Reading Progress */
        .reading-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: var(--secondary-color);
            z-index: 9999;
            transition: width 0.3s ease;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .article-layout {
                grid-template-columns: 1fr;
            }

            .article-sidebar {
                order: -1;
            }

            .stat-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .article-body {
                padding: 25px;
            }

            .article-title {
                font-size: 1.6rem;
            }

            .stat-grid {
                grid-template-columns: 1fr 1fr;
            }

            .action-group {
                flex-direction: column;
            }
        }

        /* Loading State */
        .btn.loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            text-align: center;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: var(--danger-color);
            margin-bottom: 10px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
    </style>
</head>
<body>
    <!-- Reading Progress Bar -->
    <div class="reading-progress" id="readingProgress"></div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <h1><i class="fas fa-pen-fancy"></i> Detail Artikel</h1>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a>
                        <i class="fas fa-chevron-right"></i>
                        <a href="index.php">Artikel</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>Detail</span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                    <a href="edit.php?id=<?= $artikel_id ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($artikel): ?>
        <!-- Article Layout -->
        <div class="article-layout">
            <!-- Main Content -->
            <div class="article-content">
                <!-- Hero Section -->
                <div class="article-hero">
                    <?php if (!empty($artikel['gambar'])): ?>
                        <img src="../../assets/uploads/artikel/<?= htmlspecialchars($artikel['gambar']) ?>" 
                             alt="<?= htmlspecialchars($artikel['gambar_alt'] ?? $artikel['judul']) ?>">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-image"></i>
                            <p>Tidak ada gambar</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="article-meta-overlay">
                        <div class="article-badges">
                            <span class="badge badge-<?= $artikel['status'] ?>">
                                <i class="fas fa-<?= $artikel['status'] === 'published' ? 'globe' : 'edit' ?>"></i>
                                <?= ucfirst($artikel['status']) ?>
                            </span>
                            <?php if ($artikel['featured']): ?>
                                <span class="badge badge-featured">
                                    <i class="fas fa-star"></i>
                                    Unggulan
                                </span>
                            <?php endif; ?>
                        </div>
                        <h1 class="article-title"><?= htmlspecialchars($artikel['judul']) ?></h1>
                    </div>
                </div>

                <!-- Article Body -->
                <div class="article-body">
                    <?php if (!empty($artikel['excerpt'])): ?>
                        <div class="article-excerpt">
                            <i class="fas fa-quote-left"></i>
                            <?= htmlspecialchars($artikel['excerpt']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Main Content -->
                    <div class="article-main-content">
                        <?= $artikel['konten'] ?>
                    </div>

                    <!-- Tags -->
                    <?php if (!empty($artikel['tags'])): ?>
                        <div class="article-tags">
                            <div class="tags-label">
                                <i class="fas fa-tags"></i>
                                Tags:
                            </div>
                            <div class="tag-list">
                                <?php 
                                $tags = explode(',', $artikel['tags']);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                                ?>
                                    <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="article-sidebar">
                <!-- Article Stats -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <i class="fas fa-chart-bar"></i>
                        Statistik Artikel
                    </div>
                    <div class="sidebar-card-body">
                        <div class="stat-grid">
                            <div class="stat-item">
                                <span class="stat-number"><?= number_format($artikel['views']) ?></span>
                                <span class="stat-label">Views</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= estimateReadingTime($artikel['konten']) ?></span>
                                <span class="stat-label">Min Baca</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= str_word_count(strip_tags($artikel['konten'])) ?></span>
                                <span class="stat-label">Kata</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= strlen(strip_tags($artikel['konten'])) ?></span>
                                <span class="stat-label">Karakter</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Article Meta -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <i class="fas fa-info-circle"></i>
                        Informasi Artikel
                    </div>
                    <div class="sidebar-card-body">
                        <ul class="meta-list">
                            <li class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Penulis</div>
                                    <div class="meta-value"><?= htmlspecialchars($artikel['penulis']) ?></div>
                                </div>
                            </li>
                            <li class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-folder"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Kategori</div>
                                    <div class="meta-value"><?= htmlspecialchars($artikel['kategori'] ?: 'Tidak ada kategori') ?></div>
                                </div>
                            </li>
                            <li class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Dibuat</div>
                                    <div class="meta-value"><?= formatTanggalIndonesia($artikel['created_at']) ?></div>
                                </div>
                            </li>
                            <li class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-calendar-edit"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Terakhir Update</div>
                                    <div class="meta-value"><?= timeAgo($artikel['updated_at']) ?></div>
                                </div>
                            </li>
                            <li class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Tanggal Publish</div>
                                    <div class="meta-value"><?= formatTanggalIndonesia($artikel['tanggal_publish']) ?></div>
                                </div>
                            </li>
                            <li class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-link"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Slug</div>
                                    <div class="meta-value"><?= htmlspecialchars($artikel['slug']) ?></div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <i class="fas fa-bolt"></i>
                        Quick Actions
                    </div>
                    <div class="sidebar-card-body">
                        <!-- Status Toggle -->
                        <div class="toggle-group">
                            <div class="toggle-label">
                                <i class="fas fa-<?= $artikel['status'] === 'published' ? 'globe' : 'edit' ?>"></i>
                                Status: <?= ucfirst($artikel['status']) ?>
                            </div>
                            <form method="POST" class="quick-action-form">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="status" value="<?= $artikel['status'] ?>">
                                <div class="toggle-switch <?= $artikel['status'] === 'published' ? 'active' : '' ?>" 
                                     onclick="this.closest('form').submit()"></div>
                            </form>
                        </div>

                        <!-- Featured Toggle -->
                        <div class="toggle-group">
                            <div class="toggle-label">
                                <i class="fas fa-star"></i>
                                Artikel Unggulan
                            </div>
                            <form method="POST" class="quick-action-form">
                                <input type="hidden" name="action" value="toggle_featured">
                                <input type="hidden" name="featured" value="<?= $artikel['featured'] ?>">
                                <div class="toggle-switch <?= $artikel['featured'] ? 'active' : '' ?>" 
                                     onclick="this.closest('form').submit()"></div>
                            </form>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-grid">
                            <div class="action-group">
                                <a href="edit.php?id=<?= $artikel_id ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit Artikel
                                </a>
                                <form method="POST" class="quick-action-form">
                                    <input type="hidden" name="action" value="increment_views">
                                    <button type="submit" class="btn btn-outline btn-sm">
                                        <i class="fas fa-eye"></i> +1 View
                                    </button>
                                </form>
                            </div>
                            
                            <div class="action-group">
                                <a href="../../artikel/<?= htmlspecialchars($artikel['slug']) ?>" 
                                   target="_blank" class="btn btn-success btn-sm">
                                    <i class="fas fa-external-link-alt"></i> Lihat di Website
                                </a>
                                <button onclick="copyToClipboard('<?= htmlspecialchars($artikel['slug']) ?>')" 
                                        class="btn btn-outline btn-sm">
                                    <i class="fas fa-copy"></i> Copy Link
                                </button>
                            </div>
                            
                            <button onclick="showDeleteModal()" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Hapus Artikel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SEO Info -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <i class="fas fa-search"></i>
                        SEO Information
                    </div>
                    <div class="sidebar-card-body">
                        <ul class="meta-list">
                            <li class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-heading"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Panjang Judul</div>
                                    <div class="meta-value">
                                        <?= strlen($artikel['judul']) ?> karakter
                                        <?php if (strlen($artikel['judul']) > 60): ?>
                                            <span style="color: var(--warning-color);">(Terlalu panjang)</span>
                                        <?php elseif (strlen($artikel['judul']) < 30): ?>
                                            <span style="color: var(--info-color);">(Bisa diperpanjang)</span>
                                        <?php else: ?>
                                            <span style="color: var(--success-color);">(Optimal)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                            <li class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-paragraph"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Panjang Excerpt</div>
                                    <div class="meta-value">
                                        <?= strlen($artikel['excerpt'] ?? '') ?> karakter
                                        <?php if (empty($artikel['excerpt'])): ?>
                                            <span style="color: var(--warning-color);">(Tidak ada)</span>
                                        <?php elseif (strlen($artikel['excerpt']) > 160): ?>
                                            <span style="color: var(--warning-color);">(Terlalu panjang)</span>
                                        <?php else: ?>
                                            <span style="color: var(--success-color);">(Optimal)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                            <li class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-image"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Gambar Alt Text</div>
                                    <div class="meta-value">
                                        <?php if (!empty($artikel['gambar_alt'])): ?>
                                            <span style="color: var(--success-color);">✓ Ada</span>
                                        <?php else: ?>
                                            <span style="color: var(--warning-color);">✗ Tidak ada</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus</h3>
                    <p>Apakah Anda yakin ingin menghapus artikel ini?</p>
                    <p><strong>"<?= htmlspecialchars($artikel['judul']) ?>"</strong></p>
                    <p style="color: var(--danger-color); font-size: 0.9rem;">
                        <i class="fas fa-warning"></i>
                        Tindakan ini tidak dapat dibatalkan!
                    </p>
                </div>
                <div class="modal-actions">
                    <button onclick="closeDeleteModal()" class="btn btn-outline">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Ya, Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Article Not Found -->
        <div class="article-content">
            <div class="article-body" style="text-align: center; padding: 60px;">
                <i class="fas fa-file-times" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 20px;"></i>
                <h2 style="color: var(--text-muted); margin-bottom: 15px;">Artikel Tidak Ditemukan</h2>
                <p style="color: var(--text-muted); margin-bottom: 30px;">
                    Artikel yang Anda cari tidak ditemukan atau telah dihapus.
                </p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar Artikel
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Reading progress bar
        window.addEventListener('scroll', function() {
            const article = document.querySelector('.article-main-content');
            if (!article) return;
            
            const articleTop = article.offsetTop;
            const articleHeight = article.offsetHeight;
            const windowHeight = window.innerHeight;
            const scrollTop = window.pageYOffset;
            
            const progress = Math.min(
                Math.max((scrollTop - articleTop + windowHeight) / articleHeight, 0),
                1
            ) * 100;
            
            document.getElementById('readingProgress').style.width = progress + '%';
        });

        // Delete modal functions
        function showDeleteModal() {
            document.getElementById('deleteModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Copy to clipboard function
        function copyToClipboard(slug) {
            const url = window.location.origin + '/artikel/' + slug;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    showNotification('Link berhasil disalin!', 'success');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('Link berhasil disalin!', 'success');
            }
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                animation: slideIn 0.3s ease;
            `;
            
            const icon = type === 'success' ? 'check-circle' : 
                        type === 'danger' ? 'exclamation-triangle' : 'info-circle';
            
            notification.innerHTML = `
                <i class="fas fa-${icon}"></i>
                ${message}
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Add CSS for notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC to close modal
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
            
            // Ctrl+E for edit
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                window.location.href = 'edit.php?id=<?= $artikel_id ?>';
            }
            
            // Ctrl+B for back
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                window.location.href = 'index.php';
            }
        });

        // Form submission loading states
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.classList.add('loading');
                    button.disabled = true;
                }
                
                // Auto-reload for toggle actions
                if (this.querySelector('input[name="action"]')) {
                    const action = this.querySelector('input[name="action"]').value;
                    if (action === 'toggle_status' || action === 'toggle_featured' || action === 'increment_views') {
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }
                }
            });
        });

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.3s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.style.display = 'none';
                    }
                }, 300);
            });
        }, 5000);

        // Image zoom functionality
        document.querySelectorAll('.article-main-content img').forEach(img => {
            img.style.cursor = 'zoom-in';
            img.addEventListener('click', function() {
                const overlay = document.createElement('div');
                overlay.style.cssText = `
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
                `;
                
                const zoomedImg = document.createElement('img');
                zoomedImg.src = this.src;
                zoomedImg.style.cssText = `
                    max-width: 90%;
                    max-height: 90%;
                    object-fit: contain;
                    border-radius: 10px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
                `;
                
                overlay.appendChild(zoomedImg);
                document.body.appendChild(overlay);
                document.body.style.overflow = 'hidden';
                
                overlay.addEventListener('click', function() {
                    document.body.removeChild(overlay);
                    document.body.style.overflow = 'auto';
                });
            });
        });

        // Console welcome message
        console.log(`
        📖 Article View Page Loaded!
        🔧 Quick Actions Available:
        - Ctrl+E: Edit article
        - Ctrl+B: Back to list
        - ESC: Close modals
        
        📊 Article Stats:
        - Views: ${<?= $artikel['views'] ?? 0 ?>}
        - Words: ${<?= str_word_count(strip_tags($artikel['konten'] ?? '')) ?>}
        - Reading Time: ${<?= estimateReadingTime($artikel['konten'] ?? '') ?>} min
        `);
    </script>
</body>
</html>