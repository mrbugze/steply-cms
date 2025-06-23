<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth/Auth.php';

$auth = new Auth($conn);

// If already logged in, redirect to appropriate dashboard
if ($auth->isLoggedIn()) {
    $role = $_SESSION["role"];
    $redirectPath = rtrim("/cms/", '/') . '/' . trim($role, '/') . '/index.php';
    header("Location: " . $redirectPath);
    exit;
}

$pageTitle = "Login";
$message = "";
$error = false;

// Handle login attempt
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usernameOrEmail = trim($_POST["username_or_email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($usernameOrEmail) || empty($password)) {
        $_SESSION['message'] = "Username/Email and password are required.";
        $_SESSION['error'] = true;
    } else {
        $loginResult = $auth->login($usernameOrEmail, $password);
        if ($loginResult === true) {
            // Redirect based on role
            $role = $_SESSION["role"];
            $redirectPath = rtrim("/cms/", '/') . '/' . trim($role, '/') . '/index.php';
            header("Location: " . $redirectPath);
            exit;
        } else {
            $_SESSION['message'] = $loginResult; // Error message from Auth class
            $_SESSION['error'] = true;
        }
    }
    // Redirect back to login page to show messages via session
    header("Location: login.php");
    exit;
}

// Include header - messages are now handled in header.php via session
include __DIR__ . '/../templates/partials/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your account to continue learning</p>
        </div>

        <form action="login.php" method="post" class="auth-form">
            <div class="floating-label-group">
                <input type="text" id="username_or_email" name="username_or_email" placeholder=" " required>
                <label for="username_or_email">Username or Email</label>
            </div>
            
            <div class="floating-label-group">
                <input type="password" id="password" name="password" placeholder=" " required>
                <label for="password">Password</label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-full ripple-effect">
                <span>Sign In</span>
                <div class="ripple"></div>
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php" class="auth-link">Create one here</a></p>
            <p><a href="reset_admin_password.php" class="auth-link">Forgot your password?</a></p>
        </div>
    </div>
</div>

<!-- Include modern JavaScript -->
<script src="/cms/public/js/modern-interactions.js"></script>
<script src="/cms/public/js/microinteractions.js"></script>

<style>
/* Additional login-specific styles */
.auth-form {
    margin-bottom: var(--space-8);
}

.auth-footer {
    text-align: center;
    padding-top: var(--space-6);
    border-top: 1px solid var(--border-color);
}

.auth-footer p {
    margin-bottom: var(--space-3);
    color: var(--text-secondary);
}

.auth-footer p:last-child {
    margin-bottom: 0;
}

.auth-link {
    color: var(--primary-600);
    text-decoration: none;
    font-weight: 500;
    transition: color var(--transition-fast);
}

.auth-link:hover {
    color: var(--primary-700);
    text-decoration: underline;
}

.btn-full {
    width: 100%;
    margin-top: var(--space-4);
}

/* Enhanced button styling */
.btn {
    position: relative;
    overflow: hidden;
    border: none;
    border-radius: var(--radius-lg);
    padding: var(--space-4) var(--space-6);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-normal);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
    color: white;
    box-shadow: var(--shadow-md);
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-700) 0%, var(--primary-800) 100%);
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.btn-primary:active {
    transform: translateY(0);
    box-shadow: var(--shadow-sm);
}

/* Ripple effect */
.ripple-effect {
    position: relative;
    overflow: hidden;
}

.ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: scale(0);
    animation: ripple-animation 0.6s linear;
    pointer-events: none;
}

@keyframes ripple-animation {
    to {
        transform: scale(4);
        opacity: 0;
    }
}
</style>

<script>
// Enhanced ripple effect
document.addEventListener('DOMContentLoaded', function() {
    const rippleButtons = document.querySelectorAll('.ripple-effect');
    
    rippleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = this.querySelector('.ripple');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple-animation 0.6s linear';
            
            setTimeout(() => {
                ripple.style.animation = '';
            }, 600);
        });
    });
});
</script>

<?php
// Include footer
include __DIR__ . '/../templates/partials/footer.php';
?>

