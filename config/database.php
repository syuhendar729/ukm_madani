<?php
// Konfigurasi Database untuk produksi
$host = '127.0.0.1'; // Sesuaikan dengan host di server hosting
$port = 3306;        // Sesuaikan dengan port MySQL di server hosting
$username = 'root'; // Ganti dengan username database di hosting
$password = 'root123'; // Ganti dengan password yang kuat
$dbname = 'ukm_madani'; // Ganti dengan nama database di hosting

// Buat koneksi MySQLi dengan port
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Cek koneksi
if ($conn->connect_error) {
    // Log error tanpa menampilkan detail sensitif
    error_log("Database connection failed: " . $conn->connect_error);
    die("Maaf, terjadi kesalahan koneksi ke database. Silakan hubungi administrator.");
}

// Set charset ke utf8mb4
$conn->set_charset("utf8mb4");
?>
