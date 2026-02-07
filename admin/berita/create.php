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
// Check database columns
try {
    $check_columns = $conn->query("SHOW COLUMNS FROM berita LIKE 'gambar'");
    if ($check_columns->num_rows == 0) {
        $conn->query("ALTER TABLE berita ADD COLUMN gambar VARCHAR(255) NULL AFTER konten");
        $conn->query("ALTER TABLE berita ADD COLUMN gambar_alt VARCHAR(255) NULL AFTER gambar");
    }
} catch (Exception $e) {
    // Ignore errors
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $judul = trim($_POST['judul'] ?? '');
        $konten = trim($_POST['konten'] ?? '');
        $penulis = trim($_POST['penulis'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $gambar_alt = trim($_POST['gambar_alt'] ?? '');
        
        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
        $slug = trim($slug, '-');
        
        if (empty($judul) || empty($konten) || empty($penulis)) {
            throw new Exception('Semua field wajib harus diisi!');
        }
        
        // Validate content length
        if (strlen(strip_tags($konten)) < 50) {
            throw new Exception('Konten berita terlalu pendek! Minimal 50 karakter.');
        }
        
        $gambar_filename = null;
        
        // Handle image upload (simplified)
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Error saat upload file.');
            }
            
            $upload_dir = '../../assets/uploads/berita/';
            
            // Create directory if not exists
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_info = pathinfo($_FILES['gambar']['name']);
            $extension = strtolower($file_info['extension']);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (!in_array($extension, $allowed_extensions)) {
                throw new Exception('Format file tidak didukung! Gunakan JPG, PNG, WEBP, atau GIF.');
            }
            
            // Check file size (max 5MB)
            if ($_FILES['gambar']['size'] > 5 * 1024 * 1024) {
                throw new Exception('Ukuran file terlalu besar! Maksimal 5MB.');
            }
            
            // Generate unique filename
            $timestamp = date('Y-m-d_H-i-s');
            $random = uniqid();
            $gambar_filename = $timestamp . '_' . $random . '.' . $extension;
            $target_path = $upload_dir . $gambar_filename;
            
            if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $target_path)) {
                throw new Exception('Gagal mengupload gambar!');
            }
            
            // Optional: resize image if needed
            if (function_exists('imagecreatefromjpeg') && in_array($extension, ['jpg', 'jpeg', 'png'])) {
                resizeImage($target_path, $target_path, 1200, 800);
            }
        }
        
        // Check slug uniqueness
        $original_slug = $slug;
        $counter = 1;
        
        do {
            $stmt = $conn->prepare("SELECT id FROM berita WHERE slug = ?");
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $slug = $original_slug . '-' . $counter;
                $counter++;
            } else {
                break;
            }
        } while ($counter < 100);
        
        // Set publish date
        $tanggal_publish = ($status === 'published') ? date('Y-m-d') : null;
        
        // Insert to database
        $stmt = $conn->prepare("INSERT INTO berita (judul, slug, konten, gambar, gambar_alt, penulis, status, tanggal_publish, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("ssssssss", $judul, $slug, $konten, $gambar_filename, $gambar_alt, $penulis, $status, $tanggal_publish);
        
        if ($stmt->execute()) {
            // Log activity
            try {
                $activity_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'create', ?, ?, NOW())");
                $activity_desc = "Membuat berita: " . $judul;
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                $admin_name = $_SESSION['admin_nama'] ?? $_SESSION['admin_username'];
                
                $activity_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $activity_desc, $ip_address);
                $activity_stmt->execute();
            } catch (Exception $e) {
                // Ignore if activity_log doesn't exist
            }
            
            $_SESSION['success_message'] = "Berita berhasil dibuat!";
            if ($status === 'published') {
                $_SESSION['success_message'] .= " Berita telah dipublikasikan.";
            }
            
            // Redirect immediately
            header('Location: index.php');
            exit;
            
        } else {
            throw new Exception('Gagal menyimpan ke database: ' . $conn->error);
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        
        // Delete uploaded image if insert failed
        if (!empty($gambar_filename) && file_exists($upload_dir . $gambar_filename)) {
            unlink($upload_dir . $gambar_filename);
        }
    }
}

// Function to resize image
function resizeImage($source, $destination, $maxWidth, $maxHeight) {
    $info = getimagesize($source);
    if (!$info) return false;
    
    $width = $info[0];
    $height = $info[1];
    $type = $info[2];
    
    // Calculate new dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = $width * $ratio;
    $newHeight = $height * $ratio;
    
    // Don't resize if image is already smaller
    if ($ratio >= 1) return true;
    
    // Create image resource based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($source);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) return false;
    
    // Create new image
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
    }
    
    // Resize
    imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save based on type
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($newImage, $destination, 85);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($newImage, $destination);
            break;
    }
    
    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    return $result;
}

$admin_name = $_SESSION['admin_nama'] ?? $_SESSION['admin_username'] ?? 'Administrator';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$default_penulis = $_SESSION['admin_nama'] ?? $_SESSION['admin_username'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Berita - Admin UKM Madani</title>
    
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
            max-width: 1200px;
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
            max-width: 1200px;
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
            position: relative;
        }

        .image-upload:hover {
            border-color: var(--primary-color);
            background: rgba(26, 95, 63, 0.05);
        }

        .image-upload.dragover {
            border-color: var(--secondary-color);
            background: rgba(212, 175, 55, 0.1);
        }

        .image-upload input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 15px;
        }

        .upload-text {
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .upload-text strong {
            color: var(--primary-color);
        }

        .image-preview {
            margin-top: 20px;
            text-align: center;
            display: none;
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
            box-shadow: 0 4px 15px rgba(26, 95, 63, 0.4);
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
                        <i class="fas fa-newspaper"></i>
                        Tambah Berita
                    </h1>
                    <div class="breadcrumb">
                        <a href="../dashboard.php">Dashboard</a>
                        <i class="fas fa-chevron-right"></i>
                        <a href="index.php">Berita</a>
                        <i class="fas fa-chevron-right"></i>
                        <span>Tambah</span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i>
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-pen-fancy"></i> Buat Berita Baru</h2>
            </div>

            <div class="form-body">
                <form method="POST" action="" enctype="multipart/form-data" id="beritaForm">
                    <div class="form-grid">
                        <!-- Main Content -->
                        <div class="main-content">
                            <!-- Title -->
                            <div class="form-group">
                                <label for="judul" class="required">Judul Berita</label>
                                <input type="text" 
                                       id="judul" 
                                       name="judul" 
                                       class="form-control large" 
                                       placeholder="Masukkan judul berita yang menarik..." 
                                       required 
                                       maxlength="255">
                                <div class="form-help">
                                    <i class="fas fa-info-circle"></i>
                                    Gunakan judul yang menarik dan informatif
                                </div>
                                <div class="character-count" id="titleCount">0/255 karakter</div>
                            </div>

                            <!-- Content Editor -->
                            <div class="form-group">
                                <label for="konten" class="required">Konten Berita</label>
                                
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
                                <input type="hidden" name="konten" id="konten">
                                
                                <div class="form-help">
                                    <i class="fas fa-keyboard"></i>
                                    Gunakan editor untuk format teks, tambah gambar, link, dan lainnya
                                </div>
                            </div>

                            <!-- Author -->
                            <div class="form-group">
                                <label for="penulis" class="required">Penulis</label>
                                <input type="text" 
                                       id="penulis" 
                                       name="penulis" 
                                       class="form-control" 
                                       placeholder="Nama penulis berita..." 
                                       value="<?= htmlspecialchars($default_penulis) ?>" 
                                       required>
                                <div class="form-help">
                                    <i class="fas fa-user"></i>
                                    Nama yang akan ditampilkan sebagai penulis berita
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
                                        <label class="status-option selected">
                                            <input type="radio" name="status" value="draft" checked>
                                            <div>
                                                <i class="fas fa-edit"></i>
                                                <span>Draft</span>
                                            </div>
                                        </label>
                                        <label class="status-option">
                                            <input type="radio" name="status" value="published">
                                            <div>
                                                <i class="fas fa-globe"></i>
                                                <span>Publish</span>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="form-help" style="margin-top: 10px;">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Draft:</strong> disimpan tapi tidak dipublikasi • 
                                        <strong>Published:</strong> langsung ditampilkan di website
                                    </div>
                                </div>
                            </div>

                            <!-- Featured Image -->
                            <div class="sidebar-form">
                                <h3><i class="fas fa-image"></i> Gambar Berita</h3>
                                
                                <div class="image-upload" id="imageUpload">
                                    <div class="upload-icon">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                    </div>
                                    <div class="upload-text">
                                        <strong>Klik untuk upload</strong> atau drag & drop gambar
                                    </div>
                                    <div class="upload-text">
                                        Format: JPG, PNG, WEBP, GIF (Max 5MB)
                                    </div>
                                    <input type="file" 
                                           name="gambar" 
                                           id="gambar" 
                                           accept="image/*" 
                                           style="display: none;">
                                </div>
                                
                                <div class="image-preview" id="imagePreview">
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
                                           placeholder="Deskripsi gambar untuk aksesibilitas...">
                                    <div class="form-help">
                                        <i class="fas fa-universal-access"></i>
                                        Deskripsi gambar untuk pembaca layar dan SEO
                                    </div>
                                </div>
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
                        <button type="submit" id="publishBtn" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i>
                            Publikasikan
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

        // Initialize Quill editor
        const quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Mulai tulis berita Anda di sini...',
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

        // Character counter
        function updateCharacterCount(inputId, countId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(countId);
            
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

        // Auto-generate alt text from title
        document.getElementById('judul').addEventListener('input', function() {
            const altInput = document.getElementById('gambar_alt');
            if (!altInput.value || altInput.value === altInput.getAttribute('data-auto')) {
                altInput.value = this.value;
                altInput.setAttribute('data-auto', this.value);
            }
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

        // Image upload handling
        const imageUpload = document.getElementById('imageUpload');
        const imageInput = document.getElementById('gambar');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const removeImage = document.getElementById('removeImage');

        imageUpload.addEventListener('click', () => imageInput.click());

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
                hasUnsavedChanges = true;
            };
            reader.readAsDataURL(file);
        }

        removeImage.addEventListener('click', () => {
            imageInput.value = '';
            imageUpload.style.display = 'block';
            imagePreview.style.display = 'none';
            previewImg.src = '';
            hasUnsavedChanges = true;
        });

        // Form submission
        document.getElementById('beritaForm').addEventListener('submit', function(e) {
            // Set content from Quill editor
            document.getElementById('konten').value = quill.root.innerHTML;
            
            // Validate required fields
            const title = document.getElementById('judul').value.trim();
            const content = quill.getText().trim();
            const author = document.getElementById('penulis').value.trim();
            
            if (!title || !content || !author) {
                alert('Semua field yang bertanda * harus diisi!');
                e.preventDefault();
                return;
            }
            
            if (content.length < 50) {
                alert('Konten berita terlalu pendek! Minimal 50 karakter.');
                e.preventDefault();
                return;
            }
            
            if (title.length > 255) {
                alert('Judul terlalu panjang! Maksimal 255 karakter.');
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
            document.getElementById('publishBtn').disabled = true;
            
            document.getElementById('beritaForm').submit();
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+S for save draft
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.getElementById('saveDraft').click();
            }
            
            // Ctrl+Enter for publish
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('publishBtn').click();
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

        // Content word count
        quill.on('text-change', function() {
            const text = quill.getText().trim();
            const wordCount = text.length > 0 ? text.split(/\s+/).length : 0;
            const charCount = text.length;
            
            let contentCounter = document.getElementById('content-counter');
            if (!contentCounter) {
                contentCounter = document.createElement('div');
                contentCounter.id = 'content-counter';
                contentCounter.className = 'form-help';
                contentCounter.style.marginTop = '5px';
                document.getElementById('editorContainer').parentNode.appendChild(contentCounter);
            }
            
            contentCounter.innerHTML = `
                <i class="fas fa-chart-bar"></i>
                ${wordCount} kata • ${charCount} karakter
                ${charCount < 50 ? '• <span style="color: var(--danger-color);">Konten terlalu pendek!</span>' : ''}
            `;
        });

        // Performance logging
        window.addEventListener('load', function() {
            console.log('🎉 Create berita loaded successfully!');
            console.log('💡 Tips: Gunakan Ctrl+S untuk simpan draft, Ctrl+Enter untuk publish');
        });
    </script>
</body>
</html>