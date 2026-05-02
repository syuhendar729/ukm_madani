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

$admin_name = $_SESSION['admin_nama'] ?? $_SESSION['admin_username'] ?? 'Admin';

// Get article ID
$artikel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$artikel_id) {
    header('Location: index.php?error=article_not_found');
    exit;
}

// Get article data
$artikel = null;
try {
    $stmt = $conn->prepare("SELECT * FROM artikel WHERE id = ?");
    $stmt->bind_param("i", $artikel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $artikel = $result->fetch_assoc();
    
    if (!$artikel) {
        header('Location: index.php?error=article_not_found');
        exit;
    }
} catch (Exception $e) {
    header('Location: index.php?error=database_error');
    exit;
}

// Get categories
$categories = [];
try {
    $result = $conn->query("SELECT * FROM artikel_kategori ORDER BY nama ASC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
} catch (Exception $e) {
    $error_message = "Error mengambil kategori: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $judul = trim($_POST['judul']);
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $judul));
        $konten = $_POST['konten'];
        $kategori = $_POST['kategori'];
        $tags = trim($_POST['tags']);
        $excerpt = trim($_POST['excerpt']);
        $tanggal_publish = $_POST['tanggal_publish'];
        $status = $_POST['status'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        $gambar = $artikel['gambar']; // Keep existing image
        $gambar_alt = $artikel['gambar_alt']; // Keep existing alt text
        
        // Handle image upload
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../assets/uploads/artikel/';
            
            // Create directory if not exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_info = pathinfo($_FILES['gambar']['name']);
            $extension = strtolower($file_info['extension']);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array($extension, $allowed_extensions)) {
                // Check file size (5MB max)
                if ($_FILES['gambar']['size'] > 5 * 1024 * 1024) {
                    throw new Exception("Ukuran file terlalu besar. Maksimal 5MB.");
                }
                
                $new_filename = date('Y-m-d_H-i-s_') . uniqid() . '.' . $extension;
                $file_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['gambar']['tmp_name'], $file_path)) {
                    // Delete old image if exists
                    if (!empty($artikel['gambar']) && file_exists($upload_dir . $artikel['gambar'])) {
                        unlink($upload_dir . $artikel['gambar']);
                    }
                    
                    $gambar = $new_filename;
                    $gambar_alt = $_POST['gambar_alt'] ?? $judul;
                } else {
                    throw new Exception("Gagal mengupload gambar.");
                }
            } else {
                throw new Exception("Format file tidak didukung. Gunakan JPG, PNG, WEBP, atau GIF.");
            }
        } else {
            // Update alt text even if no new image
            $gambar_alt = $_POST['gambar_alt'] ?? $artikel['gambar_alt'];
        }
        
        // Auto-generate excerpt if empty
        if (empty($excerpt)) {
            $excerpt = substr(strip_tags($konten), 0, 200) . '...';
        }
        
        // Check if slug exists (excluding current article)
        $check_stmt = $conn->prepare("SELECT id FROM artikel WHERE slug = ? AND id != ?");
        $check_stmt->bind_param("si", $slug, $artikel_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $slug = $slug . '-' . time();
        }
        
        // Update article
        $stmt = $conn->prepare("UPDATE artikel SET judul = ?, slug = ?, konten = ?, gambar = ?, gambar_alt = ?, kategori = ?, tags = ?, excerpt = ?, featured = ?, tanggal_publish = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssssssssssi", $judul, $slug, $konten, $gambar, $gambar_alt, $kategori, $tags, $excerpt, $featured, $tanggal_publish, $status, $artikel_id);
        
        if ($stmt->execute()) {
            // Log activity
            $description = "Mengedit artikel: $judul";
            $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'update', ?, ?, NOW())");
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $log_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $description, $ip_address);
            $log_stmt->execute();
            
            $_SESSION['success_message'] = "Artikel berhasil diperbarui!";
            
            // Redirect immediately
            header('Location: index.php');
            exit;
            
        } else {
            throw new Exception("Gagal memperbarui artikel: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        
        // Delete uploaded file if database update failed
        if (!empty($new_filename) && file_exists($upload_dir . $new_filename)) {
            unlink($upload_dir . $new_filename);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Artikel - UKM Madani Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Quill.js Rich Text Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    
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
            --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.15);
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

        .breadcrumb a:hover {
            text-decoration: underline;
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

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .form-header {
            background: var(--gradient);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .form-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .form-body {
            padding: 40px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .required::after {
            content: ' *';
            color: var(--danger-color);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
        }

        .form-control.large {
            padding: 15px 20px;
            font-size: 1.2rem;
            font-weight: 500;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        /* Rich Text Editor */
        .editor-container {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            transition: border-color 0.3s ease;
        }

        .editor-container:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
        }

        .ql-toolbar {
            border: none;
            border-bottom: 1px solid var(--border-color);
            background: var(--light-color);
            padding: 15px;
        }

.ql-container {
    border: none;
    min-height: 400px;
    font-family: inherit;
    box-sizing: border-box;
    width: 100%;
    overflow: hidden; /* Mencegah scroll horizontal */
}

        .ql-editor {
    min-height: 400px;
    font-size: 1rem;
    line-height: 1.8;
    padding: 20px;
    box-sizing: border-box;
    
    /* FIX OVERFLOW - TAMBAHAN INI: */
    width: 100% !important;
    max-width: 100% !important;
    overflow-wrap: break-word !important;
    word-wrap: break-word !important;
    word-break: break-word !important;
    white-space: pre-wrap !important;
    overflow-x: hidden !important;
    overflow-y: auto !important;
    hyphens: auto !important;
}

/* Fix untuk semua elemen di dalam editor */
.ql-editor * {
    max-width: 100% !important;
    box-sizing: border-box !important;
    overflow-wrap: break-word !important;
    word-wrap: break-word !important;
}

/* Fix khusus untuk paragraf dan heading */
.ql-editor p,
.ql-editor div,
.ql-editor span,
.ql-editor h1,
.ql-editor h2,
.ql-editor h3,
.ql-editor h4,
.ql-editor h5,
.ql-editor h6 {
    max-width: 100% !important;
    overflow-wrap: break-word !important;
    word-wrap: break-word !important;
}

/* Fix untuk blockquote dan code */
.ql-editor blockquote,
.ql-editor pre {
    max-width: 100% !important;
    overflow-x: auto !important;
    white-space: pre-wrap !important;
}

        /* Preview Mode */
        .preview-toggle {
            margin-bottom: 15px;
        }

        .preview-content {
            display: none;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            background: white;
            min-height: 400px;
            font-size: 1rem;
            line-height: 1.8;
        }

        .preview-content.active {
            display: block;
        }

        .editor-container.hidden {
            display: none;
        }

        /* Image Upload */
        .image-upload {
            border: 2px dashed var(--border-color);
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: var(--light-color);
        }

        .image-upload:hover {
            border-color: var(--primary-color);
            background: rgba(26, 95, 63, 0.05);
        }

        .image-upload.dragover {
            border-color: var(--secondary-color);
            background: rgba(212, 175, 55, 0.1);
        }

        .image-upload-icon {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 15px;
        }

        .image-upload-text {
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .image-upload-text strong {
            color: var(--primary-color);
        }

        .image-preview {
            margin-top: 20px;
            text-align: center;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .image-preview .remove-image {
            display: inline-block;
            margin-top: 15px;
            color: var(--danger-color);
            cursor: pointer;
            font-size: 0.9rem;
            padding: 8px 15px;
            border: 1px solid var(--danger-color);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .image-preview .remove-image:hover {
            background: var(--danger-color);
            color: white;
        }

        /* Current Image Display */
        .current-image {
            background: var(--light-color);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .current-image img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .current-image-info {
            margin-top: 15px;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .change-image {
            display: inline-block;
            margin-top: 15px;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 0.9rem;
            padding: 8px 15px;
            border: 1px solid var(--primary-color);
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .change-image:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Sidebar */
        .sidebar-form {
            background: var(--light-color);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }

        .sidebar-form h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 15px 0;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .checkbox-group:hover {
            border-color: var(--primary-color);
            background: rgba(26, 95, 63, 0.05);
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: var(--primary-color);
        }

        .status-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .status-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-option:hover {
            border-color: var(--primary-color);
            background: rgba(26, 95, 63, 0.05);
        }

        .status-option input[type="radio"] {
            accent-color: var(--primary-color);
            width: 18px;
            height: 18px;
        }

        .status-option.selected {
            border-color: var(--primary-color);
            background: rgba(26, 95, 63, 0.1);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #2d8659;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: var(--text-muted);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--dark-color);
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

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
            margin-top: 30px;
        }

        .form-help {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .character-count {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: right;
            margin-top: 5px;
        }

        .character-count.warning {
            color: var(--warning-color);
        }

        .character-count.danger {
            color: var(--danger-color);
        }

        /* Tags Input */
        .tags-input {
            position: relative;
        }

        .tag-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 150px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: var(--shadow);
        }

        .tag-suggestion {
            padding: 10px 15px;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .tag-suggestion:hover {
            background: var(--light-color);
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
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Delete Confirmation */
        .delete-section {
            background: #fdf2f2;
            border: 1px solid #fbb6b6;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }

        .delete-section h4 {
            color: var(--danger-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .delete-section p {
            color: var(--text-dark);
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }

            .form-body {
                padding: 25px;
            }

            .form-actions {
                flex-direction: column;
            }

            .status-options {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .ql-editor {
                min-height: 300px;
                padding: 15px !important; /* Tambahkan !important */
                font-size: 0.9rem !important; /* Tambahkan !important */
            }

            .preview-content {
                min-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-edit"></i>
                        Edit Artikel
                    </h1>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a>
                        <i class="fas fa-chevron-right"></i>
                        <a href="index.php">Artikel</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>Edit: <?= htmlspecialchars(substr($artikel['judul'], 0, 30)) ?><?= strlen($artikel['judul']) > 30 ? '...' : '' ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="view.php?id=<?= $artikel['id'] ?>" class="btn btn-outline" target="_blank">
                        <i class="fas fa-eye"></i>
                        Preview
                    </a>
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-edit"></i> Edit Artikel</h2>
            </div>
            
            <div class="form-body">
                <form method="POST" enctype="multipart/form-data" id="articleForm">
                    <div class="form-grid">
                        <!-- Main Content -->
                        <div class="main-content">
                            <!-- Title -->
                            <div class="form-group">
                                <label for="judul" class="required">Judul Artikel</label>
                                <input type="text" 
                                       id="judul" 
                                       name="judul" 
                                       class="form-control large" 
                                       placeholder="Masukkan judul artikel yang menarik..."
                                       value="<?= htmlspecialchars($artikel['judul']) ?>"
                                       required
                                       maxlength="255">
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i>
                                    Judul yang baik: jelas, menarik, dan mengandung kata kunci
                                </div>
                                <div class="character-count" id="titleCount"><?= strlen($artikel['judul']) ?>/255 karakter</div>
                            </div>

                            <!-- Content Editor -->
                            <div class="form-group">
                                <label for="konten" class="required">Konten Artikel</label>
                                
                                <!-- Preview Toggle -->
                                <div class="preview-toggle">
                                    <button type="button" id="togglePreview" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> Preview
                                    </button>
                                </div>
                                
                                <!-- Editor -->
                                <div class="editor-container" id="editorContainer">
                                    <div id="editor"></div>
                                </div>
                                
                                <!-- Preview -->
                                <div class="preview-content" id="previewContent"></div>
                                
                                <!-- Hidden input for content -->
                                <input type="hidden" name="konten" id="konten" value="<?= htmlspecialchars($artikel['konten']) ?>">
                                
                                <div class="form-help">
                                    <i class="fas fa-keyboard"></i>
                                    Gunakan editor untuk format teks, tambah gambar, link, dan lainnya
                                </div>
                            </div>

                            <!-- Excerpt -->
                            <div class="form-group">
                                <label for="excerpt">Ringkasan/Excerpt</label>
                                <textarea name="excerpt" 
                                         id="excerpt" 
                                         class="form-control" 
                                         rows="3"
                                         placeholder="Tulis ringkasan singkat artikel (opsional, akan auto-generate jika kosong)"
                                         maxlength="300"><?= htmlspecialchars($artikel['excerpt']) ?></textarea>
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i>
                                    Ringkasan akan tampil di halaman daftar artikel
                                </div>
                                <div class="character-count" id="excerptCount"><?= strlen($artikel['excerpt']) ?>/300 karakter</div>
                            </div>

                            <!-- Tags -->
                            <div class="form-group">
                                <label for="tags">Tags</label>
                                <div class="tags-input">
                                    <input type="text" 
                                           id="tags" 
                                           name="tags" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($artikel['tags']) ?>"
                                           placeholder="Pisahkan dengan koma (contoh: islam, pendidikan, akhlak)">
                                    <div class="tag-suggestions" id="tagSuggestions"></div>
                                </div>
                                <div class="form-help">
                                    <i class="fas fa-tags"></i>
                                    Tags membantu pengunjung menemukan artikel terkait
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="sidebar-content">
                            <!-- Publish Settings -->
                            <div class="sidebar-form">
                                <h3><i class="fas fa-cog"></i> Pengaturan Publikasi</h3>
                                
                                <!-- Status -->
                                <div class="form-group">
                                    <label>Status Publikasi</label>
                                    <div class="status-options">
                                        <label class="status-option <?= $artikel['status'] === 'draft' ? 'selected' : '' ?>">
                                            <input type="radio" name="status" value="draft" <?= $artikel['status'] === 'draft' ? 'checked' : '' ?>>
                                            <div>
                                                <i class="fas fa-edit"></i>
                                                <span>Draft</span>
                                            </div>
                                        </label>
                                        <label class="status-option <?= $artikel['status'] === 'published' ? 'selected' : '' ?>">
                                            <input type="radio" name="status" value="published" <?= $artikel['status'] === 'published' ? 'checked' : '' ?>>
                                            <div>
                                                <i class="fas fa-globe"></i>
                                                <span>Publish</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Publish Date -->
                                <div class="form-group">
                                    <label for="tanggal_publish">Tanggal Publish</label>
                                    <input type="date" 
                                           id="tanggal_publish" 
                                           name="tanggal_publish" 
                                           class="form-control"
                                           value="<?= $artikel['tanggal_publish'] ?>">
                                </div>

                                <!-- Featured -->
                                <div class="checkbox-group">
                                    <input type="checkbox" id="featured" name="featured" value="1" <?= $artikel['featured'] ? 'checked' : '' ?>>
                                    <label for="featured">
                                        <i class="fas fa-star"></i>
                                        Artikel Unggulan
                                    </label>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="sidebar-form">
                                <h3><i class="fas fa-folder"></i> Kategori</h3>
                                
                                <div class="form-group">
                                    <label for="kategori">Pilih Kategori</label>
                                    <select name="kategori" id="kategori" class="form-control">
                                        <option value="">Pilih kategori...</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= htmlspecialchars($category['nama']) ?>" 
                                                    <?= $artikel['kategori'] === $category['nama'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['nama']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Featured Image -->
                            <div class="sidebar-form">
                                <h3><i class="fas fa-image"></i> Gambar Utama</h3>
                                
                                <?php if (!empty($artikel['gambar'])): ?>
                                    <!-- Current Image -->
                                    <div class="current-image" id="currentImage">
                                        <img src="../../assets/uploads/artikel/<?= htmlspecialchars($artikel['gambar']) ?>" 
                                             alt="<?= htmlspecialchars($artikel['gambar_alt']) ?>">
                                        <div class="current-image-info">
                                            <strong>Gambar saat ini:</strong> <?= htmlspecialchars($artikel['gambar']) ?>
                                        </div>
                                        <div class="change-image" id="changeImage">
                                            <i class="fas fa-exchange-alt"></i> Ganti gambar
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="image-upload" id="imageUpload" style="<?= !empty($artikel['gambar']) ? 'display: none;' : '' ?>">
                                    <div class="image-upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="image-upload-text">
                                        <strong>Klik untuk upload</strong> atau drag & drop gambar
                                    </div>
                                    <div class="image-upload-text">
                                        Format: JPG, PNG, WEBP, GIF (Max 5MB)
                                    </div>
                                    <input type="file" 
                                           name="gambar" 
                                           id="gambar" 
                                           accept="image/*" 
                                           style="display: none;">
                                </div>
                                
                                <div class="image-preview" id="imagePreview" style="display: none;">
                                    <img id="previewImg" src="" alt="Preview">
                                    <div class="remove-image" id="removeImage">
                                        <i class="fas fa-times"></i> Hapus gambar
                                    </div>
                                </div>

                                <div class="form-group" style="margin-top: 15px;">
                                    <label for="gambar_alt">Alt Text Gambar</label>
                                    <input type="text" 
                                           id="gambar_alt" 
                                           name="gambar_alt" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($artikel['gambar_alt']) ?>"
                                           placeholder="Deskripsi gambar untuk SEO">
                                    <div class="form-help">
                                        <i class="fas fa-info-circle"></i>
                                        Alt text membantu SEO dan aksesibilitas
                                    </div>
                                </div>
                            </div>

                            <!-- Delete Section -->
                            <div class="delete-section">
                                <h4><i class="fas fa-trash-alt"></i> Hapus Artikel</h4>
                                <p>Hapus artikel ini secara permanen. Tindakan ini tidak dapat dibatalkan.</p>
                                <a href="delete.php?id=<?= $artikel['id'] ?>" 
                                   class="btn btn-danger"
                                   onclick="return confirm('Apakah Anda yakin ingin menghapus artikel ini? Tindakan ini tidak dapat dibatalkan!')">
                                    <i class="fas fa-trash-alt"></i>
                                    Hapus Artikel
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-outline">
                            <i class="fas fa-times"></i>
                            Batal
                        </a>
                        <button type="button" id="saveDraft" class="btn btn-secondary">
                            <i class="fas fa-save"></i>
                            Simpan Draft
                        </button>
                        <button type="submit" id="updateBtn" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            Perbarui Artikel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
    <script>
        // Track if form has been submitted to prevent beforeunload warning
        let formSubmitted = false;
        let hasUnsavedChanges = false;

        // Initialize Quill editor with comprehensive toolbar
        const quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Mulai tulis artikel Anda di sini...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'font': [] }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'header': 1 }, { 'header': 2 }],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'direction': 'rtl' }],
                    [{ 'align': [] }],
                    ['link', 'image', 'video'],
                    ['clean']
                ]
            }
        });

        // Load existing content into Quill editor
        const existingContent = document.getElementById('konten').value;
        if (existingContent) {
            quill.root.innerHTML = existingContent;
        }

        // Character counters
        function updateCharacterCount(inputId, countId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(countId);
            
            // Initial count
            const initialLength = input.value.length;
            counter.textContent = `${initialLength}/${maxLength} karakter`;
            
            input.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = `${length}/${maxLength} karakter`;
                hasUnsavedChanges = true;
                
                if (length > maxLength * 0.8) {
                    counter.classList.add('warning');
                } else {
                    counter.classList.remove('warning');
                }
                
                if (length > maxLength * 0.95) {
                    counter.classList.add('danger');
                } else {
                    counter.classList.remove('danger');
                }
            });
        }

        updateCharacterCount('judul', 'titleCount', 255);
        updateCharacterCount('excerpt', 'excerptCount', 300);

        // Track changes in Quill editor
        quill.on('text-change', function() {
            hasUnsavedChanges = true;
        });

        // Track changes in other form fields
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('change', function() {
                hasUnsavedChanges = true;
            });
        });

        // Auto-generate alt text from title if alt text is empty or matches old title
        const titleInput = document.getElementById('judul');
        const altInput = document.getElementById('gambar_alt');
        const originalTitle = titleInput.value;

        titleInput.addEventListener('input', function() {
            if (!altInput.value || altInput.value === originalTitle || altInput.value === altInput.getAttribute('data-auto')) {
                altInput.value = this.value;
                altInput.setAttribute('data-auto', this.value);
            }
        });

        // Image upload handling
        const imageUpload = document.getElementById('imageUpload');
        const imageInput = document.getElementById('gambar');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const removeImage = document.getElementById('removeImage');
        const currentImage = document.getElementById('currentImage');
        const changeImage = document.getElementById('changeImage');

        imageUpload.addEventListener('click', () => imageInput.click());

        // Change image button
        if (changeImage) {
            changeImage.addEventListener('click', () => {
                imageInput.click();
            });
        }

        // Drag and drop
        imageUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUpload.classList.add('dragover');
        });

        imageUpload.addEventListener('dragleave', () => {
            imageUpload.classList.remove('dragover');
        });

        imageUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            imageUpload.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleImageUpload(files[0]);
            }
        });

        imageInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleImageUpload(e.target.files[0]);
            }
        });

        function handleImageUpload(file) {
            if (!file.type.startsWith('image/')) {
                alert('File harus berupa gambar!');
                return;
            }

            if (file.size > 5 * 1024 * 1024) { // 5MB
                alert('Ukuran file terlalu besar! Maksimal 5MB.');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                imageUpload.style.display = 'none';
                imagePreview.style.display = 'block';
                if (currentImage) {
                    currentImage.style.display = 'none';
                }
                hasUnsavedChanges = true;
            };
            reader.readAsDataURL(file);
        }

        removeImage.addEventListener('click', () => {
            imageInput.value = '';
            imageUpload.style.display = 'block';
            imagePreview.style.display = 'none';
            if (currentImage) {
                currentImage.style.display = 'block';
            }
            previewImg.src = '';
            hasUnsavedChanges = true;
        });

        // Preview toggle
        const togglePreview = document.getElementById('togglePreview');
        const editorContainer = document.getElementById('editorContainer');
        const previewContent = document.getElementById('previewContent');
        let isPreviewMode = false;

        togglePreview.addEventListener('click', () => {
            isPreviewMode = !isPreviewMode;
            
            if (isPreviewMode) {
                const content = quill.root.innerHTML;
                previewContent.innerHTML = content;
                editorContainer.classList.add('hidden');
                previewContent.classList.add('active');
                togglePreview.innerHTML = '<i class="fas fa-edit"></i> Edit';
            } else {
                editorContainer.classList.remove('hidden');
                previewContent.classList.remove('active');
                togglePreview.innerHTML = '<i class="fas fa-eye"></i> Preview';
            }
        });

        // Status radio button styling
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.status-option').forEach(option => {
                    option.classList.remove('selected');
                });
                this.closest('.status-option').classList.add('selected');
                hasUnsavedChanges = true;
            });
        });

        // Tags suggestions
        const tagSuggestions = [
            'Islam', 'Pendidikan', 'Akhlak', 'Motivasi', 'Sosial', 'Teknologi',
            'Budaya', 'Dakwah', 'Pemuda', 'Kampus', 'Mahasiswa', 'Organisasi',
            'Kegiatan', 'Pelatihan', 'Workshop', 'Seminar', 'Kajian'
        ];

        const tagsInput = document.getElementById('tags');
        const suggestionsContainer = document.getElementById('tagSuggestions');

        tagsInput.addEventListener('input', function() {
            const value = this.value.toLowerCase();
            const lastTag = value.split(',').pop().trim();
            
            if (lastTag.length > 0) {
                const matches = tagSuggestions.filter(tag => 
                    tag.toLowerCase().includes(lastTag) && 
                    !value.includes(tag.toLowerCase())
                );
                
                if (matches.length > 0) {
                    suggestionsContainer.innerHTML = matches
                        .map(tag => `<div class="tag-suggestion" data-tag="${tag}">${tag}</div>`)
                        .join('');
                    suggestionsContainer.style.display = 'block';
                } else {
                    suggestionsContainer.style.display = 'none';
                }
            } else {
                suggestionsContainer.style.display = 'none';
            }
        });

        suggestionsContainer.addEventListener('click', (e) => {
            if (e.target.classList.contains('tag-suggestion')) {
                const tag = e.target.dataset.tag;
                const currentValue = tagsInput.value;
                const tags = currentValue.split(',').map(t => t.trim());
                tags[tags.length - 1] = tag;
                tagsInput.value = tags.join(', ') + ', ';
                suggestionsContainer.style.display = 'none';
                tagsInput.focus();
                hasUnsavedChanges = true;
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.tags-input')) {
                suggestionsContainer.style.display = 'none';
            }
        });

        // Form submission
        document.getElementById('articleForm').addEventListener('submit', function(e) {
            // Set content from Quill editor
            document.getElementById('konten').value = quill.root.innerHTML;
            
            // Validate required fields
            const title = document.getElementById('judul').value.trim();
            const content = quill.getText().trim();
            
            if (!title) {
                alert('Judul artikel harus diisi!');
                e.preventDefault();
                return;
            }
            
            if (content.length < 50) {
                alert('Konten artikel terlalu pendek! Minimal 50 karakter.');
                e.preventDefault();
                return;
            }
            
            // Mark form as submitted to prevent beforeunload warning
            formSubmitted = true;
            hasUnsavedChanges = false;
            
            // Show loading state
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            // Also disable other buttons to prevent double submission
            document.getElementById('saveDraft').disabled = true;
        });

        // Save draft button
        document.getElementById('saveDraft').addEventListener('click', function() {
            // Set status to draft
            document.querySelector('input[name="status"][value="draft"]').checked = true;
            document.querySelectorAll('.status-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelector('input[name="status"][value="draft"]').closest('.status-option').classList.add('selected');
            
            // Set content and submit
            document.getElementById('konten').value = quill.root.innerHTML;
            
            // Mark form as submitted
            formSubmitted = true;
            hasUnsavedChanges = false;
            
            // Show loading state
            this.classList.add('loading');
            this.disabled = true;
            document.getElementById('updateBtn').disabled = true;
            
            document.getElementById('articleForm').submit();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+S for save draft
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.getElementById('saveDraft').click();
            }
            
            // Ctrl+Enter for update
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('updateBtn').click();
            }
        });

        // Only warn before leaving if there are unsaved changes AND form hasn't been submitted
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges && !formSubmitted) {
                e.preventDefault();
                e.returnValue = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
                return e.returnValue;
            }
        });

        // Performance logging
        window.addEventListener('load', function() {
            console.log('✏️ Edit artikel loaded successfully!');
            console.log('💡 Tips: Gunakan Ctrl+S untuk simpan draft, Ctrl+Enter untuk update');
        });
    </script>
</body>
</html>