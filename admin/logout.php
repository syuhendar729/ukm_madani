<?php
session_start();

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once '../config/database.php';

// Get real IP address
function getRealIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Log logout activity
function logLogoutActivity($conn, $admin_id, $username, $ip, $reason = 'manual') {
    try {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $conn->prepare("INSERT INTO logout_logs (admin_id, username, ip_address, user_agent, logout_reason, logout_time) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $admin_id, $username, $ip, $user_agent, $reason);
        $stmt->execute();
    } catch (Exception $e) {
        // Create table if it doesn't exist
        createLogoutLogsTable($conn);
        // Try again
        try {
            $stmt = $conn->prepare("INSERT INTO logout_logs (admin_id, username, ip_address, user_agent, logout_reason, logout_time) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("issss", $admin_id, $username, $ip, $user_agent, $reason);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log logout activity: " . $e->getMessage());
        }
    }
}

// Create logout logs table
function createLogoutLogsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS logout_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        username VARCHAR(100) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        logout_reason ENUM('manual', 'timeout', 'security', 'forced') DEFAULT 'manual',
        logout_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_time (admin_id, logout_time),
        INDEX idx_ip_time (ip_address, logout_time)
    )";
    $conn->query($sql);
}

// Update admin last activity
function updateLastActivity($conn, $admin_id) {
    try {
        $stmt = $conn->prepare("UPDATE admin SET last_activity = NOW() WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Failed to update last activity: " . $e->getMessage());
    }
}

// Secure logout function
function secureLogout($reason = 'manual') {
    global $conn;
    
    $user_ip = getRealIpAddr();
    $admin_id = $_SESSION['admin_id'] ?? null;
    $username = $_SESSION['admin_username'] ?? 'unknown';
    
    // Log the logout activity
    if ($admin_id && $conn) {
        logLogoutActivity($conn, $admin_id, $username, $user_ip, $reason);
        updateLastActivity($conn, $admin_id);
    }
    
    // Clear all session data
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Start new session for logout message
    session_start();
    session_regenerate_id(true);
    
    return true;
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['admin_id']);
$logout_message = '';
$logout_type = 'info';

// Handle different logout scenarios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection for POST logout
    if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])) {
        if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            if ($is_logged_in) {
                secureLogout('manual');
                $logout_message = 'Anda telah berhasil logout dari sistem.';
                $logout_type = 'success';
            } else {
                $logout_message = 'Anda sudah tidak dalam keadaan login.';
                $logout_type = 'warning';
            }
        } else {
            $logout_message = 'Token keamanan tidak valid.';
            $logout_type = 'error';
        }
    } else {
        $logout_message = 'Permintaan logout tidak valid.';
        $logout_type = 'error';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // GET logout (direct URL access)
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'true') {
        if ($is_logged_in) {
            secureLogout('manual');
            $logout_message = 'Anda telah berhasil logout dari sistem.';
            $logout_type = 'success';
        } else {
            $logout_message = 'Anda sudah tidak dalam keadaan login.';
            $logout_type = 'warning';
        }
    } elseif (isset($_GET['reason'])) {
        // Handle automatic logout reasons
        $reason = $_GET['reason'];
        switch ($reason) {
            case 'timeout':
                $logout_message = 'Sesi Anda telah berakhir karena tidak aktif terlalu lama.';
                $logout_type = 'warning';
                break;
            case 'security':
                $logout_message = 'Anda telah logout karena alasan keamanan.';
                $logout_type = 'error';
                break;
            case 'forced':
                $logout_message = 'Anda telah logout oleh administrator.';
                $logout_type = 'error';
                break;
            default:
                $logout_message = 'Anda telah logout dari sistem.';
                $logout_type = 'info';
        }
        
        if ($is_logged_in) {
            secureLogout($reason);
        }
    }
}

// Generate new CSRF token for logout confirmation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Auto-redirect after successful logout
$auto_redirect = false;
if ($logout_type === 'success') {
    $auto_redirect = true;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - UKM Madani</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Halaman logout admin UKM Madani">
    
    <!-- Security Headers via Meta -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1a5f3f;
            --secondary-color: #d4af37;
            --white: #ffffff;
            --text-dark: #2c3e50;
            --border-color: #e9ecef;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --gradient: linear-gradient(135deg, var(--primary-color), #2a7f5f);
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --success-color: #28a745;
            --info-color: #17a2b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="islamic-pattern" patternUnits="userSpaceOnUse" width="50" height="50"><circle cx="25" cy="25" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/><path d="M25,5 L30,20 L45,20 L33,30 L38,45 L25,35 L12,45 L17,30 L5,20 L20,20 Z" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23islamic-pattern)"/></svg>');
            overflow-x: hidden;
        }

        .logout-container {
            background: var(--white);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            text-align: center;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .logout-header {
            margin-bottom: 2rem;
        }

        .logo {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .brand-text {
            font-family: 'Amiri', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .logout-subtitle {
            color: var(--text-dark);
            opacity: 0.7;
            font-size: 0.95rem;
        }

        .message {
            padding: 1.2rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #b8daff;
        }

        .logout-confirm {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .logout-confirm h3 {
            color: var(--danger-color);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .logout-confirm p {
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn {
            flex: 1;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-danger {
            background: var(--danger-color);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: var(--white);
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: #2a7f5f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 95, 63, 0.3);
        }

        .navigation-links {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .nav-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .nav-link:hover {
            background: rgba(26, 95, 63, 0.1);
            border-color: rgba(26, 95, 63, 0.2);
            transform: translateY(-1px);
        }

        .security-info {
            background: rgba(26, 95, 63, 0.1);
            border: 1px solid rgba(26, 95, 63, 0.2);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: var(--primary-color);
        }

        .countdown {
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .logout-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
                max-width: none;
            }
            
            .brand-text {
                font-size: 1.5rem;
            }
            
            .logo {
                font-size: 3rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-header">
            <div class="logo">🕌</div>
            <h1 class="brand-text">LOGOUT</h1>
            <p class="logout-subtitle">UKM Madani Institut Teknologi Sumatera</p>
        </div>

        <?php if ($logout_message): ?>
            <div class="message <?= $logout_type ?>">
                <?php if ($logout_type === 'success'): ?>
                    <i class="fas fa-check-circle"></i>
                <?php elseif ($logout_type === 'error'): ?>
                    <i class="fas fa-exclamation-triangle"></i>
                <?php elseif ($logout_type === 'warning'): ?>
                    <i class="fas fa-exclamation-circle"></i>
                <?php else: ?>
                    <i class="fas fa-info-circle"></i>
                <?php endif; ?>
                <?= htmlspecialchars($logout_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($is_logged_in && !$logout_message): ?>
            <!-- Logout Confirmation -->
            <div class="logout-confirm">
                <h3>
                    <i class="fas fa-sign-out-alt"></i>
                    Konfirmasi Logout
                </h3>
                <p>Apakah Anda yakin ingin keluar dari sistem admin? Semua sesi akan dihapus untuk keamanan.</p>
                
                <form method="POST" id="logoutForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <div class="btn-group">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i>
                            Ya, Logout
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="navigation-links">
            <?php if (!$is_logged_in): ?>
                <a href="login.php" class="nav-link btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Login Kembali
                </a>
            <?php endif; ?>
            
            <a href="../index.php" class="nav-link">
                <i class="fas fa-home"></i>
                Kembali ke Website
            </a>
        </div>

        <div class="security-info">
            <i class="fas fa-shield-alt"></i>
            Logout dilakukan dengan aman. Semua data sesi telah dihapus.
            <?php if ($auto_redirect): ?>
                <br><br>
                <span class="countdown">Redirecting in <span id="countdown">5</span> seconds...</span>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-redirect after successful logout
        <?php if ($auto_redirect): ?>
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            if (countdownElement) {
                countdownElement.textContent = countdown;
            }
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = 'login.php';
            }
        }, 1000);
        
        // Allow user to cancel redirect by clicking anywhere
        document.addEventListener('click', () => {
            clearInterval(timer);
            if (countdownElement) {
                countdownElement.parentElement.innerHTML = '<i class="fas fa-shield-alt"></i> Logout berhasil. Redirect dibatalkan.';
            }
        });
        <?php endif; ?>

        // Prevent back button after logout
        <?php if (!$is_logged_in && $logout_message): ?>
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function() {
            window.history.go(1);
        };
        <?php endif; ?>

        // Clear any sensitive data from memory
        window.addEventListener('beforeunload', function() {
            // Clear any form data
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input');
                inputs.forEach(input => {
                    if (input.type !== 'hidden') {
                        input.value = '';
                    }
                });
            });
        });

        // Disable right-click context menu
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        // Monitor for security events
        function logSecurityEvent(event) {
            console.log('Security Event:', event, 'Time:', new Date().toISOString());
        }

        // Log logout page access
        logSecurityEvent('Logout page accessed');

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        console.log('🔒 Secure logout system loaded');
        console.log('🛡️ Security features active: CSRF protection, session cleanup, redirect protection');
    </script>
</body>
</html>