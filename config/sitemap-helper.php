<?php
/**
 * Helper functions untuk sitemap management
 */

// Ping Google untuk update sitemap
function pingGoogleSitemap() {
    $sitemap_url = urlencode('https://madani.ukm.itera.ac.id/sitemap.php');
    $ping_url = "https://www.google.com/ping?sitemap=" . $sitemap_url;
    
    // Ping secara asynchronous dengan error handling
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ping_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'UKM Madani Sitemap Pinger');
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log hasil ping
    if ($http_code == 200) {
        error_log("Google sitemap ping successful");
        return true;
    } else {
        error_log("Google sitemap ping failed. HTTP Code: " . $http_code);
        return false;
    }
}

// Ping Bing untuk update sitemap
function pingBingSitemap() {
    $sitemap_url = urlencode('https://madani.ukm.itera.ac.id/sitemap.php');
    $ping_url = "https://www.bing.com/ping?sitemap=" . $sitemap_url;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ping_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_USERAGENT, 'UKM Madani Sitemap Pinger');
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        error_log("Bing sitemap ping successful");
        return true;
    } else {
        error_log("Bing sitemap ping failed. HTTP Code: " . $http_code);
        return false;
    }
}

// Ping semua search engines
function pingAllSearchEngines() {
    pingGoogleSitemap();
    pingBingSitemap();
}

// Rate limiting untuk ping (maksimal 1x per jam)
function pingSitemapWithRateLimit() {
    $last_ping_file = __DIR__ . '/../logs/last_sitemap_ping.txt';
    
    // Cek kapan terakhir ping
    if (file_exists($last_ping_file)) {
        $last_ping = (int)file_get_contents($last_ping_file);
        $now = time();
        
        // Jika belum 1 jam, skip ping
        if (($now - $last_ping) < 3600) {
            return false;
        }
    }
    
    // Ping dan simpan timestamp
    pingAllSearchEngines();
    file_put_contents($last_ping_file, time());
    return true;
}
?>
