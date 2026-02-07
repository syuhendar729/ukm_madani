<?php
session_start();
require_once '../../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit;
}

// Session timeout check (2 jam)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
    session_destroy();
    header('Location: ../login.php?message=session_expired');
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();

// Regenerate session ID untuk keamanan
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Pagination settings
$items_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Filter settings
$filter_action = isset($_GET['action']) ? $_GET['action'] : '';
$filter_user = isset($_GET['user']) ? $_GET['user'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if (!empty($filter_action)) {
    $where_conditions[] = "action = ?";
    $params[] = $filter_action;
}

if (!empty($filter_user)) {
    $where_conditions[] = "user_name LIKE ?";
    $params[] = "%$filter_user%";
}

if (!empty($filter_date)) {
    $where_conditions[] = "DATE(created_at) = ?";
    $params[] = $filter_date;
}

if (!empty($search)) {
    $where_conditions[] = "(description LIKE ? OR user_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Initialize variables
$activities = [];
$total_records = 0;
$unique_actions = [];
$unique_users = [];
$error_message = '';

try {
    // Get activities with pagination and filters
    $activity_check = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($activity_check && $activity_check->num_rows > 0) {
        // Count total records
        $count_sql = "SELECT COUNT(*) as total FROM activity_log $where_clause";
        if (!empty($params)) {
            $count_stmt = $conn->prepare($count_sql);
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $count_stmt->bind_param($types, ...$params);
            }
            $count_stmt->execute();
            $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
        } else {
            $count_result = $conn->query($count_sql);
            $total_records = $count_result->fetch_assoc()['total'];
        }

        // Get activities
        $activity_sql = "SELECT * FROM activity_log $where_clause ORDER BY created_at DESC LIMIT $items_per_page OFFSET $offset";
        if (!empty($params)) {
            $activity_stmt = $conn->prepare($activity_sql);
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $activity_stmt->bind_param($types, ...$params);
            }
            $activity_stmt->execute();
            $result = $activity_stmt->get_result();
        } else {
            $result = $conn->query($activity_sql);
        }

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
        }

        // Get unique actions for filter
        $actions_result = $conn->query("SELECT DISTINCT action FROM activity_log ORDER BY action");
        if ($actions_result) {
            while ($row = $actions_result->fetch_assoc()) {
                $unique_actions[] = $row['action'];
            }
        }

        // Get unique users for filter
        $users_result = $conn->query("SELECT DISTINCT user_name FROM activity_log ORDER BY user_name");
        if ($users_result) {
            while ($row = $users_result->fetch_assoc()) {
                $unique_users[] = $row['user_name'];
            }
        }
    } else {
        // Fallback: Gunakan login_attempts sebagai activity log
        $login_attempts_check = $conn->query("SHOW TABLES LIKE 'login_attempts'");
        if ($login_attempts_check && $login_attempts_check->num_rows > 0) {
            $fallback_sql = "SELECT 
                id,
                ip_address,
                CASE 
                    WHEN success = 1 THEN 'login'
                    ELSE 'login_failed'
                END as action,
                CASE 
                    WHEN success = 1 THEN CONCAT('Berhasil login dari IP: ', ip_address)
                    ELSE CONCAT('Gagal login dari IP: ', ip_address, ' (Username: ', COALESCE(username, 'Unknown'), ')')
                END as description,
                COALESCE(username, 'Unknown') as user_name,
                attempt_time as created_at
                FROM login_attempts 
                ORDER BY attempt_time DESC 
                LIMIT $items_per_page OFFSET $offset";
            
            $result = $conn->query($fallback_sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $activities[] = $row;
                }
            }

            $count_result = $conn->query("SELECT COUNT(*) as total FROM login_attempts");
            $total_records = $count_result->fetch_assoc()['total'];
        }
    }
} catch (Exception $e) {
    $error_message = "Error mengambil data activity: " . $e->getMessage();
    error_log("Activity Log Error: " . $e->getMessage());
}

// Calculate pagination
$total_pages = ceil($total_records / $items_per_page);

// Helper functions
function getActivityIcon($type) {
    switch ($type) {
        case 'create': return 'plus';
        case 'update': return 'edit';
        case 'delete': return 'trash';
        case 'login': return 'sign-in-alt';
        case 'login_failed': return 'exclamation-triangle';
        case 'logout': return 'sign-out-alt';
        case 'bulk_delete': return 'trash-alt';
        default: return 'info';
    }
}

function getActivityColor($type) {
    switch ($type) {
        case 'create': return 'success';
        case 'update': return 'info';
        case 'delete': 
        case 'bulk_delete': return 'danger';
        case 'login': return 'primary';
        case 'login_failed': return 'warning';
        case 'logout': return 'secondary';
        default: return 'info';
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit yang lalu';
    if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
    return date('M j, Y H:i', strtotime($datetime));
}

function formatTanggalIndonesia($date) {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
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
    $jam = date('H:i', $timestamp);
    
    return $hari . ' ' . $bulan[$bulan_num] . ' ' . $tahun . ' ' . $jam;
}

// Safe admin name display
$admin_name = $_SESSION['admin_nama'] ?? $_SESSION['admin_username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - UKM Madani</title>
    
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

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        /* Header */
        .page-header {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .page-header h1 {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
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

        /* Filters */
        .filters-section {
            background: var(--white);
            padding: 25px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .filters-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
        }

        .filters-header h3 {
            color: var(--primary-color);
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 500;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: var(--white);
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
        }

        .search-box {
            position: relative;
            grid-column: 1 / -1;
        }

        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
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

        .btn-secondary {
            background: var(--text-muted);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--dark-color);
        }

        /* Activity List */
        .activity-section {
            background: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .activity-header {
            background: var(--primary-color);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .activity-stats {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .activity-list {
            max-height: none;
            overflow: visible;
        }

        .activity-item {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: all 0.3s ease;
            position: relative;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item:hover {
            background: var(--light-color);
            transform: translateX(5px);
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: transparent;
            transition: all 0.3s ease;
        }

        .activity-item:hover::before {
            background: var(--primary-color);
        }

        .activity-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .activity-icon.create {
            background: linear-gradient(135deg, var(--success-color), #059669);
        }

        .activity-icon.update {
            background: linear-gradient(135deg, var(--info-color), #2563eb);
        }

        .activity-icon.delete,
        .activity-icon.bulk_delete {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
        }

        .activity-icon.login {
            background: linear-gradient(135deg, var(--primary-color), #4f46e5);
        }

        .activity-icon.login_failed {
            background: linear-gradient(135deg, var(--warning-color), #f59e0b);
        }

        .activity-icon.logout {
            background: linear-gradient(135deg, var(--text-muted), #6b7280);
        }

        .activity-details {
            flex: 1;
            min-width: 0;
        }

        .activity-details h6 {
            margin: 0 0 8px 0;
            font-size: 0.95rem;
            color: var(--text-dark);
            font-weight: 600;
            line-height: 1.4;
        }

        .activity-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.82rem;
            color: var(--text-muted);
            flex-wrap: wrap;
        }

        .activity-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .activity-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-create {
            background: #d4edda;
            color: #155724;
        }

        .badge-update {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-delete,
        .badge-bulk_delete {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-login {
            background: #e7f3ff;
            color: #1a5f3f;
        }

        .badge-login_failed {
            background: #fff3cd;
            color: #856404;
        }

        /* Pagination */
        .pagination-section {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .pagination {
            display: flex;
            gap: 5px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            text-decoration: none;
            color: var(--text-dark);
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

        .pagination-info {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 25px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h4 {
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border: 1px solid transparent;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .page-header,
            .filters-section,
            .activity-section {
                margin-bottom: 20px;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .activity-item {
                padding: 15px 20px;
                gap: 12px;
            }

            .activity-icon {
                width: 40px;
                height: 40px;
                font-size: 0.9rem;
            }

            .pagination-section {
                flex-direction: column;
                gap: 15px;
            }

            .filter-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-history"></i> Activity Log</h1>
            <div class="breadcrumb">
                <a href="../dashboard.php">Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span>Activity Log</span>
            </div>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters-section">
            <div class="filters-header">
                <h3><i class="fas fa-filter"></i> Filter & Pencarian</h3>
            </div>
            
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="action">Tipe Aktivitas</label>
                        <select name="action" id="action">
                            <option value="">Semua Aktivitas</option>
                            <?php foreach ($unique_actions as $action): ?>
                                <option value="<?= htmlspecialchars($action) ?>" 
                                        <?= $filter_action === $action ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $action)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="user">User</label>
                        <select name="user" id="user">
                            <option value="">Semua User</option>
                            <?php foreach ($unique_users as $user): ?>
                                <option value="<?= htmlspecialchars($user) ?>" 
                                        <?= $filter_user === $user ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="date">Tanggal</label>
                        <input type="date" name="date" id="date" value="<?= htmlspecialchars($filter_date) ?>">
                    </div>

                    <div class="filter-group search-box">
                        <label for="search">Pencarian</label>
                        <input type="text" name="search" id="search" 
                               placeholder="Cari deskripsi aktivitas..." 
                               value="<?= htmlspecialchars($search) ?>">
                        <i class="fas fa-search"></i>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Activity List -->
        <div class="activity-section">
            <div class="activity-header">
                <h3><i class="fas fa-list"></i> Riwayat Aktivitas</h3>
                <div class="activity-stats">
                    Total: <?= number_format($total_records) ?> aktivitas
                </div>
            </div>

            <div class="activity-list">
                <?php if (count($activities) > 0): ?>
                    <?php foreach ($activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= $activity['action'] ?>">
                            <i class="fas fa-<?= getActivityIcon($activity['action']) ?>"></i>
                        </div>
                        <div class="activity-details">
                            <h6><?= htmlspecialchars($activity['description']) ?></h6>
                            <div class="activity-meta">
                                <span>
                                    <i class="fas fa-user"></i>
                                    <?= htmlspecialchars($activity['user_name'] ?? 'System') ?>
                                </span>
                                <span class="activity-badge badge-<?= $activity['action'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $activity['action'])) ?>
                                </span>
                                <span>
                                    <i class="fas fa-clock"></i>
                                    <?= formatTanggalIndonesia($activity['created_at']) ?>
                                </span>
                                <span>
                                    <i class="fas fa-history"></i>
                                    <?= timeAgo($activity['created_at']) ?>
                                </span>
                                <?php if (!empty($activity['ip_address'])): ?>
                                <span>
                                    <i class="fas fa-globe"></i>
                                    <?= htmlspecialchars($activity['ip_address']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h4>Tidak Ada Aktivitas</h4>
                        <p>Tidak ada aktivitas yang ditemukan dengan filter yang diterapkan.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-section">
            <div class="pagination-info">
                Menampilkan <?= $offset + 1 ?> - <?= min($offset + $items_per_page, $total_records) ?> 
                dari <?= number_format($total_records) ?> aktivitas
            </div>
            
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?= $page - 1 ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php endif; ?>

                <?php
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?page=<?= $total_pages ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-submit form when filters change
        document.querySelectorAll('#action, #user, #date').forEach(element => {
            element.addEventListener('change', function() {
                // Optional: Auto-submit on filter change
                // this.form.submit();
            });
        });

        // Search functionality
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });

        // Search icon click
        document.querySelector('.search-box i').addEventListener('click', function() {
            document.getElementById('search').form.submit();
        });

        // Highlight search terms
        const searchTerm = '<?= htmlspecialchars($search) ?>';
        if (searchTerm) {
            const activityItems = document.querySelectorAll('.activity-details h6');
            activityItems.forEach(item => {
                const text = item.innerHTML;
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                item.innerHTML = text.replace(regex, '<mark style="background: #fff3cd; padding: 2px 4px; border-radius: 3px;">$1</mark>');
            });
        }

        // Smooth scroll for pagination
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                // Add loading state
                const icon = this.querySelector('i') || this;
                const originalContent = icon.innerHTML || this.textContent;
                
                if (icon.tagName === 'I') {
                    icon.className = 'fas fa-spinner fa-spin';
                } else {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }
                
                // Restore after navigation (fallback)
                setTimeout(() => {
                    if (icon.tagName === 'I') {
                        icon.innerHTML = originalContent;
                    } else {
                        this.innerHTML = originalContent;
                    }
                }, 1000);
            });
        });

        // Back to dashboard functionality
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = '../dashboard.php';
            }
        });

        // Tooltip for long descriptions
        document.querySelectorAll('.activity-details h6').forEach(element => {
            if (element.scrollWidth > element.clientWidth) {
                element.title = element.textContent;
                element.style.cursor = 'help';
            }
        });

        // Auto-refresh every 30 seconds (optional)
        let autoRefresh = false;
        if (autoRefresh) {
            setInterval(function() {
                if (document.hidden) return; // Don't refresh if tab is not active
                
                // Only refresh if on first page with no filters
                const urlParams = new URLSearchParams(window.location.search);
                if (!urlParams.has('page') || urlParams.get('page') === '1') {
                    if (!urlParams.has('action') && !urlParams.has('user') && 
                        !urlParams.has('date') && !urlParams.has('search')) {
                        window.location.reload();
                    }
                }
            }, 30000);
        }

        // Activity item click for details (optional enhancement)
        document.querySelectorAll('.activity-item').forEach(item => {
            item.addEventListener('click', function(e) {
                // Could implement a modal or expand functionality here
                // For now, just add a subtle animation
                this.style.transform = 'scale(1.02)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 200);
            });
        });

        // Filter badge display
        function updateFilterBadges() {
            const urlParams = new URLSearchParams(window.location.search);
            const filterSection = document.querySelector('.filters-header');
            let badges = '';
            
            if (urlParams.get('action')) {
                badges += `<span class="filter-badge">Action: ${urlParams.get('action')}</span>`;
            }
            if (urlParams.get('user')) {
                badges += `<span class="filter-badge">User: ${urlParams.get('user')}</span>`;
            }
            if (urlParams.get('date')) {
                badges += `<span class="filter-badge">Date: ${urlParams.get('date')}</span>`;
            }
            if (urlParams.get('search')) {
                badges += `<span class="filter-badge">Search: "${urlParams.get('search')}"</span>`;
            }
            
            if (badges) {
                const badgeContainer = document.createElement('div');
                badgeContainer.className = 'active-filters';
                badgeContainer.innerHTML = badges;
                filterSection.appendChild(badgeContainer);
            }
        }

        // Add CSS for filter badges
        const style = document.createElement('style');
        style.textContent = `
            .filter-badge {
                display: inline-block;
                background: var(--primary-color);
                color: white;
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 0.75rem;
                margin: 0 5px 5px 0;
            }
            .active-filters {
                margin-top: 10px;
            }
            mark {
                background: #fff3cd !important;
                padding: 2px 4px !important;
                border-radius: 3px !important;
                font-weight: 600 !important;
            }
        `;
        document.head.appendChild(style);

        // Initialize filter badges
        updateFilterBadges();

        // Console log for debugging
        console.log(`
        📊 Activity Log Page Loaded
        🔍 Total Records: ${<?= $total_records ?>}
        📄 Current Page: ${<?= $page ?>}
        📋 Total Pages: ${<?= $total_pages ?>}
        🔧 Filters Applied: ${Object.keys(<?= json_encode($_GET) ?>).length}
        `);
    </script>
</body>
</html>