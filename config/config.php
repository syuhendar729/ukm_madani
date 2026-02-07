<?php
// Ubah konfigurasi untuk produksi
define('ENVIRONMENT', 'production'); // Ubah dari 'development' ke 'production'
define('SITE_NAME', 'UKM Madani');
define('SITE_DESCRIPTION', 'Mahasiswa Peradaban Islam');
define('SITE_URL', 'https://madani.ukm.itera.ac.id'); // Ubah dari localhost ke URL produksi

// Konfigurasi Upload - Sesuaikan path jika perlu
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Konfigurasi Pagination
define('POSTS_PER_PAGE', 10);
define('ADMIN_POSTS_PER_PAGE', 20);

// Konfigurasi Session yang lebih aman untuk produksi
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Ubah dari 0 ke 1 untuk HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.sid_length', 48);
ini_set('session.use_strict_mode', 1);

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting - PENTING untuk produksi
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-errors.log');

// Pastikan folder logs ada dan dapat ditulis
if (!file_exists(__DIR__ . '/../logs/')) {
    mkdir(__DIR__ . '/../logs/', 0755, true);
}
?>