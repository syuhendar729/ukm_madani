<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Session timeout check (2 jam)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
    session_destroy();
    header('Location: login.php?message=session_expired');
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

// Initialize all variables
$stats = [
    'total_berita' => 0,
    'total_artikel' => 0,
    'total_galeri' => 0,
    'total_users' => 0,
    'berita_published' => 0,
    'berita_draft' => 0,
    'artikel_published' => 0,
    'artikel_draft' => 0,
    'galeri_published' => 0,
    'total_views_berita' => 0,
    'total_views_artikel' => 0,
    'total_admin' => 0
];

$berita_terbaru = [];
$artikel_terbaru = [];
$galeri_terbaru = [];
$activities = [];
$error_message = '';

try {
    // Total berita
    $result = $conn->query("SELECT COUNT(*) as total FROM berita");
    if ($result) {
        $stats['total_berita'] = $result->fetch_assoc()['total'];
    }
    
    // Total artikel
    $result = $conn->query("SELECT COUNT(*) as total FROM artikel");
    if ($result) {
        $stats['total_artikel'] = $result->fetch_assoc()['total'];
    }
    
    // Total galeri - Check if table exists first
    $table_check = $conn->query("SHOW TABLES LIKE 'galeri'");
    if ($table_check && $table_check->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as total FROM galeri");
        if ($result) {
            $stats['total_galeri'] = $result->fetch_assoc()['total'];
        }
    }
    
    // Berita status - Check if status column exists
    $columns_check = $conn->query("SHOW COLUMNS FROM berita LIKE 'status'");
    if ($columns_check && $columns_check->num_rows > 0) {
        $result = $conn->query("SELECT status, COUNT(*) as total FROM berita GROUP BY status");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['berita_' . $row['status']] = $row['total'];
            }
        }
    } else {
        // If no status column, assume all are published
        $stats['berita_published'] = $stats['total_berita'];
        $stats['berita_draft'] = 0;
    }
    
    // Artikel status - Check if status column exists
    $columns_check = $conn->query("SHOW COLUMNS FROM artikel LIKE 'status'");
    if ($columns_check && $columns_check->num_rows > 0) {
        $result = $conn->query("SELECT status, COUNT(*) as total FROM artikel GROUP BY status");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stats['artikel_' . $row['status']] = $row['total'];
            }
        }
    } else {
        // If no status column, assume all are published
        $stats['artikel_published'] = $stats['total_artikel'];
        $stats['artikel_draft'] = 0;
    }
    
    // Galeri status
    $galeri_check = $conn->query("SHOW TABLES LIKE 'galeri'");
    if ($galeri_check && $galeri_check->num_rows > 0) {
        $status_check = $conn->query("SHOW COLUMNS FROM galeri LIKE 'status'");
        if ($status_check && $status_check->num_rows > 0) {
            $result = $conn->query("SELECT status, COUNT(*) as total FROM galeri GROUP BY status");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $stats['galeri_' . $row['status']] = $row['total'];
                }
            }
        } else {
            $stats['galeri_published'] = $stats['total_galeri'];
        }
    }
    
    // Total views - Check if views column exists
    $views_check = $conn->query("SHOW COLUMNS FROM berita LIKE 'views'");
    if ($views_check && $views_check->num_rows > 0) {
        $result = $conn->query("SELECT SUM(views) as total FROM berita WHERE 1=1");
        if ($result) {
            $stats['total_views_berita'] = $result->fetch_assoc()['total'] ?? 0;
        }
    }
    
    $views_check = $conn->query("SHOW COLUMNS FROM artikel LIKE 'views'");
    if ($views_check && $views_check->num_rows > 0) {
        $result = $conn->query("SELECT SUM(views) as total FROM artikel WHERE 1=1");
        if ($result) {
            $stats['total_views_artikel'] = $result->fetch_assoc()['total'] ?? 0;
        }
    }
    
    // FIXED: Total admin - Menggunakan status 'aktif' sesuai dengan login.php
    $admin_check = $conn->query("SHOW TABLES LIKE 'admin'");
    if ($admin_check && $admin_check->num_rows > 0) {
        $status_check = $conn->query("SHOW COLUMNS FROM admin LIKE 'status'");
        if ($status_check && $status_check->num_rows > 0) {
            // Gunakan 'aktif' bukan 'active' untuk konsistensi dengan login.php
            $result = $conn->query("SELECT COUNT(*) as total FROM admin WHERE status = 'aktif'");
        } else {
            $result = $conn->query("SELECT COUNT(*) as total FROM admin");
        }
        if ($result) {
            $stats['total_admin'] = $result->fetch_assoc()['total'];
        }
    }
    
    // Berita terbaru
    $result = $conn->query("SELECT * FROM berita ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $berita_terbaru[] = $row;
        }
    }
    
    // Artikel terbaru
    $result = $conn->query("SELECT * FROM artikel ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $artikel_terbaru[] = $row;
        }
    }
    
    // Galeri terbaru
    $galeri_check = $conn->query("SHOW TABLES LIKE 'galeri'");
    if ($galeri_check && $galeri_check->num_rows > 0) {
        $result = $conn->query("SELECT * FROM galeri ORDER BY created_at DESC LIMIT 5");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $galeri_terbaru[] = $row;
            }
        }
    }
    
    // FIXED: Activity log - Menggunakan tabel login_attempts sebagai fallback
    $activity_check = $conn->query("SHOW TABLES LIKE 'activity_log'");
    if ($activity_check && $activity_check->num_rows > 0) {
        $result = $conn->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 10");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
        }
    } else {
        // Fallback: Gunakan login_attempts sebagai activity log
        $login_attempts_check = $conn->query("SHOW TABLES LIKE 'login_attempts'");
        if ($login_attempts_check && $login_attempts_check->num_rows > 0) {
            $result = $conn->query("SELECT 
                CASE 
                    WHEN success = 1 THEN 'login'
                    ELSE 'login_failed'
                END as type,
                CASE 
                    WHEN success = 1 THEN CONCAT('Berhasil login dari IP: ', ip_address)
                    ELSE CONCAT('Gagal login dari IP: ', ip_address, ' (Username: ', COALESCE(username, 'Unknown'), ')')
                END as description,
                COALESCE(username, 'Unknown') as user_name,
                attempt_time as created_at
                FROM login_attempts 
                ORDER BY attempt_time DESC 
                LIMIT 10");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $activities[] = $row;
                }
            }
        }
        
        // Jika tidak ada data, buat dummy activity
        if (empty($activities)) {
            $activities = [
                [
                    'type' => 'login',
                    'description' => 'Admin login to dashboard',
                    'user_name' => $_SESSION['admin_nama'] ?? $_SESSION['admin_username'] ?? 'Admin',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
        }
    }
    
} catch (Exception $e) {
    $error_message = "Error mengambil data: " . $e->getMessage();
    error_log("Dashboard Error: " . $e->getMessage());
}

// Helper functions
function getActivityIcon($type) {
    switch ($type) {
        case 'create': return 'plus';
        case 'update': return 'edit';
        case 'delete': return 'trash';
        case 'login': return 'sign-in-alt';
        case 'login_failed': return 'exclamation-triangle';
        case 'logout': return 'sign-out-alt';
        default: return 'info';
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit yang lalu';
    if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
    return date('M j, Y', strtotime($datetime));
}

// Format tanggal Indonesia
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

// Safe admin name display
$admin_name = $_SESSION['admin_nama'] ?? $_SESSION['admin_username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - UKM Madani</title>
    
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

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background: var(--primary-color);
            color: white;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            background: rgba(0, 0, 0, 0.1);
        }

        .sidebar-header .logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            border: 3px solid var(--secondary-color);
        }

        .sidebar-header h4 {
            font-family: 'Amiri', serif;
            font-size: 1.3rem;
            margin-bottom: 5px;
            color: var(--secondary-color);
            font-weight: 700;
        }

        .sidebar-header p {
            font-size: 0.85rem;
            opacity: 0.8;
            font-weight: 300;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu .menu-header {
            padding: 15px 20px 10px;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 600;
            letter-spacing: 1px;
        }

        .sidebar-menu li {
            margin-bottom: 2px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            position: relative;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: var(--secondary-color);
            transform: translateX(5px);
        }

        .sidebar-menu a i {
            width: 25px;
            margin-right: 15px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar-menu a .badge {
            margin-left: auto;
            background: var(--danger-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            transition: all 0.3s ease;
            background: var(--light-color);
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.3rem;
            color: var(--text-dark);
            cursor: pointer;
            padding: 8px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: var(--light-color);
            color: var(--primary-color);
        }

        .header-left h1 {
            color: var(--primary-color);
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0;
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

        .header-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 10px 40px 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 25px;
            width: 300px;
            transition: all 0.3s ease;
            background: white;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            width: 350px;
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .notifications {
            position: relative;
            cursor: pointer;
        }

        .notifications i {
            font-size: 1.3rem;
            color: var(--text-dark);
            transition: color 0.3s ease;
        }

        .notifications:hover i {
            color: var(--primary-color);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .user-menu:hover {
            background: var(--light-color);
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            border: 3px solid white;
            box-shadow: var(--shadow);
        }

        .user-info h6 {
            margin: 0;
            font-weight: 600;
            color: var(--text-dark);
        }

        .user-info p {
            margin: 0;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .logout-btn {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Dashboard Content */
        .dashboard-content {
            padding: 30px;
        }

        .welcome-section {
            background: var(--gradient);
            color: white;
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: 
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(212, 175, 55, 0.1) 0%, transparent 50%);
            border-radius: 50%;
            transform: translate(50px, -50px);
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-content h2 {
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .welcome-content p {
            opacity: 0.9;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .welcome-stats {
            display: flex;
            gap: 30px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .welcome-stat {
            text-align: center;
        }

        .welcome-stat .number {
            font-size: 2rem;
            font-weight: 800;
            display: block;
        }

        .welcome-stat .label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* UKM Info Section - New */
        .ukm-info-section {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .ukm-info-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient);
        }

        .ukm-info-content {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 30px;
            align-items: center;
        }

        .ukm-info-text h3 {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ukm-info-text p {
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .kenalan-btn {
            background: var(--secondary-color);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
        }

        .kenalan-btn:hover {
            background: #b8941f;
            transform: translateY(-2px);
            box-shadow: var(--shadow);
            color: white;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
        }

        .stat-card.success::before {
            background: var(--success-color);
        }

        .stat-card.warning::before {
            background: var(--warning-color);
        }

        .stat-card.info::before {
            background: var(--info-color);
        }

        .stat-card.danger::before {
            background: var(--danger-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .stat-card .stat-title {
            font-size: 0.9rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-card .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-card .stat-icon.primary {
            background: linear-gradient(135deg, var(--primary-color), #2d8659);
        }

        .stat-card .stat-icon.success {
            background: linear-gradient(135deg, var(--success-color), #20c997);
        }

        .stat-card .stat-icon.warning {
            background: linear-gradient(135deg, var(--warning-color), #fd7e14);
        }

        .stat-card .stat-icon.info {
            background: linear-gradient(135deg, var(--info-color), #6610f2);
        }

        .stat-card .stat-icon.danger {
            background: linear-gradient(135deg, var(--danger-color), #e83e8c);
        }

        .stat-card .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--dark-color);
            margin-bottom: 10px;
            line-height: 1;
        }

        .stat-card .stat-label {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 15px;
        }

        .stat-card .stat-trend {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .trend-up {
            color: var(--success-color);
        }

        .trend-down {
            color: var(--danger-color);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .content-card {
            background: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .content-card .card-header {
            background: var(--primary-color);
            color: white;
            padding: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .content-card .card-header h5 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }

        .card-header .card-actions {
            display: flex;
            gap: 10px;
        }

        .card-header .btn-sm {
            padding: 5px 12px;
            font-size: 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-outline-light {
            border: 1px solid rgba(255, 255, 255, 0.5);
            color: white;
        }

        .btn-outline-light:hover {
            background: white;
            color: var(--primary-color);
        }

        .content-card .card-body {
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
        }

        .content-list {
            list-style: none;
        }

        .content-list li {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .content-list li:hover {
            background: var(--light-color);
            transform: translateX(5px);
        }

        .content-list li:last-child {
            border-bottom: none;
        }

        .content-item {
            flex: 1;
        }

        .content-item h6 {
            margin-bottom: 8px;
            color: var(--dark-color);
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.3;
        }

        .content-item p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .content-status {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-published {
            background: #d4edda;
            color: #155724;
        }

        .status-draft {
            background: #fff3cd;
            color: #856404;
        }

        .status-pending {
            background: #d1ecf1;
            color: #0c5460;
        }

        .empty-state {
            text-align: center;
            padding: 50px 25px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h6 {
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        /* Quick Actions */
        .quick-actions {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .quick-actions h4 {
            color: var(--primary-color);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 25px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-align: left;
        }

        .action-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        .action-btn.secondary {
            background: var(--info-color);
        }

        .action-btn.success {
            background: var(--success-color);
        }

        .action-btn.warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .action-btn i {
            font-size: 1.2rem;
        }

        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: var(--white);
            padding: 30px;
            border-radius: 15px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .chart-card h5 {
            color: var(--primary-color);
            margin-bottom: 25px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

       /* Activity Feed - Improved Version */
.activity-feed {
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    overflow: hidden;
    transition: all 0.3s ease;
}

.activity-feed:hover {
    box-shadow: var(--shadow-hover);
    transform: translateY(-2px);
}

.card-header {
    background: var(--primary-color);
    padding: 20px 25px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header h5 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-actions .btn-sm {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.card-actions .btn-sm:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-1px);
}

.card-body {
    padding: 0;
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
    background: var(--light-bg);
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

.activity-icon::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.activity-icon:hover::before {
    opacity: 1;
}

.activity-icon.create {
    background: linear-gradient(135deg, var(--success-color), #059669);
}

.activity-icon.update {
    background: linear-gradient(135deg, var(--info-color), #2563eb);
}

.activity-icon.delete {
    background: linear-gradient(135deg, var(--danger-color), #dc2626);
}

.activity-icon.login {
    background: linear-gradient(135deg, var(--primary-color), #4f46e5);
}

.activity-details {
    flex: 1;
    min-width: 0;
}

.activity-details h6 {
    margin: 0 0 8px 0;
    font-size: 0.95rem;
    color: var(--text-primary);
    font-weight: 600;
    line-height: 1.4;
}

.activity-details p {
    margin: 0;
    font-size: 0.82rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 8px;
}

.activity-details p .separator {
    width: 4px;
    height: 4px;
    background: var(--text-muted);
    border-radius: 50%;
    opacity: 0.5;
}

.user-name {
    color: var(--text-secondary);
    font-weight: 500;
}

.time-ago {
    color: var(--text-muted);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 50px 25px;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state h6 {
    font-size: 1.1rem;
    margin-bottom: 8px;
    color: var(--text-secondary);
}

.empty-state p {
    font-size: 0.9rem;
    max-width: 300px;
    margin: 0 auto;
}

/* Responsive Design */
@media (max-width: 768px) {
    .card-header {
        padding: 15px 20px;
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

    .activity-details h6 {
        font-size: 0.9rem;
    }

    .activity-details p {
        font-size: 0.8rem;
    }
}

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
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

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeaa7;
        }

        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        /* Mobile Navigation */
        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 998;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .mobile-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .content-grid,
            .charts-section {
                grid-template-columns: 1fr;
            }

            .ukm-info-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .header-right {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }

            .dashboard-content {
                padding: 20px;
            }

            .search-box input {
                width: 200px;
            }

            .search-box input:focus {
                width: 250px;
            }

            .welcome-stats {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .welcome-section {
                padding: 25px 20px;
            }

            .stat-card {
                padding: 20px;
            }

            .quick-actions,
            .ukm-info-section {
                padding: 20px;
            }

            .content-card .card-header {
                padding: 20px;
            }

            .header {
                padding: 15px;
            }

            .dashboard-content {
                padding: 15px;
            }

            .search-box {
                width: 100%;
            }

            .search-box input {
                width: 100%;
                min-width: 200px;
            }

            .header-right {
                flex-direction: column;
                gap: 15px;
            }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    </style>
</head>
<body>
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">            
                <img src="../assets/images/logo-madani.png" alt="Logo UKM Madani" style="width: 100%; height: 100%; object-fit: contain; border-radius: 50%;">
            </div>
            <h4>UKM MADANI</h4>
            <p>Admin Panel</p>
        </div>
        
        <ul class="sidebar-menu">
            <li class="menu-header">Main</li>
            <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            
            <li class="menu-header">Content Management</li>
            <li><a href="berita/index.php"><i class="fas fa-newspaper"></i> Berita <span class="badge"><?= $stats['total_berita'] ?></span></a></li>
            <li><a href="artikel/index.php"><i class="fas fa-pen-fancy"></i> Artikel <span class="badge"><?= $stats['total_artikel'] ?></span></a></li>
            <li><a href="galeri/index.php"><i class="fas fa-images"></i> Galeri <span class="badge"><?= $stats['total_galeri'] ?></span></a></li>
            
            <li class="menu-header">User Management</li>
            <li><a href="admin/index.php"><i class="fas fa-user-shield"></i> Admin Users</a></li>
            <li><a href="members/index.php"><i class="fas fa-users"></i> Members</a></li>
            
            <li class="menu-header">Website</li>
            <li><a href="pages/index.php"><i class="fas fa-file-alt"></i> Pages</a></li>
            <li><a href="menu/index.php"><i class="fas fa-bars"></i> Menu</a></li>
            <li><a href="settings/index.php"><i class="fas fa-cog"></i> Settings</a></li>
            
            <li class="menu-header">Reports</li>
            <li><a href="analytics/index.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
            <li><a href="reports/index.php"><i class="fas fa-file-pdf"></i> Reports</a></li>
            <li><a href="backup/index.php"><i class="fas fa-database"></i> Backup</a></li>
            
            <li class="menu-header">External</li>
            <li><a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Website</a></li>
            <li><a href="help/index.php"><i class="fas fa-question-circle"></i> Help & Support</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                    <div class="breadcrumb">
                        <a href="dashboard.php">Home</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>Dashboard</span>
                    </div>
                </div>
            </div>
            
            <div class="header-right">
                <div class="search-box">
                    <input type="text" placeholder="Cari konten..." id="globalSearch">
                    <i class="fas fa-search"></i>
                </div>
                
                <div class="notifications" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                
                <div class="user-menu">
                    <div class="user-avatar">
                        <?= strtoupper(substr($admin_name, 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <h6><?= htmlspecialchars($admin_name) ?></h6>
                        <p>Administrator</p>
                    </div>
                </div>
                
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <h2>Selamat Datang, <?= htmlspecialchars($admin_name) ?>!</h2>
                    <p>Kelola konten website UKM Madani dengan mudah melalui admin panel yang lengkap ini.</p>
                    
                    <div class="welcome-stats">
                        <div class="welcome-stat">
                            <span class="number"><?= date('d') ?></span>
                            <span class="label"><?= date('M Y') ?></span>
                        </div>
                        <div class="welcome-stat">
                            <span class="number"><?= $stats['total_berita'] + $stats['total_artikel'] ?></span>
                            <span class="label">Total Konten</span>
                        </div>
                        <div class="welcome-stat">
                            <span class="number"><?= number_format($stats['total_views_berita'] + $stats['total_views_artikel']) ?></span>
                            <span class="label">Total Views</span>
                        </div>
                        <div class="welcome-stat">
                            <span class="number"><?= $stats['total_galeri'] ?></span>
                            <span class="label">Dokumentasi</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- UKM Info Section - New -->
            <div class="ukm-info-section">
                <div class="ukm-info-content">
                    <div class="ukm-info-text">
                        <h3><i class="fas fa-mosque"></i> Tentang UKM Madani</h3>
                        <p>
                            UKM Madani adalah organisasi mahasiswa yang berfokus pada pengembangan peradaban Islam di lingkungan ITERA. 
                            Kami berkomitmen untuk membentuk generasi muslim yang berkualitas, berakhlak mulia, dan berwawasan luas 
                            melalui berbagai kegiatan dakwah, pendidikan, dan pengembangan diri.
                        </p>
                    </div>
                    <div class="ukm-info-action">
                        <a href="../index.php#about" target="_blank" class="kenalan-btn">
                            <i class="fas fa-heart"></i>
                            Kenalan Lebih Dalam
                        </a>
                    </div>
                </div>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h4><i class="fas fa-bolt"></i> Quick Actions</h4>
                <div class="action-buttons">
                    <a href="berita/create.php" class="action-btn">
                        <i class="fas fa-plus"></i>
                        <span>Tambah Berita</span>
                    </a>
                    <a href="artikel/create.php" class="action-btn secondary">
                        <i class="fas fa-pen"></i>
                        <span>Tulis Artikel</span>
                    </a>
                    <a href="galeri/create.php" class="action-btn success">
                        <i class="fas fa-camera"></i>
                        <span>Upload Dokumentasi</span>
                    </a>
                    <a href="admin/create.php" class="action-btn warning">
                        <i class="fas fa-user-plus"></i>
                        <span>Tambah Admin</span>
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Total Berita</div>
                        <div class="stat-icon primary">
                            <i class="fas fa-newspaper"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= $stats['total_berita'] ?></div>
                    <div class="stat-label">Semua berita yang dibuat</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <span>+12% dari bulan lalu</span>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-header">
                        <div class="stat-title">Berita Published</div>
                        <div class="stat-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= $stats['berita_published'] ?></div>
                    <div class="stat-label">Berita yang dipublikasi</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <span>+8% dari minggu lalu</span>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-header">
                        <div class="stat-title">Draft Berita</div>
                        <div class="stat-icon warning">
                            <i class="fas fa-edit"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= $stats['berita_draft'] ?></div>
                    <div class="stat-label">Berita dalam draft</div>
                    <div class="stat-trend">
                        <i class="fas fa-clock"></i>
                        <span>Perlu direview</span>
                    </div>
                </div>

                <div class="stat-card info">
                    <div class="stat-header">
                        <div class="stat-title">Total Artikel</div>
                        <div class="stat-icon info">
                            <i class="fas fa-pen-fancy"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= $stats['total_artikel'] ?></div>
                    <div class="stat-label">Artikel yang ditulis</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <span>+15% dari bulan lalu</span>
                    </div>
                </div>

                <div class="stat-card danger">
                    <div class="stat-header">
                        <div class="stat-title">Total Views</div>
                        <div class="stat-icon danger">
                            <i class="fas fa-eye"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= number_format($stats['total_views_berita'] + $stats['total_views_artikel']) ?></div>
                    <div class="stat-label">Views keseluruhan</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <span>+25% dari bulan lalu</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">Dokumentasi</div>
                        <div class="stat-icon primary">
                            <i class="fas fa-images"></i>
                        </div>
                    </div>
                    <div class="stat-number"><?= $stats['total_galeri'] ?></div>
                    <div class="stat-label">Galeri yang diupload</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        <span>+5% dari minggu lalu</span>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-card">
                    <h5><i class="fas fa-chart-line"></i> Content Growth</h5>
                    <canvas id="contentChart" width="400" height="200"></canvas>
                </div>
                
                <div class="chart-card">
                    <h5><i class="fas fa-chart-pie"></i> Content Distribution</h5>
                    <canvas id="distributionChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent News -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-newspaper"></i> Berita Terbaru</h5>
                        <div class="card-actions">
                            <a href="berita/index.php" class="btn-sm btn-outline-light">Lihat Semua</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($berita_terbaru) > 0): ?>
                            <ul class="content-list">
                                <?php foreach ($berita_terbaru as $berita): ?>
                                <li>
                                    <div class="content-item">
                                        <h6><?= htmlspecialchars(substr($berita['judul'], 0, 60)) ?><?= strlen($berita['judul']) > 60 ? '...' : '' ?></h6>
                                        <p>
                                            <span><i class="fas fa-user"></i> <?= htmlspecialchars($berita['penulis'] ?? 'Admin') ?></span>
                                            <span><i class="fas fa-calendar"></i> <?= formatTanggalIndonesia($berita['created_at']) ?></span>
                                            <span><i class="fas fa-eye"></i> <?= $berita['views'] ?? 0 ?> views</span>
                                        </p>
                                    </div>
                                    <span class="content-status status-<?= $berita['status'] ?? 'published' ?>">
                                        <?= ucfirst($berita['status'] ?? 'Published') ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-newspaper"></i>
                                <h6>Belum ada berita</h6>
                                <p>Mulai buat berita pertama Anda</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Articles -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-pen-fancy"></i> Artikel Terbaru</h5>
                        <div class="card-actions">
                            <a href="artikel/index.php" class="btn-sm btn-outline-light">Lihat Semua</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($artikel_terbaru) > 0): ?>
                            <ul class="content-list">
                                <?php foreach ($artikel_terbaru as $artikel): ?>
                                <li>
                                    <div class="content-item">
                                        <h6><?= htmlspecialchars(substr($artikel['judul'], 0, 60)) ?><?= strlen($artikel['judul']) > 60 ? '...' : '' ?></h6>
                                        <p>
                                            <span><i class="fas fa-user"></i> <?= htmlspecialchars($artikel['penulis'] ?? 'Admin') ?></span>
                                            <span><i class="fas fa-calendar"></i> <?= formatTanggalIndonesia($artikel['created_at']) ?></span>
                                            <span><i class="fas fa-eye"></i> <?= $artikel['views'] ?? 0 ?> views</span>
                                        </p>
                                    </div>
                                    <span class="content-status status-<?= $artikel['status'] ?? 'published' ?>">
                                        <?= ucfirst($artikel['status'] ?? 'Published') ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-pen-fancy"></i>
                                <h6>Belum ada artikel</h6>
                                <p>Mulai tulis artikel pertama Anda</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Gallery -->
                <div class="content-card">
                    <div class="card-header">
                        <h5><i class="fas fa-images"></i> Dokumentasi Terbaru</h5>
                        <div class="card-actions">
                            <a href="galeri/index.php" class="btn-sm btn-outline-light">Lihat Semua</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($galeri_terbaru) > 0): ?>
                            <ul class="content-list">
                                <?php foreach ($galeri_terbaru as $galeri): ?>
                                <li>
                                    <div class="content-item">
                                        <h6><?= htmlspecialchars(substr($galeri['judul'], 0, 60)) ?><?= strlen($galeri['judul']) > 60 ? '...' : '' ?></h6>
                                        <p>
                                            <?php if (!empty($galeri['kategori'])): ?>
                                            <span><i class="fas fa-tag"></i> <?= htmlspecialchars($galeri['kategori']) ?></span>
                                            <?php endif; ?>
                                            <span><i class="fas fa-calendar"></i> <?= formatTanggalIndonesia($galeri['tanggal_kegiatan'] ?? $galeri['created_at']) ?></span>
                                            <?php if (!empty($galeri['total_foto'])): ?>
                                            <span><i class="fas fa-images"></i> <?= $galeri['total_foto'] ?> foto</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <span class="content-status status-<?= $galeri['status'] ?? 'published' ?>">
                                        <?= ucfirst($galeri['status'] ?? 'Published') ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-images"></i>
                                <h6>Belum ada dokumentasi</h6>
                                <p>Mulai upload dokumentasi pertama Anda</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="activity-feed">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i>Last Activity</h5>
                    <div class="card-actions">
                        <a href="activity/index.php" class="btn-sm btn-outline-light">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($activities) > 0): ?>
                        <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?= $activity['type'] ?>">
                                <i class="fas fa-<?= getActivityIcon($activity['type']) ?>"></i>
                            </div>
                            <div class="activity-details">
                                <h6><?= htmlspecialchars($activity['description']) ?></h6>
                                <p>
                                    by <?= htmlspecialchars($activity['user_name'] ?? 'System') ?> • 
                                    <?= timeAgo($activity['created_at']) ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <h6>No recent activity</h6>
                            <p>Activity will appear here when actions are performed</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
            } else {
                sidebar.classList.toggle('collapsed');
                const mainContent = document.querySelector('.main-content');
                mainContent.style.marginLeft = sidebar.classList.contains('collapsed') ? '70px' : '280px';
            }
        });

        // Close sidebar on mobile when clicking outside
        document.getElementById('mobileOverlay').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            sidebar.classList.remove('show');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        // Close sidebar on mobile when clicking on nav links
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('mobileOverlay');
                    
                    sidebar.classList.remove('show');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        // Global Search
        document.getElementById('globalSearch').addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    window.location.href = `search.php?q=${encodeURIComponent(query)}`;
                }
            }
        });

        // Search icon click
        document.querySelector('.search-box i').addEventListener('click', function() {
            const searchInput = document.getElementById('globalSearch');
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `search.php?q=${encodeURIComponent(query)}`;
            }
        });

        // Content Growth Chart
        const contentCtx = document.getElementById('contentChart').getContext('2d');
        new Chart(contentCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Berita',
                    data: [12, 19, 8, 15, 28, <?= $stats['total_berita'] ?>],
                    borderColor: '#1a5f3f',
                    backgroundColor: 'rgba(26, 95, 63, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Artikel',
                    data: [5, 12, 15, 8, 22, <?= $stats['total_artikel'] ?>],
                    borderColor: '#d4af37',
                    backgroundColor: 'rgba(212, 175, 55, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Galeri',
                    data: [3, 8, 12, 6, 15, <?= $stats['total_galeri'] ?>],
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Content Distribution Chart
        const distributionCtx = document.getElementById('distributionChart').getContext('2d');
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Berita Published', 'Berita Draft', 'Artikel Published', 'Artikel Draft', 'Galeri'],
                datasets: [{
                    data: [
                        <?= $stats['berita_published'] ?>,
                        <?= $stats['berita_draft'] ?>,
                        <?= $stats['artikel_published'] ?>,
                        <?= $stats['artikel_draft'] ?>,
                        <?= $stats['total_galeri'] ?>
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107',
                        '#17a2b8',
                        '#dc3545',
                        '#6f42c1'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Real-time notifications
        function checkNotifications() {
            // Simulated notification check
            const count = Math.floor(Math.random() * 5);
            const badge = document.querySelector('.notification-badge');
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }

        // Check notifications every minute
        setInterval(checkNotifications, 60000);

        // Notification click handler
        document.querySelector('.notifications').addEventListener('click', function() {
            // Create simple notification popup
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                border-left: 4px solid var(--primary-color);
                max-width: 300px;
            `;
            notification.innerHTML = `
                <h6 style="margin: 0 0 10px 0; color: var(--primary-color);">📢 Notifikasi</h6>
                <p style="margin: 0; font-size: 0.9rem; color: var(--text-muted);">
                    Selamat datang di dashboard UKM Madani! Sistem notifikasi akan segera hadir.
                </p>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        });

        // Dynamic greeting based on time
        function updateGreeting() {
            const hour = new Date().getHours();
            const greeting = hour < 12 ? 'Selamat Pagi' : hour < 18 ? 'Selamat Siang' : 'Selamat Malam';
            const welcomeText = document.querySelector('.welcome-content h2');
            if (welcomeText) {
                const name = '<?= htmlspecialchars($admin_name) ?>';
                welcomeText.textContent = `${greeting}, ${name}!`;
            }
        }

        updateGreeting();

        // Auto-hide error messages
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

        // Smooth scroll for internal links
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

        // Add loading states to action buttons
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.href && !this.href.includes('#')) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                    this.style.pointerEvents = 'none';
                    
                    // Reset after 2 seconds in case navigation fails
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = '';
                    }, 2000);
                }
            });
        });

        // Content cards hover effects
        document.querySelectorAll('.content-list li').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.backgroundColor = 'var(--light-color)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });

        // Responsive sidebar handling
        function handleResize() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.querySelector('.main-content');
            const overlay = document.getElementById('mobileOverlay');
            
            if (window.innerWidth > 768) {
                sidebar.classList.remove('show');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                
                if (sidebar.classList.contains('collapsed')) {
                    mainContent.style.marginLeft = '70px';
                } else {
                    mainContent.style.marginLeft = '280px';
                }
            } else {
                mainContent.style.marginLeft = '0';
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize(); // Call on load

        // Initialize tooltips for icons
        document.querySelectorAll('[title]').forEach(element => {
            element.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.textContent = this.getAttribute('title');
                tooltip.style.cssText = `
                    position: absolute;
                    background: rgba(0,0,0,0.8);
                    color: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 0.8rem;
                    z-index: 10000;
                    pointer-events: none;
                    white-space: nowrap;
                `;
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.bottom + 5 + 'px';
                
                this._tooltip = tooltip;
            });
            
            element.addEventListener('mouseleave', function() {
                if (this._tooltip) {
                    this._tooltip.remove();
                    this._tooltip = null;
                }
            });
        });

        // Console welcome message
        console.log(`
        🎉 Dashboard UKM Madani berhasil dimuat!
        📊 Total Konten: ${<?= $stats['total_berita'] + $stats['total_artikel'] + $stats['total_galeri'] ?>}
        👁️ Total Views: ${<?= $stats['total_views_berita'] + $stats['total_views_artikel'] ?>}
        
        Selamat mengelola website UKM Madani! 🕌✨
        `);
    </script>
</body>
</html>