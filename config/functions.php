<?php
/**
 * Helper Functions untuk UKM Madani Website
 */

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Generate slug from title
function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/\s+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

// Upload image function
function uploadImage($file, $folder, $maxSize = MAX_FILE_SIZE) {
    $uploadDir = UPLOAD_PATH . $folder . '/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExtension, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Format file tidak diizinkan'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Ukuran file terlalu besar'];
    }
    
    $fileName = uniqid() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'filename' => $fileName];
    }
    
    return ['success' => false, 'message' => 'Gagal upload file'];
}

// Resize image function
function resizeImage($source, $destination, $width, $height) {
    $imageInfo = getimagesize($source);
    $imageType = $imageInfo[2];
    
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    $originalWidth = imagesx($image);
    $originalHeight = imagesy($image);
    
    $newImage = imagecreatetruecolor($width, $height);
    
    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $width, $height, $transparent);
    }
    
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $originalWidth, $originalHeight);
    
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            imagejpeg($newImage, $destination, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($newImage, $destination);
            break;
        case IMAGETYPE_GIF:
            imagegif($newImage, $destination);
            break;
    }
    
    imagedestroy($image);
    imagedestroy($newImage);
    
    return true;
}

// Format date to Indonesian
function formatDateIndonesian($date) {
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    
    return $day . ' ' . $month . ' ' . $year;
}

// Truncate text
function truncateText($text, $length = 150) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . '...';
}

// Get excerpt from content
function getExcerpt($content, $length = 150) {
    $content = strip_tags($content);
    return truncateText($content, $length);
}

// Generate pagination
function generatePagination($currentPage, $totalPages, $baseUrl) {
    $pagination = '';
    
    if ($totalPages <= 1) {
        return $pagination;
    }
    
    $pagination .= '<div class="pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $pagination .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="page-link">&laquo; Prev</a>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $currentPage) {
            $pagination .= '<span class="page-link active">' . $i . '</span>';
        } else {
            $pagination .= '<a href="' . $baseUrl . '?page=' . $i . '" class="page-link">' . $i . '</a>';
        }
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $pagination .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="page-link">Next &raquo;</a>';
    }
    
    $pagination .= '</div>';
    
    return $pagination;
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

// Redirect function
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Flash message functions
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Time ago function
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Baru saja';
    if ($time < 3600) return floor($time/60) . ' menit yang lalu';
    if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
    if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
    if ($time < 31104000) return floor($time/2592000) . ' bulan yang lalu';
    
    return floor($time/31104000) . ' tahun yang lalu';
}
?>