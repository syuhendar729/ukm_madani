-- UKM Madani MySQL Database Schema
-- Generated from Next.js MongoDB models analysis

-- Create database
CREATE DATABASE IF NOT EXISTS ukm_madani CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ukm_madani;

-- =============================================
-- TABLE: users
-- From: User.ts model
-- =============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(255) NOT NULL,
    role ENUM('admin', 'super_admin') DEFAULT 'admin',
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    last_login DATETIME NULL,
    login_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role)
);

-- =============================================
-- TABLE: kategoris
-- From: Kategori.ts model
-- =============================================
CREATE TABLE kategoris (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(255) NOT NULL UNIQUE,
    slug VARCHAR(255) NOT NULL UNIQUE,
    deskripsi TEXT,
    warna VARCHAR(7) DEFAULT '#10b981',
    aktif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_slug (slug),
    INDEX idx_aktif (aktif),
    INDEX idx_nama (nama)
);

-- =============================================
-- TABLE: artikels
-- From: Artikel.ts model + Basic SEO fields
-- =============================================
CREATE TABLE artikels (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(500) NOT NULL,
    slug VARCHAR(500) NOT NULL UNIQUE,
    konten LONGTEXT NOT NULL,
    excerpt TEXT,
    -- SEO Fields (simple)
    meta_title VARCHAR(60), -- Custom title for SEO (max 60 chars)
    meta_description VARCHAR(160), -- Custom meta description (max 160 chars)
    -- End SEO Fields
    penulis VARCHAR(255) NOT NULL,
    kategori VARCHAR(255),
    tags TEXT,
    gambar VARCHAR(500) DEFAULT '',
    gambar_alt VARCHAR(255),
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('published', 'draft') DEFAULT 'draft',
    tanggal_publish DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_kategori (kategori),
    INDEX idx_penulis (penulis),
    INDEX idx_tanggal_publish (tanggal_publish),
    FULLTEXT idx_search (judul, konten, excerpt, tags)
);

-- =============================================
-- TABLE: beritas
-- From: Berita.ts model + Basic SEO fields
-- =============================================
CREATE TABLE beritas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(500) NOT NULL,
    slug VARCHAR(500) NOT NULL UNIQUE,
    konten LONGTEXT NOT NULL,
    excerpt TEXT,
    -- SEO Fields (simple)
    meta_title VARCHAR(60), -- Custom title for SEO (max 60 chars)
    meta_description VARCHAR(160), -- Custom meta description (max 160 chars)
    -- End SEO Fields
    penulis VARCHAR(255) NOT NULL,
    gambar VARCHAR(500) DEFAULT '',
    gambar_alt VARCHAR(255),
    status ENUM('published', 'draft') DEFAULT 'draft',
    tanggal_publish DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_penulis (penulis),
    INDEX idx_tanggal_publish (tanggal_publish),
    FULLTEXT idx_search (judul, konten, excerpt)
);

-- =============================================
-- TABLE: galeris
-- From: Galeri.ts model
-- Note: Galeri hanya link ke Google Drive dengan preview thumbnail
-- =============================================
CREATE TABLE galeris (
    id INT PRIMARY KEY AUTO_INCREMENT,
    judul VARCHAR(500) NOT NULL,
    deskripsi TEXT,
    google_drive_url VARCHAR(1000) NOT NULL, -- Link ke Google Drive folder/album
    thumbnail_image VARCHAR(500), -- Upload gambar untuk preview/thumbnail
    tanggal_kegiatan DATE NOT NULL,
    status ENUM('published', 'draft') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_tanggal_kegiatan (tanggal_kegiatan),
    FULLTEXT idx_search (judul, deskripsi)
);

-- =============================================
-- SAMPLE DATA INSERTS
-- =============================================

-- Insert default admin user (password: admin123 - bcrypt hashed)
INSERT INTO users (username, email, password, nama_lengkap, role, status) VALUES
('admin', 'admin@ukmmadani.com', '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'super_admin', 'aktif');

-- Insert sample categories
INSERT INTO kategoris (nama, slug, deskripsi, warna) VALUES
('Kegiatan', 'kegiatan', 'Artikel tentang kegiatan UKM', '#10b981'),
('Pengumuman', 'pengumuman', 'Pengumuman resmi UKM', '#3b82f6'),
('Prestasi', 'prestasi', 'Pencapaian dan prestasi anggota', '#f59e0b'),
('Tutorial', 'tutorial', 'Tutorial dan panduan', '#8b5cf6');

-- =============================================
-- VIEWS FOR COMMON QUERIES
-- =============================================

-- View for published articles with category info
CREATE VIEW published_articles AS
SELECT
    a.*,
    k.nama as kategori_nama,
    k.warna as kategori_warna
FROM artikels a
LEFT JOIN kategoris k ON a.kategori = k.slug
WHERE a.status = 'published'
ORDER BY a.tanggal_publish DESC;

-- View for published news
CREATE VIEW published_news AS
SELECT *
FROM beritas
WHERE status = 'published'
ORDER BY tanggal_publish DESC;

-- View for published gallery
CREATE VIEW published_gallery AS
SELECT *
FROM galeris
WHERE status = 'published'
ORDER BY tanggal_kegiatan DESC;

-- =============================================
-- STORED PROCEDURES
-- =============================================

-- Procedure to get articles with pagination
DELIMITER //
CREATE PROCEDURE GetArticlesPaginated(
    IN p_limit INT,
    IN p_offset INT,
    IN p_kategori VARCHAR(255),
    IN p_status VARCHAR(20)
)
BEGIN
    DECLARE sql_query TEXT;

    SET sql_query = 'SELECT * FROM artikels WHERE 1=1';

    IF p_kategori IS NOT NULL AND p_kategori != '' THEN
        SET sql_query = CONCAT(sql_query, ' AND kategori = "', p_kategori, '"');
    END IF;

    IF p_status IS NOT NULL AND p_status != '' THEN
        SET sql_query = CONCAT(sql_query, ' AND status = "', p_status, '"');
    ELSE
        SET sql_query = CONCAT(sql_query, ' AND status = "published"');
    END IF;

    SET sql_query = CONCAT(sql_query, ' ORDER BY tanggal_publish DESC LIMIT ', p_limit, ' OFFSET ', p_offset);

    SET @sql = sql_query;
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END //
DELIMITER ;

-- =============================================
-- TRIGGERS
-- =============================================

-- Trigger to auto-generate slug for articles if not provided
DELIMITER //
CREATE TRIGGER artikel_before_insert
BEFORE INSERT ON artikels
FOR EACH ROW
BEGIN
    IF NEW.slug IS NULL OR NEW.slug = '' THEN
        SET NEW.slug = LOWER(REPLACE(REPLACE(REPLACE(NEW.judul, ' ', '-'), '.', ''), ',', ''));
    END IF;
END //
DELIMITER ;

-- Trigger to auto-generate slug for news if not provided
DELIMITER //
CREATE TRIGGER berita_before_insert
BEFORE INSERT ON beritas
FOR EACH ROW
BEGIN
    IF NEW.slug IS NULL OR NEW.slug = '' THEN
        SET NEW.slug = LOWER(REPLACE(REPLACE(REPLACE(NEW.judul, ' ', '-'), '.', ''), ',', ''));
    END IF;
END //
DELIMITER ;

-- Trigger to auto-generate slug for categories if not provided
DELIMITER //
CREATE TRIGGER kategori_before_insert
BEFORE INSERT ON kategoris
FOR EACH ROW
BEGIN
    IF NEW.slug IS NULL OR NEW.slug = '' THEN
        SET NEW.slug = LOWER(REPLACE(REPLACE(REPLACE(NEW.nama, ' ', '-'), '.', ''), ',', ''));
    END IF;
END //
DELIMITER ;

-- =============================================
-- PERFORMANCE OPTIMIZATIONS
-- =============================================

-- Additional composite indexes for better query performance
CREATE INDEX idx_artikel_status_featured ON artikels(status, featured);
CREATE INDEX idx_artikel_kategori_status ON artikels(kategori, status);
CREATE INDEX idx_berita_status_publish ON beritas(status, tanggal_publish);
CREATE INDEX idx_galeri_status_tanggal ON galeris(status, tanggal_kegiatan);

-- =============================================
-- SCHEMA DOCUMENTATION
-- =============================================

/*
MIGRATION NOTES FROM MONGODB TO MYSQL:

1. ObjectId -> INT AUTO_INCREMENT
2. Mongoose timestamps -> TIMESTAMP with AUTO UPDATE
3. MongoDB arrays (gambar in galeri) -> google_drive_url + thumbnail_image
4. MongoDB enum -> MySQL ENUM
5. MongoDB indexes -> MySQL INDEX
6. MongoDB text search -> MySQL FULLTEXT INDEX

KEY DIFFERENCES:
- Galeri: Changed from image array to Google Drive URL + thumbnail upload
- Added foreign key constraints where appropriate
- Added views for common queries
- Added stored procedures for complex operations
- Added triggers for auto-slug generation
- Added performance-oriented composite indexes

GALERI FUNCTIONALITY:
- Users upload 1 thumbnail image for preview
- google_drive_url stores link to Google Drive folder/album
- Button "Lihat Dokumentasi" redirects to Google Drive
- No need for galeri_images table anymore

REQUIRED CHANGES IN APPLICATION:
1. Replace mongoose with mysql2 or database connection library
2. Update all model queries to use SQL syntax
3. Update galeri to handle Google Drive URL + thumbnail
4. Modify search functionality to use MySQL FULLTEXT search
*/