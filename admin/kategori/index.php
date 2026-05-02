<?php
// admin/kategori/index.php
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

$admin_name = $_SESSION['admin_nama'] ?? $_SESSION['admin_username'] ?? 'Admin';

// Get success message from session
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Initialize variables for filtering and pagination
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $nama = trim($_POST['nama']);
                    $deskripsi = trim($_POST['deskripsi']);
                    $warna = trim($_POST['warna']) ?: '#1a5f3f';
                    
                    // Generate slug
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nama)));
                    $slug = trim($slug, '-');
                    
                    if (empty($nama)) {
                        throw new Exception('Nama kategori wajib diisi!');
                    }
                    
                    // Check if nama or slug already exists
                    $check_stmt = $conn->prepare("SELECT id FROM artikel_kategori WHERE nama = ? OR slug = ?");
                    $check_stmt->bind_param("ss", $nama, $slug);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        throw new Exception('Kategori dengan nama tersebut sudah ada!');
                    }
                    
                    $stmt = $conn->prepare("INSERT INTO artikel_kategori (nama, slug, deskripsi, warna, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->bind_param("ssss", $nama, $slug, $deskripsi, $warna);
                    
                    if ($stmt->execute()) {
                        // Log activity
                        $activity_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'create', ?, ?, NOW())");
                        $activity_desc = "Membuat kategori artikel: " . $nama;
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                        
                        $activity_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $activity_desc, $ip_address);
                        $activity_stmt->execute();
                        
                        $_SESSION['success_message'] = "Kategori '$nama' berhasil dibuat!";
                    } else {
                        throw new Exception('Gagal menyimpan kategori: ' . $conn->error);
                    }
                    break;
                    
                case 'update':
                    $id = (int)$_POST['id'];
                    $nama = trim($_POST['nama']);
                    $deskripsi = trim($_POST['deskripsi']);
                    $warna = trim($_POST['warna']) ?: '#1a5f3f';
                    
                    // Generate slug
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nama)));
                    $slug = trim($slug, '-');
                    
                    if (empty($nama)) {
                        throw new Exception('Nama kategori wajib diisi!');
                    }
                    
                    // Check if nama or slug already exists (exclude current record)
                    $check_stmt = $conn->prepare("SELECT id FROM artikel_kategori WHERE (nama = ? OR slug = ?) AND id != ?");
                    $check_stmt->bind_param("ssi", $nama, $slug, $id);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        throw new Exception('Kategori dengan nama tersebut sudah ada!');
                    }
                    
                    $stmt = $conn->prepare("UPDATE artikel_kategori SET nama = ?, slug = ?, deskripsi = ?, warna = ? WHERE id = ?");
                    $stmt->bind_param("ssssi", $nama, $slug, $deskripsi, $warna, $id);
                    
                    if ($stmt->execute()) {
                        // Log activity
                        $activity_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'update', ?, ?, NOW())");
                        $activity_desc = "Mengupdate kategori artikel: " . $nama;
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                        
                        $activity_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $activity_desc, $ip_address);
                        $activity_stmt->execute();
                        
                        $_SESSION['success_message'] = "Kategori '$nama' berhasil diupdate!";
                    } else {
                        throw new Exception('Gagal mengupdate kategori: ' . $conn->error);
                    }
                    break;
                    
                case 'delete':
                    $id = (int)$_POST['id'];
                    
                    // Get kategori name for logging
                    $get_stmt = $conn->prepare("SELECT nama FROM artikel_kategori WHERE id = ?");
                    $get_stmt->bind_param("i", $id);
                    $get_stmt->execute();
                    $kategori_result = $get_stmt->get_result();
                    $kategori_name = $kategori_result->fetch_assoc()['nama'] ?? 'Unknown';
                    
                    // Check if kategori is being used by any artikel
                    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM artikel WHERE kategori = ?");
                    $check_stmt->bind_param("s", $kategori_name);
                    $check_stmt->execute();
                    $usage_result = $check_stmt->get_result();
                    $usage_count = $usage_result->fetch_assoc()['count'];
                    
                    if ($usage_count > 0) {
                        throw new Exception("Kategori '$kategori_name' tidak dapat dihapus karena masih digunakan oleh $usage_count artikel!");
                    }
                    
                    $stmt = $conn->prepare("DELETE FROM artikel_kategori WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        // Log activity
                        $activity_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'delete', ?, ?, NOW())");
                        $activity_desc = "Menghapus kategori artikel: " . $kategori_name;
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                        
                        $activity_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $activity_desc, $ip_address);
                        $activity_stmt->execute();
                        
                        $_SESSION['success_message'] = "Kategori '$kategori_name' berhasil dihapus!";
                    } else {
                        throw new Exception('Gagal menghapus kategori: ' . $conn->error);
                    }
                    break;
                    
                case 'bulk_delete':
                    $selected_ids = $_POST['selected_ids'] ?? [];
                    
                    if (!empty($selected_ids) && is_array($selected_ids)) {
                        $deleted_count = 0;
                        $errors = [];
                        
                        foreach ($selected_ids as $id) {
                            $id = (int)$id;
                            
                            // Get kategori name
                            $get_stmt = $conn->prepare("SELECT nama FROM artikel_kategori WHERE id = ?");
                            $get_stmt->bind_param("i", $id);
                            $get_stmt->execute();
                            $kategori_result = $get_stmt->get_result();
                            $kategori_data = $kategori_result->fetch_assoc();
                            
                            if (!$kategori_data) continue;
                            
                            $kategori_name = $kategori_data['nama'];
                            
                            // Check usage
                            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM artikel WHERE kategori = ?");
                            $check_stmt->bind_param("s", $kategori_name);
                            $check_stmt->execute();
                            $usage_result = $check_stmt->get_result();
                            $usage_count = $usage_result->fetch_assoc()['count'];
                            
                            if ($usage_count > 0) {
                                $errors[] = "Kategori '$kategori_name' digunakan oleh $usage_count artikel";
                                continue;
                            }
                            
                            // Delete
                            $stmt = $conn->prepare("DELETE FROM artikel_kategori WHERE id = ?");
                            $stmt->bind_param("i", $id);
                            
                            if ($stmt->execute()) {
                                $deleted_count++;
                            }
                        }
                        
                        if ($deleted_count > 0) {
                            // Log activity
                            $activity_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'bulk_delete', ?, ?, NOW())");
                            $activity_desc = "Menghapus $deleted_count kategori artikel secara bulk";
                            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                            
                            $activity_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $activity_desc, $ip_address);
                            $activity_stmt->execute();
                            
                            $_SESSION['success_message'] = "$deleted_count kategori berhasil dihapus!";
                        }
                        
                        if (!empty($errors)) {
                            $_SESSION['error_message'] = implode(', ', $errors);
                        }
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header('Location: index.php');
    exit;
}

// Build WHERE clause for search
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(nama LIKE ? OR deskripsi LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $param_types .= 'ss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Validate sort and order
$allowed_sorts = ['nama', 'deskripsi', 'created_at'];
$sort = in_array($sort, $allowed_sorts) ? $sort : 'created_at';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM artikel_kategori $where_clause";
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

// Get kategori data with usage count
$sql = "SELECT k.*, 
        (SELECT COUNT(*) FROM artikel WHERE kategori = k.nama) as artikel_count
        FROM artikel_kategori k 
        $where_clause 
        ORDER BY $sort $order 
        LIMIT ? OFFSET ?";

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
    $simple_sql = "SELECT k.*, 
                   (SELECT COUNT(*) FROM artikel WHERE kategori = k.nama) as artikel_count
                   FROM artikel_kategori k 
                   ORDER BY $sort $order 
                   LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($simple_sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

$kategori_items = [];
while ($row = $result->fetch_assoc()) {
    $kategori_items[] = $row;
}

// Get statistics
$stats = [
    'total' => $total_records,
    'used' => 0,
    'unused' => 0
];

try {
    $stats_result = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN (SELECT COUNT(*) FROM artikel WHERE kategori = artikel_kategori.nama) > 0 THEN 1 ELSE 0 END) as used,
            SUM(CASE WHEN (SELECT COUNT(*) FROM artikel WHERE kategori = artikel_kategori.nama) = 0 THEN 1 ELSE 0 END) as unused
        FROM artikel_kategori
    ");
    $stats = $stats_result->fetch_assoc();
} catch (Exception $e) {
    // Handle error silently
}

$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori Artikel - UKM Madani Admin</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
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
            border-radius: 15px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
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

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

        .stat-card.used {
            border-left-color: var(--success-color);
        }

        .stat-card.unused {
            border-left-color: var(--warning-color);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-card.used .stat-number {
            color: var(--success-color);
        }

        .stat-card.unused .stat-number {
            color: var(--warning-color);
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

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 25px;
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
            box-shadow: 0 4px 15px rgba(26, 95, 63, 0.4);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-secondary {
            background: var(--text-muted);
            color: white;
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
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
            grid-template-columns: 2fr auto auto;
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

        .filter-group input {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: block;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: modalShow 0.3s ease-out;
        }

        @keyframes modalShow {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            background: var(--gradient);
            color: white;
            padding: 25px 30px;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .close {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: var(--secondary-color);
        }

        .modal-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .color-input-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .color-input-wrapper input[type="color"] {
            width: 50px;
            height: 45px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .color-input-wrapper input[type="text"] {
            flex: 1;
        }

        .modal-footer {
            padding: 20px 30px 30px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-header {
            background: var(--light-color);
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            background: var(--light-color);
            font-weight: 700;
            color: var(--text-dark);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: background-color 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(26, 95, 63, 0.05);
        }

        .kategori-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            border: 2px solid var(--border-color);
        }

        .kategori-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .kategori-description {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .usage-badge {
            background: var(--success-color);
            color: white;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .usage-badge.unused {
            background: var(--text-muted);
        }

        .action-buttons {
            display: flex;
            gap: 5px;
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

            .stats-container {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .table-container {
                overflow-x: auto;
            }

            .table {
                min-width: 600px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
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
                    <i class="fas fa-folder-open"></i>
                    Kelola Kategori Artikel
                </h1>
                <div class="breadcrumb">
                    <a href="../dashboard.php">Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="../artikel/index.php">Artikel</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Kategori</span>
                </div>
            </div>
            <div class="header-actions">
                <button onclick="openCreateModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Tambah Kategori
                </button>
                <a href="../artikel/index.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke Artikel
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
                <div class="stat-label">Total Kategori</div>
                <i class="fas fa-folder stat-icon"></i>
            </div>
            <div class="stat-card used">
                <div class="stat-number"><?= number_format($stats['used']) ?></div>
                <div class="stat-label">Kategori Terpakai</div>
                <i class="fas fa-check-circle stat-icon"></i>
            </div>
            <div class="stat-card unused">
                <div class="stat-number"><?= number_format($stats['unused']) ?></div>
                <div class="stat-label">Kategori Kosong</div>
                <i class="fas fa-exclamation-circle stat-icon"></i>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Cari Kategori</label>
                        <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Cari nama atau deskripsi kategori...">
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
                        <span id="selectedCount">0</span> kategori dipilih
                    </div>
                    <div>
                        <button type="submit" name="action" value="bulk_delete" class="btn btn-danger btn-sm" 
                                onclick="return confirm('Apakah Anda yakin ingin menghapus kategori yang dipilih? Kategori yang sedang digunakan tidak akan dihapus.')">
                            <i class="fas fa-trash"></i>
                            Hapus yang Dipilih
                        </button>
                    </div>
                </div>
                <div id="selectedIds"></div>
            </form>
        </div>

        <!-- Table -->
        <?php if (empty($kategori_items)): ?>
            <div class="table-container">
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <h3>Belum Ada Kategori</h3>
                    <?php if (!empty($search)): ?>
                        <p>Tidak ditemukan kategori yang sesuai dengan pencarian.</p>
                        <a href="?" class="btn btn-primary">Lihat Semua Kategori</a>
                    <?php else: ?>
                        <p>Mulai dengan membuat kategori pertama untuk mengorganisir artikel.</p>
                        <button onclick="openCreateModal()" class="btn btn-primary">Tambah Kategori</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <div class="table-header">
                    <h3>Daftar Kategori Artikel</h3>
                    <div>
                        <?php if ($total_records > 0): ?>
                            Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $total_records) ?> dari <?= $total_records ?> kategori
                        <?php endif; ?>
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th width="50">Warna</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th width="120">Penggunaan</th>
                            <th width="180">Dibuat</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kategori_items as $item): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="<?= $item['id'] ?>" 
                                           <?= $item['artikel_count'] > 0 ? 'disabled title="Kategori sedang digunakan"' : '' ?>>
                                </td>
                                <td>
                                    <div class="kategori-color" style="background-color: <?= htmlspecialchars($item['warna']) ?>"></div>
                                </td>
                                <td>
                                    <div class="kategori-name"><?= htmlspecialchars($item['nama']) ?></div>
                                    <div class="kategori-description">
                                        Slug: <?= htmlspecialchars($item['slug']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?= !empty($item['deskripsi']) ? htmlspecialchars($item['deskripsi']) : '<em>Tidak ada deskripsi</em>' ?>
                                </td>
                                <td>
                                    <span class="usage-badge <?= $item['artikel_count'] == 0 ? 'unused' : '' ?>">
                                        <?= $item['artikel_count'] ?> artikel
                                    </span>
                                </td>
                                <td>
                                    <?= date('d M Y', strtotime($item['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($item)) ?>)" 
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="deleteKategori(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['nama'])) ?>', <?= $item['artikel_count'] ?>)" 
                                                title="Hapus"
                                                <?= $item['artikel_count'] > 0 ? 'disabled' : '' ?>>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

    <!-- Create/Edit Modal -->
    <div id="kategoriModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Tambah Kategori Baru</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="kategoriForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="kategoriId">
                    
                    <div class="form-group">
                        <label for="nama">Nama Kategori *</label>
                        <input type="text" id="nama" name="nama" required maxlength="100" 
                               placeholder="Masukkan nama kategori">
                    </div>
                    
                    <div class="form-group">
                        <label for="warna">Warna Kategori</label>
                        <div class="color-input-wrapper">
                            <input type="color" id="warna" name="warna" value="#1a5f3f">
                            <input type="text" id="warnaText" placeholder="#1a5f3f" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="deskripsi">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" rows="3" 
                                  placeholder="Deskripsi singkat tentang kategori ini (opsional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script>
        // Modal functionality
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Kategori Baru';
            document.getElementById('formAction').value = 'create';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Simpan';
            document.getElementById('kategoriForm').reset();
            document.getElementById('warna').value = '#1a5f3f';
            document.getElementById('warnaText').value = '#1a5f3f';
            document.getElementById('kategoriModal').classList.add('show');
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').textContent = 'Edit Kategori';
            document.getElementById('formAction').value = 'update';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update';
            document.getElementById('kategoriId').value = data.id;
            document.getElementById('nama').value = data.nama;
            document.getElementById('deskripsi').value = data.deskripsi || '';
            document.getElementById('warna').value = data.warna;
            document.getElementById('warnaText').value = data.warna;
            document.getElementById('kategoriModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('kategoriModal').classList.remove('show');
        }

        // Color picker sync
        document.getElementById('warna').addEventListener('input', function() {
            document.getElementById('warnaText').value = this.value;
        });

        document.getElementById('warnaText').addEventListener('input', function() {
            const color = this.value;
            if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
                document.getElementById('warna').value = color;
            }
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('kategoriModal');
            if (event.target === modal) {
                closeModal();
            }
        });

        // Delete kategori
        function deleteKategori(id, nama, artikelCount) {
            if (artikelCount > 0) {
                alert(`Kategori "${nama}" tidak dapat dihapus karena masih digunakan oleh ${artikelCount} artikel!`);
                return;
            }
            
            if (confirm(`Apakah Anda yakin ingin menghapus kategori "${nama}"? Tindakan ini tidak dapat dibatalkan.`)) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

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
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            
            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(checkbox => {
                        if (!checkbox.disabled) {
                            checkbox.checked = this.checked;
                            const id = parseInt(checkbox.value);
                            
                            if (this.checked) {
                                selectedItems.add(id);
                            } else {
                                selectedItems.delete(id);
                            }
                        }
                    });
                    updateBulkActions();
                });
            }
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const id = parseInt(this.value);
                    
                    if (this.checked) {
                        selectedItems.add(id);
                    } else {
                        selectedItems.delete(id);
                        if (selectAll) selectAll.checked = false;
                    }
                    
                    updateBulkActions();
                    
                    // Update select all checkbox
                    if (selectAll) {
                        const enabledCheckboxes = Array.from(checkboxes).filter(cb => !cb.disabled);
                        const checkedEnabledCheckboxes = enabledCheckboxes.filter(cb => cb.checked);
                        selectAll.checked = enabledCheckboxes.length > 0 && checkedEnabledCheckboxes.length === enabledCheckboxes.length;
                    }
                });
            });
        });

        // Auto-submit search with debounce
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    document.getElementById('filterForm').submit();
                }
            }, 500);
        });

        // Form validation
        document.getElementById('kategoriForm').addEventListener('submit', function(e) {
            const nama = document.getElementById('nama').value.trim();
            
            if (!nama) {
                e.preventDefault();
                alert('Nama kategori wajib diisi!');
                return;
            }
            
            if (nama.length > 100) {
                e.preventDefault();
                alert('Nama kategori terlalu panjang! Maksimal 100 karakter.');
                return;
            }
            
            // Disable submit button to prevent double submission
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        });

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

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+N for new category
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                openCreateModal();
            }
            
            // Escape to close modal
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        console.log('🎉 Kategori management loaded successfully!');
        console.log('💡 Tips: Gunakan Ctrl+N untuk tambah kategori baru, Escape untuk tutup modal');
    </script>
</body>
</html>