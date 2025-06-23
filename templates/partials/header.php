<?php
// config/db.php should have started the session
// Ensure Auth class is available
if (!isset($auth) || !($auth instanceof Auth)) {
    // Attempt to instantiate Auth if not already done (e.g., if header is included directly)
    // This requires $conn to be available
    if (isset($conn)) {
        require_once __DIR__ . 
            '/../../src/Auth/Auth.php'; // Corrected path
        $auth = new Auth($conn);
    } else {
        // Fallback if $conn is not available - treat as logged out
        error_log("Auth object not available and DB connection missing in header.php");
        $auth = null; // Ensure $auth exists but indicates failure
    }
}

// Fetch site name (replace with actual logic later if needed)
$site_name = "Steply"; 

// Use Auth object methods for consistency
$isLoggedIn = $auth ? $auth->isLoggedIn() : false;
$userRole = $isLoggedIn ? $auth->getRole() : null;
$username = $isLoggedIn ? $auth->getUsername() : "Guest";

// Determine header class based on role for specific styling (PHP 7.x compatible)
$headerClass = ''; // Default value
if ($userRole) {
    switch ($userRole) {
        case 'admin':
            $headerClass = 'admin-header';
            break;
        case 'instructor':
            $headerClass = 'instructor-header';
            break;
        case 'student':
            $headerClass = 'student-header';
            break;
        default:
            $headerClass = '';
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " - " : ""; ?><?php echo $site_name; ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Modern CSS -->
    <link rel="stylesheet" href="/cms/public/css/modern-style.css">
    
    <!-- Swiper CSS for course slider -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    
    <!-- Additional CSS for enhanced animations -->
    <style>
        /* Page-specific enhancements */
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1;
            padding-top: 5rem; /* Account for fixed header */
        }
        
        /* Enhanced form styling */
        .auth-container {
            min-height: calc(100vh - 5rem);
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-50) 0%, var(--secondary-50) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)"/><circle cx="800" cy="300" r="150" fill="url(%23a)"/><circle cx="400" cy="700" r="120" fill="url(%23a)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }
        
        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-2xl);
            padding: var(--space-12);
            box-shadow: var(--shadow-2xl);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 2;
            animation: slideInUp 0.6s ease-out;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: var(--space-8);
        }
        
        .auth-title {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
        }
        
        .auth-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }
        
        /* Enhanced floating labels */
        .floating-label-group {
            position: relative;
            margin-bottom: var(--space-6);
        }
        
        .floating-label-group input {
            width: 100%;
            padding: var(--space-4) var(--space-4) var(--space-3) var(--space-4);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            font-size: 1rem;
            background: var(--bg-primary);
            transition: all var(--transition-normal);
            outline: none;
        }
        
        .floating-label-group input:focus {
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .floating-label-group label {
            position: absolute;
            left: var(--space-4);
            top: 50%;
            transform: translateY(-50%);
            background: var(--bg-primary);
            padding: 0 var(--space-2);
            color: var(--text-tertiary);
            font-size: 1rem;
            transition: all var(--transition-normal);
            pointer-events: none;
            z-index: 1;
        }
        
        .floating-label-group input:focus + label,
        .floating-label-group input:not(:placeholder-shown) + label {
            top: 0;
            transform: translateY(-50%) scale(0.85);
            color: var(--primary-600);
            font-weight: 500;
        }
        
        /* Course detail enhancements */
        .course-hero {
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-800) 100%);
            color: white;
            padding: var(--space-20) 0;
            position: relative;
            overflow: hidden;
        }
        
        .course-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="100" cy="100" r="50" fill="url(%23a)"/><circle cx="900" cy="200" r="80" fill="url(%23a)"/><circle cx="300" cy="800" r="60" fill="url(%23a)"/></svg>');
            animation: float 30s ease-in-out infinite;
        }
        
        .course-image-container {
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-xl);
        }
        
        .course-image-container img {
            transition: transform var(--transition-slow);
        }
        
        .course-image-container:hover img {
            transform: scale(1.05);
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-normal);
        }
        
        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Notification system */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInRight 0.3s ease-out;
            max-width: 400px;
        }
        
        .notification-success {
            background: var(--success-50);
            color: var(--success-700);
            border: 1px solid var(--success-200);
        }
        
        .notification-error {
            background: var(--danger-50);
            color: var(--danger-700);
            border: 1px solid var(--danger-200);
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity var(--transition-fast);
        }
        
        .notification-close:hover {
            opacity: 1;
        }
    </style>
</head>
<body class="page-wrapper">
    <!-- Scroll progress indicator -->
    <div class="scroll-progress" id="scrollProgress"></div>
    
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <p>Loading...</p>
    </div>
    
    <header class="site-header <?php echo $headerClass; ?>">
        <div class="container">
            <nav class="navbar">
                <div class="brand">
                    <a href="/cms/public/index.php"><?php echo $site_name; ?></a>
                </div>
                
                <!-- Mobile menu toggle -->
                <button class="mobile-menu-toggle" aria-label="Toggle menu">
                    <div class="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </button>
                
                <ul class="nav-links">
                    <?php if ($isLoggedIn): ?>
                        <li><span class="welcome-msg">Welcome, <?php echo htmlspecialchars($username); ?>!</span></li>
                        <?php if ($userRole === 'admin'): ?>
                            <li><a href="/cms/admin/index.php">Admin Dashboard</a></li>
                        <?php elseif ($userRole === 'instructor'): ?>
                            <li><a href="/cms/instructor/index.php">Instructor Dashboard</a></li>
                            <li><a href="/cms/instructor/manage_courses.php">My Courses</a></li>
                            <li><a href="/cms/instructor/manage_students.php">My Students</a></li> 
                        <?php elseif ($userRole === 'student'): ?>
                            <li><a href="/cms/student/index.php">Student Dashboard</a></li>
                            <li><a href="/cms/student/my_courses.php">My Courses</a></li>
                            <li><a href="/cms/student/wallet.php">My Wallet</a></li>
                        <?php endif; ?>
                        <li><a href="/cms/public/index.php">Course Catalog</a></li>
                        <li><a href="/cms/public/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="/cms/public/index.php">Course Catalog</a></li>
                        <li><a href="/cms/public/login.php">Login</a></li>
                        <li><a href="/cms/public/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <?php
        // Display session messages using the new alert styles
        if (session_status() == PHP_SESSION_ACTIVE) {
             $message_text = null;
             $message_type = 'info'; // Default type

             if (isset($_SESSION['message'])) {
                 $message_text = $_SESSION['message'];
                 $message_type = isset($_SESSION['error']) && $_SESSION['error'] ? 'error' : 'success';
                 unset($_SESSION['message']);
                 unset($_SESSION['error']);
             } elseif (isset($_GET['message'])) { // Also check GET params for messages
                 $message_text = $_GET['message'];
                 $message_type = isset($_GET['error']) ? 'error' : 'success';
             } elseif (isset($_GET['error'])) {
                 $message_text = $_GET['error'];
                 $message_type = 'error';
             }

             if ($message_text) {
                 echo '<div class="notification notification-' . $message_type . '" id="sessionNotification">';
                 echo '<span>' . htmlspecialchars($message_text) . '</span>';
                 echo '<button class="notification-close" onclick="this.parentElement.remove()">&times;</button>';
                 echo '</div>';
                 echo '<script>setTimeout(() => { const notif = document.getElementById("sessionNotification"); if(notif) notif.remove(); }, 5000);</script>';
             }
        }
        ?>

    <script>
        // Scroll progress indicator
        window.addEventListener('scroll', () => {
            const scrollProgress = document.getElementById('scrollProgress');
            const scrollTop = document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrollPercent = (scrollTop / scrollHeight) * 100;
            scrollProgress.style.width = scrollPercent + '%';
        });
        
        // Loading overlay functions
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active');
        }
        
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }
        
        // Form submission loading
        document.addEventListener('DOMContentLoaded', () => {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', () => {
                    showLoading();
                });
            });
        });
    </script>

