<?php
// admin/galeri/create.php - PERBAIKAN LENGKAP
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

// Initialize variables
$error_message = '';
$success_message = '';
$judul = '';
$deskripsi = '';
$kategori = '';
$lokasi = '';
$tanggal_kegiatan = '';
$google_drive_link = '';
$total_foto = '';
$tags = '';
$status = 'draft';

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
    } elseif (!empty($tanggal_kegiatan)) {
        // TAMBAHAN: Validasi tanggal server-side
        $event_date = new DateTime($tanggal_kegiatan);
        $today = new DateTime();
        $today->setTime(23, 59, 59); // Allow today
        
        if ($event_date > $today) {
            $error_message = "Tanggal kegiatan tidak boleh di masa depan.";
        }
    }
    
    if (empty($error_message)) {
        try {
            // Generate slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul), '-'));
            
            // Check if slug already exists
            $check_stmt = $conn->prepare("SELECT id FROM galeri WHERE slug = ?");
            $check_stmt->bind_param("s", $slug);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $slug .= '-' . time();
            }
            
            // Handle cover image upload
            $cover_image = '';
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
                
                $cover_image = 'galeri_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $cover_image;
                
                if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                    throw new Exception("Gagal mengupload gambar cover.");
                }
            }
            
            // Insert into database
            $sql = "INSERT INTO galeri (judul, slug, deskripsi, kategori, lokasi, tanggal_kegiatan, google_drive_link, total_foto, tags, cover_image, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssisss", $judul, $slug, $deskripsi, $kategori, $lokasi, $tanggal_kegiatan, $google_drive_link, $total_foto, $tags, $cover_image, $status);
            
            if ($stmt->execute()) {
                // Log activity
                try {
                    $activity_stmt = $conn->prepare("INSERT INTO activity_log (user_id, user_name, action, description, ip_address, created_at) VALUES (?, ?, 'create', ?, ?, NOW())");
                    $activity_desc = "Menambah dokumentasi galeri: " . $judul;
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                    
                    $activity_stmt->bind_param("isss", $_SESSION['admin_id'], $admin_name, $activity_desc, $ip_address);
                    $activity_stmt->execute();
                } catch (Exception $e) {
                    // Ignore if activity_log doesn't exist
                }
                
                // PERBAIKAN: Set session message dan redirect seperti edit.php
                $_SESSION['success_message'] = "Dokumentasi berhasil dibuat!";
                if ($status === 'published') {
                    $_SESSION['success_message'] .= " Dokumentasi telah dipublikasikan.";
                } else {
                    $_SESSION['success_message'] .= " Dokumentasi disimpan sebagai draft.";
                }
                
                // REDIRECT LANGSUNG KE INDEX.PHP
                header('Location: index.php');
                exit;
                
            } else {
                throw new Exception("Gagal menyimpan dokumentasi: " . $stmt->error);
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            
            // Delete uploaded file if database insert failed
            if (!empty($cover_image) && file_exists($upload_dir . $cover_image)) {
                unlink($upload_dir . $cover_image);
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
    <title>Tambah Dokumentasi - UKM Madani</title>
    
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
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Back Button */
        .back-btn {
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

        .back-btn:hover {
            background: #b8941f;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.4);
            color: white;
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
            display: none;
            text-align: center;
            padding: 15px;
            background: var(--light-color);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            border: 2px solid var(--border-color);
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
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-camera"></i>
                    Tambah Dokumentasi
                </h1>
                <div class="breadcrumb">
                    <a href="../admin/dashboard.php">Dashboard</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="index.php">Galeri</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Tambah Dokumentasi</span>
                </div>
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Kembali
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-header">
                <h2>Tambah Dokumentasi Kegiatan</h2>
                <p>Kelola dokumentasi kegiatan UKM Madani dengan mudah</p>
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
                    <div class="file-upload">
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
                    <div class="preview-container" id="imagePreview">
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
                        Simpan Dokumentasi
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-undo"></i>
                        Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // SIMPLE & CLEAN JAVASCRIPT - TANPA AUTO-SAVE YANG MENGGANGGU
        
        // Initialize tags
        let tags = [];
        const existingTags = document.getElementById('tags').value;
        if (existingTags) {
            tags = existingTags.split(',').map(tag => tag.trim()).filter(tag => tag);
            renderTags();
        }

        // Tags functionality
        function renderTags() {
            const tagsInput = document.getElementById('tagsInput');
            const tagInput = tagsInput.querySelector('.tag-input');
            
            tagsInput.querySelectorAll('.tag').forEach(tag => tag.remove());
            
            tags.forEach((tag, index) => {
                const tagElement = document.createElement('span');
                tagElement.className = 'tag';
                tagElement.innerHTML = `
                    ${tag}
                    <span class="tag-remove" onclick="removeTag(${index})">×</span>
                `;
                tagsInput.insertBefore(tagElement, tagInput);
            });
            
            document.getElementById('tags').value = tags.join(', ');
        }

        function addTag(tagText) {
            const tag = tagText.trim().toLowerCase();
            if (tag && !tags.includes(tag)) {
                tags.push(tag);
                renderTags();
            }
        }

        function removeTag(index) {
            tags.splice(index, 1);
            renderTags();
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
        }

        // File upload preview
        document.getElementById('cover_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            const fileUploadLabel = document.querySelector('.file-upload-label');
            
            if (file) {
                // Validasi file
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung. Gunakan: JPG, PNG, GIF, WebP');
                    this.value = '';
                    return;
                }
                
                // Validasi ukuran file (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Ukuran file terlalu besar. Maksimal 5MB.');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Update upload area
                    fileUploadLabel.innerHTML = `
                        <i class="fas fa-check-circle" style="color: var(--success-color); font-size: 2rem;"></i>
                        <div>
                            <strong style="color: var(--success-color);">File berhasil dipilih!</strong><br>
                            <small>${file.name}</small><br>
                            <small>Klik untuk mengganti file</small>
                        </div>
                    `;
                    fileUploadLabel.style.borderColor = 'var(--success-color)';
                    fileUploadLabel.style.background = 'rgba(40, 167, 69, 0.05)';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
                resetFileUpload();
            }
        });

        // Reset file upload display
        function resetFileUpload() {
            const fileUploadLabel = document.querySelector('.file-upload-label');
            fileUploadLabel.innerHTML = `
                <i class="fas fa-cloud-upload-alt file-upload-icon"></i>
                <div>
                    <strong>Klik untuk upload gambar cover</strong><br>
                    <small>atau drag & drop file di sini</small><br>
                    <small>Format: JPG, PNG, GIF, WebP (Max: 5MB)</small>
                </div>
            `;
            fileUploadLabel.style.borderColor = 'var(--border-color)';
            fileUploadLabel.style.background = 'var(--light-color)';
        }

        // Drag and drop functionality
        const fileUpload = document.querySelector('.file-upload');
        const fileInput = document.getElementById('cover_image');

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

        // Enhanced validation
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
            
            // Check date validation - Allow today and past dates
            const eventDate = new Date(document.getElementById('tanggal_kegiatan').value);
            const today = new Date();
            
            eventDate.setHours(0, 0, 0, 0);
            today.setHours(0, 0, 0, 0);
            
            if (eventDate > today) {
                const formGroup = document.getElementById('tanggal_kegiatan').closest('.form-group');
                formGroup.classList.add('error');
                showFieldError(formGroup, 'Tanggal kegiatan tidak boleh di masa depan');
                isValid = false;
            }
            
            return isValid;
        }

        // Form submission
        document.getElementById('galeriForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Validate form before submission
            if (!validateForm() || !enhancedValidation()) {
                e.preventDefault();
                
                // Scroll to first error
                const firstError = document.querySelector('.form-group.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }
            
            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Menyimpan...';
            submitBtn.disabled = true;
        });

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

        // Reset form function
        function resetForm() {
            if (confirm('Apakah Anda yakin ingin mereset form? Semua data yang telah diisi akan hilang.')) {
                document.getElementById('galeriForm').reset();
                document.getElementById('imagePreview').style.display = 'none';
                tags = [];
                renderTags();
                resetFileUpload();
                
                // Remove all error states
                document.querySelectorAll('.form-group.error').forEach(group => {
                    group.classList.remove('error');
                    removeFieldError(group);
                });
            }
        }

        // Auto-resize textarea
        document.getElementById('deskripsi').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });

        // Set date input max to today
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('tanggal_kegiatan');
            const today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('max', today);
        });

        console.log('✅ Create Galeri form loaded successfully!');
    </script>
</body>
</html>