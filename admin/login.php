<?php
session_start();

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

require_once '../config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$login_attempts = 0;
$max_attempts = 5;
$lockout_time = 15 * 60; // 15 menit

// Check if IP is locked out
function isLockedOut($conn, $ip) {
    global $lockout_time, $max_attempts;
    
    try {
        // Clean old attempts (older than lockout time)
        $cleanup_stmt = $conn->prepare("DELETE FROM login_attempts WHERE attempt_time < (NOW() - INTERVAL ? SECOND)");
        $cleanup_stmt->bind_param("i", $lockout_time);
        $cleanup_stmt->execute();
        
        // Count recent attempts from this IP
        $check_stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL ? SECOND)");
        $check_stmt->bind_param("si", $ip, $lockout_time);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        return $row['attempts'] >= $max_attempts;
    } catch (Exception $e) {
        // If table doesn't exist, create it
        createLoginAttemptsTable($conn);
        return false;
    }
}

// Create login attempts table if it doesn't exist
function createLoginAttemptsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        username VARCHAR(100),
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        success BOOLEAN DEFAULT FALSE,
        user_agent TEXT,
        INDEX idx_ip_time (ip_address, attempt_time)
    )";
    $conn->query($sql);
}

// Log login attempt
function logLoginAttempt($conn, $ip, $username, $success = false) {
    try {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, success, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssbs", $ip, $username, $success, $user_agent);
        $stmt->execute();
    } catch (Exception $e) {
        createLoginAttemptsTable($conn);
        // Try again after creating table
        try {
            $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, success, user_agent) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssbs", $ip, $username, $success, $user_agent);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("Failed to log login attempt: " . $e->getMessage());
        }
    }
}

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

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate limiting
$user_ip = getRealIpAddr();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Token keamanan tidak valid. Silakan refresh halaman.';
    } else {
        // Check if locked out
        if (isLockedOut($conn, $user_ip)) {
            $error = 'Terlalu banyak percobaan login gagal. Akun dikunci selama 15 menit.';
        } else {
            // Sanitize input
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Validate input
            if (empty($username) || empty($password)) {
                $error = 'Username dan password harus diisi.';
                logLoginAttempt($conn, $user_ip, $username, false);
            } else {
                // Use prepared statement untuk mencegah SQL injection
                $stmt = $conn->prepare("SELECT id, username, password, nama_lengkap, email, status, last_login FROM admin WHERE username = ? AND status = 'aktif' LIMIT 1");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $admin = $result->fetch_assoc();
                    
                    // Verify password
                    if (password_verify($password, $admin['password'])) {
                        // Regenerate session ID untuk mencegah session fixation
                        session_regenerate_id(true);
                        
                        // Set session variables
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_nama'] = $admin['nama_lengkap'];
                        $_SESSION['admin_email'] = $admin['email'];
                        $_SESSION['login_time'] = time();
                        $_SESSION['last_activity'] = time();
                        $_SESSION['user_ip'] = $user_ip;
                        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                        
                        // Update last login
                        $update_stmt = $conn->prepare("UPDATE admin SET last_login = NOW(), login_count = login_count + 1 WHERE id = ?");
                        $update_stmt->bind_param("i", $admin['id']);
                        $update_stmt->execute();
                        
                        // Log successful attempt
                        logLoginAttempt($conn, $user_ip, $username, true);
                        
                        // Clear failed attempts for this IP
                        $clear_stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ? AND success = 0");
                        $clear_stmt->bind_param("s", $user_ip);
                        $clear_stmt->execute();
                        
                        // Redirect dengan secure headers
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error = 'Username atau password salah!';
                        logLoginAttempt($conn, $user_ip, $username, false);
                    }
                } else {
                    $error = 'Username atau password salah!';
                    logLoginAttempt($conn, $user_ip, $username, false);
                }
                
                // Add delay untuk slow down brute force attacks
                sleep(1);
            }
        }
    }
}

// Get current attempts count for display
try {
    $attempts_stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL ? SECOND) AND success = 0");
    $attempts_stmt->bind_param("si", $user_ip, $lockout_time);
    $attempts_stmt->execute();
    $attempts_result = $attempts_stmt->get_result();
    $attempts_row = $attempts_result->fetch_assoc();
    $login_attempts = $attempts_row['attempts'];
} catch (Exception $e) {
    $login_attempts = 0;
}

$remaining_attempts = $max_attempts - $login_attempts;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - UKM Madani</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Halaman login admin UKM Madani">
    
    <!-- Security Headers via Meta -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
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

        .login-container {
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

        .login-header {
            margin-bottom: 2rem;
        }

        .logo {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .brand-text {
            font-family: 'Amiri', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: var(--text-dark);
            opacity: 0.7;
            font-size: 0.95rem;
        }

        .security-info {
            background: rgba(26, 95, 63, 0.1);
            border: 1px solid rgba(26, 95, 63, 0.2);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 95, 63, 0.1);
            background: white;
        }

        .input-group {
            position: relative;
        }

        .input-group .form-control {
            padding-left: 3rem;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dark);
            opacity: 0.5;
            transition: all 0.3s ease;
        }

        .input-group:focus-within .input-icon {
            color: var(--primary-color);
            opacity: 1;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            background: #2a7f5f;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 95, 63, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .warning-message {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid #ffeaa7;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .attempts-counter {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: var(--danger-color);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .back-link {
            margin-top: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .back-link:hover {
            color: var(--secondary-color);
            background: rgba(212, 175, 55, 0.1);
            border-color: rgba(212, 175, 55, 0.2);
            transform: translateY(-2px);
        }

        .security-features {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .security-features h4 {
            color: var(--primary-color);
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .security-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }

        .security-badge {
            background: rgba(26, 95, 63, 0.1);
            color: var(--primary-color);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid rgba(26, 95, 63, 0.2);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
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
        }

        /* Anti-bot protection styles */
        .honeypot {
            position: absolute !important;
            left: -9999px !important;
            top: -9999px !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">🕌</div>
            <h1 class="brand-text">ADMIN PANEL</h1>
            <p class="login-subtitle">UKM Madani Institut Teknologi Sumatera</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($login_attempts > 0 && $remaining_attempts > 0): ?>
            <div class="attempts-counter">
                <i class="fas fa-exclamation-circle"></i>
                Sisa percobaan: <?= $remaining_attempts ?> dari <?= $max_attempts ?>
            </div>
        <?php endif; ?>

        <?php if ($remaining_attempts <= 2 && $remaining_attempts > 0): ?>
            <div class="warning-message">
                <i class="fas fa-warning"></i>
                Peringatan: Akun akan dikunci setelah <?= $remaining_attempts ?> percobaan gagal lagi
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" id="loginForm">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <!-- Honeypot field untuk anti-bot -->
            <input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off">

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           required 
                           autocomplete="username"
                           maxlength="50"
                           pattern="[a-zA-Z0-9_-]+"
                           title="Username hanya boleh mengandung huruf, angka, underscore, dan dash">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required 
                           autocomplete="current-password"
                           maxlength="100">
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <a href="../index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> 
            Kembali ke Website
        </a>

    </div>

    <script>
        // Anti-tampering protection
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const honeypot = document.querySelector('input[name="website"]');
            if (honeypot.value !== '') {
                e.preventDefault();
                return false;
            }
            
            // Disable button to prevent double submission
            const loginBtn = document.getElementById('loginBtn');
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            
            // Re-enable button after 3 seconds if no redirect
            setTimeout(() => {
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
            }, 3000);
        });

        // Clear form on page unload for security
        window.addEventListener('beforeunload', function() {
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
        });

        // Focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Basic client-side validation
        document.getElementById('username').addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z0-9_-]/g, '');
        });

        // Prevent right-click on form (mild deterrent)
        document.getElementById('loginForm').addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        // Log security event (for demonstration - in production, send to server)
        function logSecurityEvent(event) {
            console.log('Security Event:', event, 'Time:', new Date().toISOString());
        }

        // Monitor for potential attacks
        let keystrokes = 0;
        document.addEventListener('keydown', function() {
            keystrokes++;
            if (keystrokes > 1000) { // Excessive keystrokes might indicate automated attack
                logSecurityEvent('Excessive keyboard activity detected');
            }
        });

        console.log('🔒 Secure admin login loaded with enhanced security features');
        console.log('⚡ Features: CSRF protection, rate limiting, IP monitoring, session security');
    </script>
</body>
</html>