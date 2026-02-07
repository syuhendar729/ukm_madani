<?php
// admin/struktur/tambah.php
session_start();
require_once '../../config/database.php';

// Check admin authentication - gunakan satu metode saja
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Session timeout check (sama seperti file sebelumnya)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
    session_destroy();
    header('Location: ../login.php?message=session_expired');
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();

$admin_name = $_SESSION['admin_nama'] ?? $_SESSION['admin_username'] ?? 'Admin';

// Initialize variables
$search = $_GET['search'] ?? '';
$kategori_filter = $_GET['kategori'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $selected_ids = $_POST['selected_ids'] ?? [];
    $action = $_POST['action'];
    
    if (!empty($selected_ids) && is_array($selected_ids)) {
        $ids_placeholder = str_repeat('?,', count($selected_ids) - 1) . '?';
        
        try {
            switch ($action) {
              // Ganti bagian bulk delete di index.php dengan kode ini:

            case 'delete':
                // STEP 1: Get images to delete first
                $stmt = $conn->prepare("SELECT id, judul, cover_image FROM galeri WHERE id IN ($ids_placeholder)");
                $stmt->bind_param(str_repeat('i', count($selected_ids)), ...$selected_ids);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $images_to_delete = [];
                $titles_to_delete = [];
                while ($row = $result->fetch_assoc()) {
                    if (!empty($row['cover_image'])) {
                        $images_to_delete[] = $row['cover_image'];
                    }
                    $titles_to_delete[] = $row['judul'];
                }
                
                // STEP 2: Delete from database
                $delete_stmt = $conn->prepare("DELETE FROM galeri WHERE id IN ($ids_placeholder)");
                $delete_stmt->bind_param(str_repeat('i', count($selected_ids)), ...$selected_ids);
                
                if ($delete_stmt->execute()) {
                    $deleted_count = $delete_stmt->affected_rows;
                    
                    // STEP 3: Delete image files
                    $files_deleted = 0;
                    foreach ($images_to_delete as $image) {
                        $image_path = '../../assets/uploads/galeri/' . $image;
                        if (file_exists($image_path)) {
                            if (unlink($image_path)) {
                                $files_deleted++;
                            } else {
                                error_log("Failed to delete image file: " . $image_path);
                            }
                        }
                    }
                    
                    // STEP 4: Log activity
                    try {
                        $activity_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'bulk_delete', ?, ?, NOW())");
                        $activity_desc = "Menghapus " . $deleted_count . " dokumentasi galeri secara bulk: " . implode(', ', array_slice($titles_to_delete, 0, 3)) . ($deleted_count > 3 ? '...' : '');
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                        
                        $activity_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $activity_desc, $ip_address);
                        $activity_stmt->execute();
                    } catch (Exception $e) {
                        // Ignore if activity_log doesn't exist
                        error_log("Failed to log bulk delete activity: " . $e->getMessage());
                    }
                    
                    // Success message
                    $success_message = $deleted_count . " dokumentasi berhasil dihapus.";
                    if ($files_deleted > 0) {
                        $success_message .= " " . $files_deleted . " file gambar juga telah dihapus.";
                    }
                } else {
                    throw new Exception("Gagal menghapus dokumentasi dari database: " . $delete_stmt->error);
                }
                break;
                    
                case 'publish':
                    $stmt = $conn->prepare("UPDATE galeri SET status = 'published', updated_at = NOW() WHERE id IN ($ids_placeholder)");
                    $stmt->bind_param(str_repeat('i', count($selected_ids)), ...$selected_ids);
                    $stmt->execute();
                    $success_message = count($selected_ids) . " dokumentasi berhasil dipublikasikan.";
                    break;
                    
                case 'draft':
                    $stmt = $conn->prepare("UPDATE galeri SET status = 'draft', updated_at = NOW() WHERE id IN ($ids_placeholder)");
                    $stmt->bind_param(str_repeat('i', count($selected_ids)), ...$selected_ids);
                    $stmt->execute();
                    $success_message = count($selected_ids) . " dokumentasi berhasil diubah ke draft.";
                    break;
            }
        } catch (Exception $e) {
            $error_message = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Build WHERE clause for filtering
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(judul LIKE ? OR deskripsi LIKE ? OR tags LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $param_types .= 'sss';
}

if (!empty($kategori_filter)) {
    $where_conditions[] = "kategori = ?";
    $params[] = $kategori_filter;
    $param_types .= 's';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Validate sort and order
$allowed_sorts = ['judul', 'kategori', 'tanggal_kegiatan', 'status', 'created_at', 'updated_at'];
$sort = in_array($sort, $allowed_sorts) ? $sort : 'created_at';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM galeri $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($param_types, ...$params);
    $count_stmt->execute();
    $total_result = $count_stmt->get_result();
} else {
    $total_result = $conn->query($count_sql);
}
$total_records = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Get galeri data
$sql = "SELECT * FROM galeri $where_clause ORDER BY $sort $order LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$param_types .= 'ii';

if (!empty($where_conditions)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // If no filters, use simpler query
    $simple_sql = "SELECT * FROM galeri ORDER BY $sort $order LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($simple_sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

$galeri_items = [];
while ($row = $result->fetch_assoc()) {
    $galeri_items[] = $row;
}

// Get categories for filter dropdown
$categories = [];
try {
    $cat_result = $conn->query("SELECT DISTINCT kategori FROM galeri WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat['kategori'];
    }
} catch (Exception $e) {
    // Handle error silently
}

// Get statistics
$stats = [
    'total' => 0,
    'published' => 0,
    'draft' => 0,
    'today' => 0
];

try {
    $stats_result = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
        FROM galeri
    ");
    $stats = $stats_result->fetch_assoc();
} catch (Exception $e) {
    // Handle error silently
}

$admin_name = $_SESSION['admin_nama'] ?? $_SESSION['admin_username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Galeri - UKM Madani</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
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
            padding: 20px 0;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .page-title {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            color: var(--secondary-color);
            font-size: 2rem;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.published {
            border-left-color: var(--success-color);
        }

        .stat-card.draft {
            border-left-color: var(--warning-color);
        }

        .stat-card.today {
            border-left-color: var(--info-color);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-card.published .stat-number {
            color: var(--success-color);
        }

        .stat-card.draft .stat-number {
            color: var(--warning-color);
        }

        .stat-card.today .stat-number {
            color: var(--info-color);
        }

        .stat-label {
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            float: right;
            font-size: 2.5rem;
            color: var(--border-color);
            margin-top: -10px;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto auto;
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
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
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: var(--text-muted);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Bulk Actions */
        .bulk-actions {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            display: none;
        }

        .bulk-actions.show {
            display: block;
        }

        .bulk-actions-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .selected-count {
            font-weight: 600;
            color: var(--primary-color);
        }

        .bulk-buttons {
            display: flex;
            gap: 10px;
        }

        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .gallery-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .gallery-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .gallery-card.selected {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
        }

        .card-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .gallery-card:hover .card-image img {
            transform: scale(1.05);
        }

        .no-image {
            background: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 3rem;
        }

        .card-checkbox {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 10;
        }

        .card-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .card-status {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-published {
            background: var(--success-color);
            color: white;
        }

        .status-draft {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .card-content {
            padding: 20px;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .card-description {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
        }

        .tag {
            background: var(--light-color);
            color: var(--text-muted);
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            border: 1px solid var(--border-color);
        }

        .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border-color);
            padding-top: 15px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        .card-date {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }

        .pagination {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination .current {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--border-color);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .empty-state p {
            font-size: 1rem;
            margin-bottom: 20px;
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

        .alert i {
            font-size: 1.2rem;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        .loading i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .filter-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .bulk-actions-content {
                flex-direction: column;
                gap: 15px;
            }

            .bulk-buttons {
                width: 100%;
                justify-content: center;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .card-actions {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }

            .action-buttons {
                justify-content: center;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .header-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            .filter-group input,
            .filter-group select {
                font-size: 16px; /* Prevent zoom on iOS */
            }
        }

        /* Print Styles */
        @media print {
            .header,
            .filter-section,
            .bulk-actions,
            .pagination-container {
                display: none;
            }

            .gallery-card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid var(--border-color);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-images"></i>
                    Kelola Galeri
                </h1>
                <div class="breadcrumb">
                    <a href="../dashboard.php">Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Galeri</span>
                </div>
            </div>
            <div class="header-actions">
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Tambah Dokumentasi
                </a>
                <a href="../dashboard.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['total']) ?></div>
                <div class="stat-label">Total Dokumentasi</div>
                <i class="fas fa-images stat-icon"></i>
            </div>
            <div class="stat-card published">
                <div class="stat-number"><?= number_format($stats['published']) ?></div>
                <div class="stat-label">Dipublikasikan</div>
                <i class="fas fa-eye stat-icon"></i>
            </div>
            <div class="stat-card draft">
                <div class="stat-number"><?= number_format($stats['draft']) ?></div>
                <div class="stat-label">Draft</div>
                <i class="fas fa-edit stat-icon"></i>
            </div>
            <div class="stat-card today">
                <div class="stat-number"><?= number_format($stats['today']) ?></div>
                <div class="stat-label">Hari Ini</div>
                <i class="fas fa-calendar-day stat-icon"></i>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Cari Dokumentasi</label>
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Cari judul, deskripsi, atau tags...">
                    </div>
                    <div class="filter-group">
                        <label for="kategori">Kategori</label>
                        <select id="kategori" name="kategori">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $kategori_filter === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">Semua Status</option>
                            <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Dipublikasikan</option>
                            <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                    <a href="?" class="btn btn-secondary">
                        <i class="fas fa-refresh"></i>
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <form method="POST" id="bulkForm">
                <div class="bulk-actions-content">
                    <div class="selected-count">
                        <span id="selectedCount">0</span> item dipilih
                    </div>
                    <div class="bulk-buttons">
                        <button type="submit" name="action" value="publish" class="btn btn-success btn-sm">
                            <i class="fas fa-eye"></i>
                            Publikasikan
                        </button>
                        <button type="submit" name="action" value="draft" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i>
                            Jadikan Draft
                        </button>
                        <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" 
                                onclick="return confirm('Apakah Anda yakin ingin menghapus item yang dipilih? Tindakan ini tidak dapat dibatalkan.')">
                            <i class="fas fa-trash"></i>
                            Hapus
                        </button>
                    </div>
                </div>
                <div id="selectedIds"></div>
            </form>
        </div>

        <!-- Gallery Grid -->
        <?php if (empty($galeri_items)): ?>
            <div class="empty-state">
                <i class="fas fa-images"></i>
                <h3>Belum Ada Dokumentasi</h3>
                <?php if (!empty($search) || !empty($kategori_filter) || !empty($status_filter)): ?>
                    <p>Tidak ditemukan dokumentasi yang sesuai dengan filter.</p>
                    <a href="?" class="btn btn-primary">Lihat Semua Dokumentasi</a>
                <?php else: ?>
                    <p>Mulai dengan menambahkan dokumentasi kegiatan pertama Anda.</p>
                    <a href="create.php" class="btn btn-primary">Tambah Dokumentasi</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($galeri_items as $item): ?>
                    <div class="gallery-card" data-id="<?= $item['id'] ?>">
                        <div class="card-image">
                            <?php if (!empty($item['cover_image']) && file_exists('../../assets/uploads/galeri/' . $item['cover_image'])): ?>
                                <img src="../../assets/uploads/galeri/<?= htmlspecialchars($item['cover_image']) ?>" 
                                     alt="<?= htmlspecialchars($item['judul']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-checkbox">
                                <input type="checkbox" class="item-checkbox" value="<?= $item['id'] ?>">
                            </div>
                            
                            <div class="card-status status-<?= $item['status'] ?>">
                                <?= $item['status'] === 'published' ? 'Publik' : 'Draft' ?>
                            </div>
                        </div>
                        
                        <div class="card-content">
                            <h3 class="card-title"><?= htmlspecialchars($item['judul']) ?></h3>
                            
                            <div class="card-meta">
                                <?php if (!empty($item['kategori'])): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-folder"></i>
                                        <?= htmlspecialchars($item['kategori']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($item['lokasi'])): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($item['lokasi']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($item['tanggal_kegiatan'])): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d M Y', strtotime($item['tanggal_kegiatan'])) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($item['total_foto']) && $item['total_foto'] > 0): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-images"></i>
                                        <?= $item['total_foto'] ?> foto
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($item['deskripsi'])): ?>
                                <p class="card-description"><?= htmlspecialchars($item['deskripsi']) ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($item['tags'])): ?>
                                <div class="card-tags">
                                    <?php 
                                    $tags = array_slice(array_map('trim', explode(',', $item['tags'])), 0, 3);
                                    foreach ($tags as $tag): 
                                        if (!empty($tag)):
                                    ?>
                                        <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-actions">
                                <div class="action-buttons">
                                    <a href="view.php?id=<?= $item['id'] ?>" class="btn btn-primary btn-sm" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?= $item['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if (!empty($item['google_drive_link'])): ?>
                                        <a href="<?= htmlspecialchars($item['google_drive_link']) ?>" target="_blank" 
                                           class="btn btn-success btn-sm" title="Buka Google Drive">
                                            <i class="fab fa-google-drive"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="deleteItem(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['judul'])) ?>')" 
                                            title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="card-date">
                                    Dibuat: <?= date('d M Y', strtotime($item['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination">
                        <?php
                        $query_params = $_GET;
                        unset($query_params['page']);
                        $base_url = '?' . http_build_query($query_params);
                        $base_url = $base_url === '?' ? '?' : $base_url . '&';
                        ?>
                        
                        <?php if ($page > 1): ?>
                            <a href="<?= $base_url ?>page=1">&laquo; Pertama</a>
                            <a href="<?= $base_url ?>page=<?= $page - 1 ?>">&lsaquo; Prev</a>
                        <?php else: ?>
                            <span class="disabled">&laquo; Pertama</span>
                            <span class="disabled">&lsaquo; Prev</span>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="<?= $base_url ?>page=<?= $i ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="<?= $base_url ?>page=<?= $page + 1 ?>">Next &rsaquo;</a>
                            <a href="<?= $base_url ?>page=<?= $total_pages ?>">Terakhir &raquo;</a>
                        <?php else: ?>
                            <span class="disabled">Next &rsaquo;</span>
                            <span class="disabled">Terakhir &raquo;</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="selected_ids[]" id="deleteId">
    </form>

    <script>
        // Bulk selection functionality
        let selectedItems = new Set();

        function updateBulkActions() {
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            const selectedIds = document.getElementById('selectedIds');
            
            selectedCount.textContent = selectedItems.size;
            
            if (selectedItems.size > 0) {
                bulkActions.classList.add('show');
                
                // Update hidden inputs for selected IDs
                selectedIds.innerHTML = '';
                selectedItems.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_ids[]';
                    input.value = id;
                    selectedIds.appendChild(input);
                });
            } else {
                bulkActions.classList.remove('show');
            }
        }

        // Checkbox event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const id = parseInt(this.value);
                    const card = this.closest('.gallery-card');
                    
                    if (this.checked) {
                        selectedItems.add(id);
                        card.classList.add('selected');
                    } else {
                        selectedItems.delete(id);
                        card.classList.remove('selected');
                    }
                    
                    updateBulkActions();
                });
            });
            
            // Select all functionality
            const selectAllBtn = document.createElement('button');
            selectAllBtn.type = 'button';
            selectAllBtn.className = 'btn btn-outline btn-sm';
            selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Pilih Semua';
            selectAllBtn.style.position = 'fixed';
            selectAllBtn.style.bottom = '20px';
            selectAllBtn.style.left = '20px';
            selectAllBtn.style.zIndex = '1000';
            
            selectAllBtn.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('.item-checkbox');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                
                checkboxes.forEach(checkbox => {
                    const id = parseInt(checkbox.value);
                    const card = checkbox.closest('.gallery-card');
                    
                    if (allChecked) {
                        checkbox.checked = false;
                        selectedItems.delete(id);
                        card.classList.remove('selected');
                        selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Pilih Semua';
                    } else {
                        checkbox.checked = true;
                        selectedItems.add(id);
                        card.classList.add('selected');
                        selectAllBtn.innerHTML = '<i class="fas fa-square"></i> Batal Pilih';
                    }
                });
                
                updateBulkActions();
            });
            
            if (checkboxes.length > 0) {
                document.body.appendChild(selectAllBtn);
            }
        });

        // Delete single item
        function deleteItem(id, title) {
            if (confirm(`Apakah Anda yakin ingin menghapus dokumentasi "${title}"? Tindakan ini tidak dapat dibatalkan.`)) {
                const deleteForm = document.getElementById('deleteForm');
                const deleteId = document.getElementById('deleteId');
                deleteId.value = id;
                deleteForm.submit();
            }
        }

        // Auto-submit filter form on change
        document.getElementById('kategori').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        document.getElementById('status').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        // Search with debounce
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    document.getElementById('filterForm').submit();
                }
            }, 500);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+A to select all
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                const selectAllBtn = document.querySelector('button[style*="position: fixed"]');
                if (selectAllBtn) {
                    selectAllBtn.click();
                }
            }
            
            // Delete key to delete selected items
            if (e.key === 'Delete' && selectedItems.size > 0) {
                e.preventDefault();
                const deleteBtn = document.querySelector('button[value="delete"]');
                if (deleteBtn && confirm(`Apakah Anda yakin ingin menghapus ${selectedItems.size} item yang dipilih?`)) {
                    deleteBtn.click();
                }
            }
            
            // Escape to clear selection
            if (e.key === 'Escape' && selectedItems.size > 0) {
                document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
                    cb.checked = false;
                    cb.closest('.gallery-card').classList.remove('selected');
                });
                selectedItems.clear();
                updateBulkActions();
            }
        });

        // Lazy loading for images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Success message auto-hide
        setTimeout(function() {
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                successAlert.style.transition = 'opacity 0.5s ease';
                successAlert.style.opacity = '0';
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, 500);
            }
        }, 5000);

        // Auto-refresh every 5 minutes
        setTimeout(function() {
            if (document.hidden === false) {
                location.reload();
            }
        }, 300000);

        // Page visibility API for auto-refresh
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Page became visible, check for updates
                const lastUpdate = localStorage.getItem('galeri_last_update');
                const now = Date.now();
                
                if (!lastUpdate || (now - parseInt(lastUpdate)) > 300000) { // 5 minutes
                    location.reload();
                }
            }
        });

        // Store last update timestamp
        localStorage.setItem('galeri_last_update', Date.now());

        // Enhanced card hover effects
        document.querySelectorAll('.gallery-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = selectedItems.has(parseInt(this.dataset.id)) ? 'translateY(-5px)' : 'translateY(0)';
            });
        });

        // Loading states for buttons
        document.querySelectorAll('button[type="submit"]').forEach(button => {
            button.addEventListener('click', function() {
                if (this.type === 'submit') {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                    this.disabled = true;
                    
                    // Reset after timeout
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 10000);
                }
            });
        });

        // Performance monitoring
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log(`📊 Galeri loaded in ${Math.round(loadTime)}ms`);
            
            // Log statistics
            console.log('📈 Statistics:', {
                total: <?= $stats['total'] ?>,
                published: <?= $stats['published'] ?>,
                draft: <?= $stats['draft'] ?>,
                today: <?= $stats['today'] ?>
            });
        });

        console.log('🎉 Galeri index loaded successfully!');
        console.log('💡 Tips: Gunakan Ctrl+A untuk pilih semua, Delete untuk hapus, Escape untuk batal');
    </script>
</body>
</html>