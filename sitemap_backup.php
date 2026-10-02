<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// sitemap.php - Dynamic Sitemap Generator
header('Content-Type: application/xml; charset=utf-8');

require_once 'config/database.php';

// $base_url = 'https://madani.ukm.itera.ac.id';
$base_url = 'https://madani.my.id'

// Start XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

// Static pages
$static_pages = [
    [
        'url' => '/',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '1.00'
    ],
    [
        'url' => '/tentang.php',
        'lastmod' => '2025-01-15',
        'changefreq' => 'monthly',
        'priority' => '0.90'
    ],
    [
        'url' => '/artikel.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '0.85'
    ],
    [
        'url' => '/berita.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '0.85'
    ],
    [
        'url' => '/galeri.php',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.80'
    ]
];

// Output static pages
foreach ($static_pages as $page) {
    echo "  <url>\n";
    echo "    <loc>{$base_url}{$page['url']}</loc>\n";
    echo "    <lastmod>{$page['lastmod']}</lastmod>\n";
    echo "    <changefreq>{$page['changefreq']}</changefreq>\n";
    echo "    <priority>{$page['priority']}</priority>\n";
    echo "  </url>\n";
}

// Dynamic Articles
try {
    $artikel_query = "SELECT slug, updated_at, created_at, judul, gambar 
                      FROM artikel 
                      WHERE status = 'published' 
                      ORDER BY created_at DESC";
    $result = $conn->query($artikel_query);
    
    while ($artikel = $result->fetch_assoc()) {
        $lastmod = !empty($artikel['updated_at']) ? $artikel['updated_at'] : $artikel['created_at'];
        $lastmod = date('Y-m-d', strtotime($lastmod));
        
        echo "  <url>\n";
        echo "    <loc>{$base_url}/artikel-detail.php?slug=" . urlencode($artikel['slug']) . "</loc>\n";
        echo "    <lastmod>{$lastmod}</lastmod>\n";
        echo "    <changefreq>monthly</changefreq>\n";
        echo "    <priority>0.75</priority>\n";
        
        // Add image if exists
        if (!empty($artikel['gambar'])) {
            $image_url = $base_url . '/assets/uploads/artikel/' . $artikel['gambar'];
            echo "    <image:image>\n";
            echo "      <image:loc>{$image_url}</image:loc>\n";
            echo "      <image:title>" . htmlspecialchars($artikel['judul']) . "</image:title>\n";
            echo "    </image:image>\n";
        }
        
        echo "  </url>\n";
    }
} catch (Exception $e) {
    error_log("Sitemap error (artikel): " . $e->getMessage());
}

// Dynamic News/Berita
try {
    $berita_query = "SELECT slug, updated_at, created_at, tanggal_publish, judul, gambar 
                     FROM berita 
                     WHERE status = 'published' 
                     ORDER BY tanggal_publish DESC, created_at DESC";
    $result = $conn->query($berita_query);
    
    while ($berita = $result->fetch_assoc()) {
        $lastmod = !empty($berita['updated_at']) ? $berita['updated_at'] : $berita['created_at'];
        $lastmod = date('Y-m-d', strtotime($lastmod));
        
        $publish_date = !empty($berita['tanggal_publish']) ? $berita['tanggal_publish'] : $berita['created_at'];
        $is_recent = (time() - strtotime($publish_date)) < (30 * 24 * 60 * 60); // 30 days
        
        echo "  <url>\n";
        echo "    <loc>{$base_url}/berita-detail.php?slug=" . urlencode($berita['slug']) . "</loc>\n";
        echo "    <lastmod>{$lastmod}</lastmod>\n";
        echo "    <changefreq>yearly</changefreq>\n";
        echo "    <priority>0.70</priority>\n";
        
        // Add Google News markup for recent news
        if ($is_recent) {
            $pub_date = date('c', strtotime($publish_date));
            echo "    <news:news>\n";
            echo "      <news:publication>\n";
            echo "        <news:name>UKM Madani ITERA</news:name>\n";
            echo "        <news:language>id</news:language>\n";
            echo "      </news:publication>\n";
            echo "      <news:publication_date>{$pub_date}</news:publication_date>\n";
            echo "      <news:title>" . htmlspecialchars($berita['judul']) . "</news:title>\n";
            echo "    </news:news>\n";
        }
        
        // Add image if exists
        if (!empty($berita['gambar'])) {
            $image_url = $base_url . '/assets/uploads/berita/' . $berita['gambar'];
            echo "    <image:image>\n";
            echo "      <image:loc>{$image_url}</image:loc>\n";
            echo "      <image:title>" . htmlspecialchars($berita['judul']) . "</image:title>\n";
            echo "    </image:image>\n";
        }
        
        echo "  </url>\n";
    }
} catch (Exception $e) {
    error_log("Sitemap error (berita): " . $e->getMessage());
}

echo '</urlset>';
?>
