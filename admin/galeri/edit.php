<?php
// admin/galeri/edit.php
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

// Get galeri ID
$galeri_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$galeri_id) {
    header('Location: index.php?error=galeri_not_found');
    exit;
}

// Get galeri data
$galeri = null;
try {
    $stmt = $conn->prepare("SELECT * FROM galeri WHERE id = ?");
    $stmt->bind_param("i", $galeri_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $galeri = $result->fetch_assoc();
    
    if (!$galeri) {
        header('Location: index.php?error=galeri_not_found');
        exit;
    }
} catch (Exception $e) {
    header('Location: index.php?error=database_error');
    exit;
}

// Initialize variables with existing data
$error_message = '';
$success_message = '';
$judul = $galeri['judul'];
$deskripsi = $galeri['deskripsi'];
$kategori = $galeri['kategori'];
$lokasi = $galeri['lokasi'];
$tanggal_kegiatan = $galeri['tanggal_kegiatan'];
$google_drive_link = $galeri['google_drive_link'];
$total_foto = $galeri['total_foto'];
$tags = $galeri['tags'];
$status = $galeri['status'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    $tanggal_kegiatan = $_POST['tanggal_kegiatan'] ?? '';
    $google_drive_link = trim($_POST['google_drive_link'] ?? '');
    $total_foto = (int)($_POST['total_foto'] ?? 0);
    $tags = trim($_POST['tags'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    
    // Validation
    if (empty($judul)) {
        $error_message = "Judul dokumentasi harus diisi.";
    } elseif (empty($deskripsi)) {
        $error_message = "Deskripsi harus diisi.";
    } elseif (empty($tanggal_kegiatan)) {
        $error_message = "Tanggal kegiatan harus diisi.";
    } else {
        try {
            // Generate slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul), '-'));
            
            // Check if slug already exists (excluding current record)
            $check_stmt = $conn->prepare("SELECT id FROM galeri WHERE slug = ? AND id != ?");
            $check_stmt->bind_param("si", $slug, $galeri_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $slug .= '-' . time();
            }
            
            // Handle cover image upload
            $cover_image = $galeri['cover_image']; // Keep existing image
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/uploads/galeri/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception("Format file tidak didukung. Gunakan: " . implode(', ', $allowed_extensions));
                }
                
                if ($_FILES['cover_image']['size'] > 5 * 1024 * 1024) { // 5MB
                    throw new Exception("Ukuran file terlalu besar. Maksimal 5MB.");
                }
                
                $new_cover_image = 'galeri_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_cover_image;
                
                if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                    throw new Exception("Gagal mengupload gambar cover.");
                }
                
                // Delete old image if exists
                if (!empty($galeri['cover_image']) && file_exists($upload_dir . $galeri['cover_image'])) {
                    unlink($upload_dir . $galeri['cover_image']);
                }
                
                $cover_image = $new_cover_image;
            }
            
            // Update database
            $sql = "UPDATE galeri SET judul = ?, slug = ?, deskripsi = ?, kategori = ?, lokasi = ?, tanggal_kegiatan = ?, google_drive_link = ?, total_foto = ?, tags = ?, cover_image = ?, status = ?, updated_at = NOW() WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssisssi", $judul, $slug, $deskripsi, $kategori, $lokasi, $tanggal_kegiatan, $google_drive_link, $total_foto, $tags, $cover_image, $status, $galeri_id);
            
            if ($stmt->execute()) {
                // Log activity
                try {
                    $activity_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'update', ?, ?, NOW())");
                    $activity_desc = "Mengedit dokumentasi galeri: " . $judul;
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                    
                    $activity_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $activity_desc, $ip_address);
                    $activity_stmt->execute();
                } catch (Exception $e) {
                    // Ignore if activity_log doesn't exist
                }
                
                $_SESSION['success_message'] = "Dokumentasi berhasil diperbarui!";
                if ($status === 'published') {
                    $_SESSION['success_message'] .= " Dokumentasi telah dipublikasikan.";
                }
                
                // Redirect immediately
                header('Location: index.php');
                exit;
                
            } else {
                throw new Exception("Gagal memperbarui dokumentasi: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            
            // Delete uploaded file if update failed
            if (!empty($new_cover_image) && file_exists($upload_dir . $new_cover_image)) {
                unlink($upload_dir . $new_cover_image);
            }
        }
    }
}

// Get categories for dropdown
$categories = [];
try {
    $cat_result = $conn->query("SELECT DISTINCT kategori FROM galeri WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
    while ($cat = $cat_result->fetch_assoc()) {
        $categories[] = $cat['kategori'];
    }
} catch (Exception $e) {
    // Handle error silently
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dokumentasi - UKM Madani</title>
    
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
            max-width: 1000px;
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
            max-width: 1000px;
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

        /* Back Button */
        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .back-btn, .preview-btn {
            background: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover, .preview-btn:hover {
            background: #b8941f;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
            color: white;
        }

        .preview-btn {
            background: var(--info-color);
        }

        .preview-btn:hover {
            background: #138496;
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        /* Form Groups */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
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

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        /* File Upload */
        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 40px 20px;
            border: 2px dashed var(--border-color);
            border-radius: 10px;
            background: var(--light-color);
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .file-upload:hover .file-upload-label,
        .file-upload.dragover .file-upload-label {
            border-color: var(--primary-color);
            background: rgba(26, 95, 63, 0.05);
            color: var(--primary-color);
        }

        .file-upload-icon {
            font-size: 2rem;
        }

        .preview-container {
            margin-top: 15px;
            text-align: center;
        }

        .preview-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 10px;
            box-shadow: var(--shadow);
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
            max-width: 200px;
            max-height: 150px;
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

        /* Buttons */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
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

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        .btn-secondary {
            background: var(--text-muted);
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
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

        /* Tags Input */
        .tags-input {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 8px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            min-height: 48px;
            cursor: text;
        }

        .tags-input:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
        }

        .tag {
            background: var(--primary-color);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .tag-remove {
            cursor: pointer;
            font-size: 0.7rem;
            padding: 2px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
        }

        .tag-remove:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .tag-input {
            border: none;
            outline: none;
            flex: 1;
            min-width: 120px;
            padding: 8px;
            font-family: inherit;
        }

        /* Category Suggestions */
        .category-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .category-suggestion {
            background: var(--light-color);
            color: var(--text-muted);
            border: 1px solid var(--border-color);
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .category-suggestion:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Delete Section */
        .delete-section {
            background: #fdf2f2;
            border: 1px solid #fbb6b6;
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            text-align: center;
        }

        .delete-section h4 {
            color: var(--danger-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
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

            .form-card {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .header-actions {
                flex-direction: column;
                width: 100%;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .page-title {
                font-size: 1.5rem;
            }
        }

        /* Loading Animation */
        .btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Form Validation */
        .form-group.error input,
        .form-group.error select,
        .form-group.error textarea {
            border-color: var(--danger-color);
        }

        .error-message {
            color: var(--danger-color);
            font-size: 0.85rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-edit"></i>
                    Edit Dokumentasi
                </h1>
                <div class="breadcrumb">
                    <a href="../admin/dashboard.php">Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="index.php">Galeri</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Edit: <?= htmlspecialchars(substr($galeri['judul'], 0, 30)) ?><?= strlen($galeri['judul']) > 30 ? '...' : '' ?></span>
                </div>
            </div>
            <div class="header-actions">
                <a href="view.php?id=<?= $galeri['id'] ?>" class="preview-btn" target="_blank">
                    <i class="fas fa-eye"></i>
                    Preview
                </a>
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Kembali
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

        <div class="form-card">
            <div class="form-header">
                <h2>Edit Dokumentasi Kegiatan</h2>
                <p>Perbarui dokumentasi kegiatan UKM Madani</p>
            </div>

            <form id="galeriForm" method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="judul" class="required">Judul Dokumentasi</label>
                        <input type="text" id="judul" name="judul" value="<?= htmlspecialchars($judul) ?>" 
                               placeholder="Contoh: Kajian Islami Bulan Ramadan" required>
                        <small>Judul yang menarik dan deskriptif untuk dokumentasi</small>
                    </div>

                    <div class="form-group">
                        <label for="kategori">Kategori</label>
                        <input type="text" id="kategori" name="kategori" value="<?= htmlspecialchars($kategori) ?>" 
                               placeholder="Contoh: Kajian, Baksos, Event" list="category-suggestions">
                        <datalist id="category-suggestions">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <?php if (!empty($categories)): ?>
                            <div class="category-suggestions">
                                <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                                    <span class="category-suggestion" onclick="selectCategory('<?= htmlspecialchars($cat) ?>')">
                                        <?= htmlspecialchars($cat) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="deskripsi" class="required">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" placeholder="Deskripsikan kegiatan, tujuan, dan momen-momen penting..." required><?= htmlspecialchars($deskripsi) ?></textarea>
                    <small>Jelaskan secara detail tentang kegiatan yang didokumentasikan</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="lokasi">Lokasi</label>
                        <input type="text" id="lokasi" name="lokasi" value="<?= htmlspecialchars($lokasi) ?>" 
                               placeholder="Contoh: Masjid ITERA, Aula UKM">
                    </div>

                    <div class="form-group">
                        <label for="tanggal_kegiatan" class="required">Tanggal Kegiatan</label>
                        <input type="date" id="tanggal_kegiatan" name="tanggal_kegiatan" 
                               value="<?= htmlspecialchars($tanggal_kegiatan) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="google_drive_link">Link Google Drive</label>
                    <input type="url" id="google_drive_link" name="google_drive_link" 
                           value="<?= htmlspecialchars($google_drive_link) ?>" 
                           placeholder="https://drive.google.com/drive/folders/...">
                    <small>Link folder Google Drive yang berisi semua foto kegiatan</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="total_foto">Total Foto</label>
                        <input type="number" id="total_foto" name="total_foto" value="<?= htmlspecialchars($total_foto) ?>" 
                               min="0" placeholder="0">
                        <small>Jumlah foto dalam dokumentasi (opsional)</small>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Dipublikasikan</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="cover_image">Gambar Cover</label>
                    
                    <?php if (!empty($galeri['cover_image'])): ?>
                        <!-- Current Image -->
                        <div class="current-image" id="currentImage">
                            <img src="../../assets/uploads/galeri/<?= htmlspecialchars($galeri['cover_image']) ?>" 
                                 alt="<?= htmlspecialchars($galeri['judul']) ?>">
                            <div class="current-image-info">
                                <strong>Gambar saat ini:</strong> <?= htmlspecialchars($galeri['cover_image']) ?>
                            </div>
                            <div class="change-image" id="changeImage">
                                <i class="fas fa-exchange-alt"></i> Ganti gambar
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="file-upload" id="fileUpload" style="<?= !empty($galeri['cover_image']) ? 'display: none;' : '' ?>">
                        <input type="file" id="cover_image" name="cover_image" accept="image/*">
                        <label for="cover_image" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                            <div>
                                <strong>Klik untuk upload gambar cover</strong><br>
                                <small>atau drag & drop file di sini</small><br>
                                <small>Format: JPG, PNG, GIF, WebP (Max: 5MB)</small>
                            </div>
                        </label>
                    </div>
                    <div class="preview-container" id="imagePreview" style="display: none;">
                        <img class="preview-image" id="previewImg" alt="Preview">
                    </div>
                </div>

                <div class="form-group">
                    <label for="tags">Tags</label>
                    <div class="tags-input" id="tagsInput">
                        <input type="text" class="tag-input" id="tagInput" 
                               placeholder="Ketik tag dan tekan Enter...">
                    </div>
                    <input type="hidden" id="tags" name="tags" value="<?= htmlspecialchars($tags) ?>">
                    <small>Tags membantu dalam pencarian. Pisahkan dengan Enter. Contoh: kajian, ramadan, itera</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        Perbarui Dokumentasi
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-undo"></i>
                        Reset Form
                    </button>
                </div>
            </form>

            <!-- Delete Section -->
            <div class="delete-section">
                <h4><i class="fas fa-trash-alt"></i> Hapus Dokumentasi</h4>
                <p>Hapus dokumentasi ini secara permanen. Tindakan ini tidak dapat dibatalkan.</p>
                <a href="delete.php?id=<?= $galeri['id'] ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('Apakah Anda yakin ingin menghapus dokumentasi ini? Tindakan ini tidak dapat dibatalkan!')">
                    <i class="fas fa-trash-alt"></i>
                    Hapus Dokumentasi
                </a>
            </div>
        </div>
    </div>

    <script>
        // Initialize tags from existing data
        let tags = [];
        const existingTags = document.getElementById('tags').value;
        if (existingTags) {
            tags = existingTags.split(',').map(tag => tag.trim()).filter(tag => tag);
            renderTags();
        }

        // Track if form has been modified
        let hasUnsavedChanges = false;
        let formSubmitted = false;

        // Tags functionality
        function renderTags() {
            const tagsInput = document.getElementById('tagsInput');
            const tagInput = tagsInput.querySelector('.tag-input');
            
            // Remove existing tags
            tagsInput.querySelectorAll('.tag').forEach(tag => tag.remove());
            
            // Add tags before input
            tags.forEach((tag, index) => {
                const tagElement = document.createElement('span');
                tagElement.className = 'tag';
                tagElement.innerHTML = `
                    ${tag}
                    <span class="tag-remove" onclick="removeTag(${index})">×</span>
                `;
                tagsInput.insertBefore(tagElement, tagInput);
            });
            
            // Update hidden input
            document.getElementById('tags').value = tags.join(', ');
        }

        function addTag(tagText) {
            const tag = tagText.trim().toLowerCase();
            if (tag && !tags.includes(tag)) {
                tags.push(tag);
                renderTags();
                hasUnsavedChanges = true;
            }
        }

        function removeTag(index) {
            tags.splice(index, 1);
            renderTags();
            hasUnsavedChanges = true;
        }

        // Tag input event listeners
        document.getElementById('tagInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const tagText = this.value.trim();
                if (tagText) {
                    addTag(tagText);
                    this.value = '';
                }
            } else if (e.key === 'Backspace' && !this.value && tags.length > 0) {
                removeTag(tags.length - 1);
            }
        });

        // Category suggestions
        function selectCategory(category) {
            document.getElementById('kategori').value = category;
            hasUnsavedChanges = true;
        }

        // File upload preview
        document.getElementById('cover_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            const currentImage = document.getElementById('currentImage');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    if (currentImage) {
                        currentImage.style.display = 'none';
                    }
                    hasUnsavedChanges = true;
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                if (currentImage) {
                    currentImage.style.display = 'block';
                }
            }
        });

        // Change image button
        const changeImage = document.getElementById('changeImage');
        if (changeImage) {
            changeImage.addEventListener('click', () => {
                document.getElementById('cover_image').click();
            });
        }

        // Drag and drop functionality
        const fileUpload = document.getElementById('fileUpload');
        const fileInput = document.getElementById('cover_image');

        if (fileUpload) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileUpload.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                fileUpload.addEventListener(eventName, () => {
                    fileUpload.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                fileUpload.addEventListener(eventName, () => {
                    fileUpload.classList.remove('dragover');
                });
            });

            fileUpload.addEventListener('drop', function(e) {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        }

        // Track changes in form fields
        document.querySelectorAll('input, select, textarea').forEach(field => {
            const originalValue = field.value;
            field.addEventListener('input', function() {
                if (this.value !== originalValue) {
                    hasUnsavedChanges = true;
                }
            });
        });

        // Form submission with loading state
        document.getElementById('galeriForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Validate form before submission
            if (!validateForm()) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Memperbarui...';
            submitBtn.disabled = true;
            
            // Mark form as submitted
            formSubmitted = true;
            hasUnsavedChanges = false;
            
            // Reset button state after some time (in case of network issues)
            setTimeout(() => {
                submitBtn.classList.remove('loading');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });

        // Form validation
        function validateForm() {
            let isValid = true;
            const requiredFields = ['judul', 'deskripsi', 'tanggal_kegiatan'];
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                const formGroup = field.closest('.form-group');
                
                if (!field.value.trim()) {
                    formGroup.classList.add('error');
                    showFieldError(formGroup, 'Field ini harus diisi');
                    isValid = false;
                } else {
                    formGroup.classList.remove('error');
                    removeFieldError(formGroup);
                }
            });
            
            // Enhanced validation
            isValid = enhancedValidation() && isValid;
            
            return isValid;
        }

        function showFieldError(formGroup, message) {
            removeFieldError(formGroup);
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            formGroup.appendChild(errorDiv);
        }

        function removeFieldError(formGroup) {
            const existingError = formGroup.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
        }

        // Real-time validation
        ['judul', 'deskripsi', 'tanggal_kegiatan'].forEach(fieldId => {
            document.getElementById(fieldId).addEventListener('input', function() {
                const formGroup = this.closest('.form-group');
                if (this.value.trim()) {
                    formGroup.classList.remove('error');
                    removeFieldError(formGroup);
                }
            });
        });

        // Enhanced form validation
        function enhancedValidation() {
            let isValid = true;
            
            // Check file size
            const fileInput = document.getElementById('cover_image');
            if (fileInput.files[0] && fileInput.files[0].size > 5 * 1024 * 1024) {
                const formGroup = fileInput.closest('.form-group');
                formGroup.classList.add('error');
                showFieldError(formGroup, 'Ukuran file terlalu besar (maksimal 5MB)');
                isValid = false;
            }
            
            // Check Google Drive link format
            const driveLink = document.getElementById('google_drive_link').value.trim();
            if (driveLink && !driveLink.includes('drive.google.com')) {
                const formGroup = document.getElementById('google_drive_link').closest('.form-group');
                formGroup.classList.add('error');
                showFieldError(formGroup, 'Link harus dari Google Drive');
                isValid = false;
            }
            
            // Check date not in future (allow current date)
            const eventDate = new Date(document.getElementById('tanggal_kegiatan').value);
            const today = new Date();
            today.setHours(23, 59, 59, 999); // Allow today
            
            if (eventDate > today) {
                const formGroup = document.getElementById('tanggal_kegiatan').closest('.form-group');
                formGroup.classList.add('error');
                showFieldError(formGroup, 'Tanggal kegiatan tidak boleh di masa depan');
                isValid = false;
            }
            
            return isValid;
        }

        // Reset form function
        function resetForm() {
            if (confirm('Apakah Anda yakin ingin mereset form? Semua perubahan yang belum disimpan akan hilang.')) {
                location.reload(); // Reload to get original data
            }
        }

        // Auto-resize textarea
        document.getElementById('deskripsi').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        // Initialize textarea height
        const deskripsiTextarea = document.getElementById('deskripsi');
        deskripsiTextarea.style.height = 'auto';
        deskripsiTextarea.style.height = deskripsiTextarea.scrollHeight + 'px';

        // Google Drive link validation
        document.getElementById('google_drive_link').addEventListener('input', function() {
            const url = this.value.trim();
            const formGroup = this.closest('.form-group');
            
            if (url && !url.includes('drive.google.com')) {
                formGroup.classList.add('error');
                showFieldError(formGroup, 'Link harus dari Google Drive');
            } else {
                formGroup.classList.remove('error');
                removeFieldError(formGroup);
            }
        });

        // Warn before leaving if there are unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges && !formSubmitted) {
                e.preventDefault();
                e.returnValue = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
                return e.returnValue;
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                if (validateForm()) {
                    document.getElementById('galeriForm').submit();
                }
            }
            
            // Ctrl+R to reset (with confirmation)
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                resetForm();
            }
        });

        // Show keyboard shortcuts help
        function showShortcuts() {
            alert('Keyboard Shortcuts:\n\nCtrl+S: Simpan form\nCtrl+R: Reset form\nEnter: Tambah tag (saat di input tag)');
        }

        // Add help button for shortcuts
        document.addEventListener('DOMContentLoaded', function() {
            const helpBtn = document.createElement('button');
            helpBtn.type = 'button';
            helpBtn.className = 'btn btn-secondary';
            helpBtn.innerHTML = '<i class="fas fa-question-circle"></i> Bantuan';
            helpBtn.onclick = showShortcuts;
            helpBtn.style.position = 'fixed';
            helpBtn.style.bottom = '20px';
            helpBtn.style.right = '20px';
            helpBtn.style.zIndex = '1000';
            helpBtn.style.borderRadius = '50px';
            helpBtn.style.padding = '12px 16px';
            helpBtn.style.fontSize = '0.9rem';
            document.body.appendChild(helpBtn);
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

        // Auto-save to localStorage (draft functionality)
        function autoSave() {
            const formData = {
                judul: document.getElementById('judul').value,
                deskripsi: document.getElementById('deskripsi').value,
                kategori: document.getElementById('kategori').value,
                lokasi: document.getElementById('lokasi').value,
                tanggal_kegiatan: document.getElementById('tanggal_kegiatan').value,
                google_drive_link: document.getElementById('google_drive_link').value,
                total_foto: document.getElementById('total_foto').value,
                tags: tags.join(', '),
                status: document.getElementById('status').value
            };
            
            localStorage.setItem('galeri_edit_draft_<?= $galeri_id ?>', JSON.stringify(formData));
        }

        // Auto-save every 30 seconds
        setInterval(autoSave, 30000);

        // Save on form input
        document.getElementById('galeriForm').addEventListener('input', autoSave);

        // Clear draft on successful submission
        document.getElementById('galeriForm').addEventListener('submit', function() {
            setTimeout(() => {
                if (document.querySelector('.alert-success')) {
                    localStorage.removeItem('galeri_edit_draft_<?= $galeri_id ?>');
                }
            }, 1000);
        });

        // Load draft if exists
        function loadDraft() {
            const draft = localStorage.getItem('galeri_edit_draft_<?= $galeri_id ?>');
            if (draft) {
                const draftData = JSON.parse(draft);
                const currentData = {
                    judul: document.getElementById('judul').value,
                    deskripsi: document.getElementById('deskripsi').value,
                    kategori: document.getElementById('kategori').value,
                    lokasi: document.getElementById('lokasi').value,
                    tanggal_kegiatan: document.getElementById('tanggal_kegiatan').value,
                    google_drive_link: document.getElementById('google_drive_link').value,
                    total_foto: document.getElementById('total_foto').value,
                    tags: document.getElementById('tags').value,
                    status: document.getElementById('status').value
                };
                
                // Check if draft is different from current data
                if (JSON.stringify(draftData) !== JSON.stringify(currentData)) {
                    if (confirm('Ditemukan draft perubahan yang tersimpan. Ingin melanjutkan draft?')) {
                        Object.keys(draftData).forEach(key => {
                            const element = document.getElementById(key);
                            if (element && draftData[key]) {
                                element.value = draftData[key];
                            }
                        });
                        
                        if (draftData.tags) {
                            tags = draftData.tags.split(',').map(tag => tag.trim()).filter(tag => tag);
                            renderTags();
                        }
                        
                        // Trigger textarea resize
                        const deskripsiTextarea = document.getElementById('deskripsi');
                        deskripsiTextarea.style.height = 'auto';
                        deskripsiTextarea.style.height = deskripsiTextarea.scrollHeight + 'px';
                    }
                }
            }
        }

        // Load draft on page load
        window.addEventListener('load', loadDraft);

        console.log('✏️ Edit Galeri form loaded successfully!');
        console.log('💡 Tips: Gunakan Ctrl+S untuk simpan, Ctrl+R untuk reset');
    </script>
</body>
</html>