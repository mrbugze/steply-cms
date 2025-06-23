<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth/Auth.php';
$auth = new Auth($conn);

// If already logged in, redirect to appropriate dashboard
if ($auth->isLoggedIn()) {
    $role = $_SESSION['role'];
    $redirectPath = rtrim("/cms/", '/') . '/' . trim($role, '/') . '/index.php';
    header("Location: " . $redirectPath);
    exit;
}

$pageTitle = "Register";
$message = "";
$error = false;

// Handle registration attempt
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   $username = trim($_POST['username'] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirm_password = $_POST["confirm_password"] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['message'] = "All fields are required.";
        $_SESSION['error'] = true;
    } elseif ($password !== $confirm_password) {
        $_SESSION['message'] = "Passwords do not match.";
        $_SESSION['error'] = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = "Invalid email format.";
        $_SESSION['error'] = true;
    } else {
        $registrationResult = $auth->register($username, $email, $password);
        if ($registrationResult === true) {
            // Automatically log in the user after successful registration
            if ($auth->login($username, $password) === true) {
                $role = $_SESSION['role'];
                $_SESSION['message'] = "Registration successful! Welcome.";
                $_SESSION['error'] = false;
                $redirectPath = rtrim("/cms/", '/') . '/' . trim($role, '/') . '/index.php';
                header("Location: " . $redirectPath);
                exit;
            } else {
                // Should not happen if registration succeeded, but handle just in case
                $_SESSION['message'] = "Registration successful, but auto-login failed. Please log in manually.";
                $_SESSION['error'] = false; // Not an error state for the registration page itself
                header("Location: login.php"); // Redirect to login page
                exit;
            }
        } else {
            $_SESSION['message'] = $registrationResult; // Error message from Auth class
            $_SESSION['error'] = true;
        }
    }
    // Redirect back to register page to show messages via session
    header("Location: register.php");
    exit;
}

// Include header - messages are now handled in header.php via session
include __DIR__ . '/../templates/partials/header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">Join Steply</h1>
            <p class="auth-subtitle">Create your account and start your learning journey</p>
        </div>

        <form action="register.php" method="post" class="auth-form" id="registerForm">
            <div class="floating-label-group">
                <input type="text" id="username" name="username" placeholder=" " required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <label for="username">Username</label>
                <div class="field-feedback" id="usernameFeedback"></div>
            </div>
            
            <div class="floating-label-group">
                <input type="email" id="email" name="email" placeholder=" " required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <label for="email">Email Address</label>
                <div class="field-feedback" id="emailFeedback"></div>
            </div>
            
            <div class="floating-label-group">
                <input type="password" id="password" name="password" placeholder=" " required>
                <label for="password">Password</label>
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar">
                        <div class="strength-fill"></div>
                    </div>
                    <div class="strength-text">Password strength</div>
                </div>
                <div class="field-feedback" id="passwordFeedback"></div>
            </div>
            
            <div class="floating-label-group">
                <input type="password" id="confirm_password" name="confirm_password" placeholder=" " required>
                <label for="confirm_password">Confirm Password</label>
                <div class="field-feedback" id="confirmPasswordFeedback"></div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-full ripple-effect" id="submitBtn">
                <span>Create Account</span>
                <div class="ripple"></div>
            </button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php" class="auth-link">Sign in here</a></p>
        </div>
    </div>
</div>

<!-- Include modern JavaScript -->
<script src="/cms/public/js/modern-interactions.js"></script>
<script src="/cms/public/js/microinteractions.js"></script>

<style>
/* Additional register-specific styles */
.auth-form {
    margin-bottom: var(--space-8);
}

.auth-footer {
    text-align: center;
    padding-top: var(--space-6);
    border-top: 1px solid var(--border-color);
}

.auth-footer p {
    margin-bottom: 0;
    color: var(--text-secondary);
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

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

/* Password strength indicator */
.password-strength {
    margin-top: var(--space-2);
    opacity: 0;
    transition: opacity var(--transition-normal);
}

.password-strength.visible {
    opacity: 1;
}

.strength-bar {
    height: 4px;
    background: var(--gray-200);
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: var(--space-1);
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all var(--transition-normal);
    border-radius: 2px;
}

.strength-fill.weak {
    width: 25%;
    background: var(--danger-500);
}

.strength-fill.fair {
    width: 50%;
    background: var(--warning-500);
}

.strength-fill.good {
    width: 75%;
    background: var(--primary-500);
}

.strength-fill.strong {
    width: 100%;
    background: var(--success-500);
}

.strength-text {
    font-size: 0.875rem;
    color: var(--text-tertiary);
}

/* Field feedback */
.field-feedback {
    margin-top: var(--space-1);
    font-size: 0.875rem;
    min-height: 1.25rem;
    opacity: 0;
    transition: opacity var(--transition-fast);
}

.field-feedback.visible {
    opacity: 1;
}

.field-feedback.success {
    color: var(--success-600);
}

.field-feedback.error {
    color: var(--danger-600);
}

.field-feedback.warning {
    color: var(--warning-600);
}

/* Enhanced floating labels for validation states */
.floating-label-group.success input {
    border-color: var(--success-500);
}

.floating-label-group.error input {
    border-color: var(--danger-500);
}

.floating-label-group.success label {
    color: var(--success-600);
}

.floating-label-group.error label {
    color: var(--danger-600);
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
// Enhanced form validation and interactions
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const submitBtn = document.getElementById('submitBtn');
    
    // Validation state
    const validation = {
        username: false,
        email: false,
        password: false,
        confirmPassword: false
    };
    
    // Username validation
    usernameInput.addEventListener('input', function() {
        const value = this.value.trim();
        const feedback = document.getElementById('usernameFeedback');
        const group = this.closest('.floating-label-group');
        
        if (value.length === 0) {
            setFieldState(group, feedback, '', '');
            validation.username = false;
        } else if (value.length < 3) {
            setFieldState(group, feedback, 'error', 'Username must be at least 3 characters');
            validation.username = false;
        } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
            setFieldState(group, feedback, 'error', 'Username can only contain letters, numbers, and underscores');
            validation.username = false;
        } else {
            setFieldState(group, feedback, 'success', 'Username looks good');
            validation.username = true;
        }
        updateSubmitButton();
    });
    
    // Email validation
    emailInput.addEventListener('input', function() {
        const value = this.value.trim();
        const feedback = document.getElementById('emailFeedback');
        const group = this.closest('.floating-label-group');
        
        if (value.length === 0) {
            setFieldState(group, feedback, '', '');
            validation.email = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            setFieldState(group, feedback, 'error', 'Please enter a valid email address');
            validation.email = false;
        } else {
            setFieldState(group, feedback, 'success', 'Email looks good');
            validation.email = true;
        }
        updateSubmitButton();
    });
    
    // Password validation and strength
    passwordInput.addEventListener('input', function() {
        const value = this.value;
        const feedback = document.getElementById('passwordFeedback');
        const group = this.closest('.floating-label-group');
        const strengthIndicator = document.getElementById('passwordStrength');
        const strengthFill = strengthIndicator.querySelector('.strength-fill');
        const strengthText = strengthIndicator.querySelector('.strength-text');
        
        if (value.length === 0) {
            setFieldState(group, feedback, '', '');
            strengthIndicator.classList.remove('visible');
            validation.password = false;
        } else {
            strengthIndicator.classList.add('visible');
            const strength = calculatePasswordStrength(value);
            
            // Update strength indicator
            strengthFill.className = 'strength-fill ' + strength.level;
            strengthText.textContent = strength.text;
            
            if (strength.score < 2) {
                setFieldState(group, feedback, 'error', 'Password is too weak');
                validation.password = false;
            } else if (strength.score < 3) {
                setFieldState(group, feedback, 'warning', 'Password could be stronger');
                validation.password = true;
            } else {
                setFieldState(group, feedback, 'success', 'Strong password');
                validation.password = true;
            }
        }
        
        // Re-validate confirm password
        if (confirmPasswordInput.value) {
            confirmPasswordInput.dispatchEvent(new Event('input'));
        }
        
        updateSubmitButton();
    });
    
    // Confirm password validation
    confirmPasswordInput.addEventListener('input', function() {
        const value = this.value;
        const feedback = document.getElementById('confirmPasswordFeedback');
        const group = this.closest('.floating-label-group');
        
        if (value.length === 0) {
            setFieldState(group, feedback, '', '');
            validation.confirmPassword = false;
        } else if (value !== passwordInput.value) {
            setFieldState(group, feedback, 'error', 'Passwords do not match');
            validation.confirmPassword = false;
        } else {
            setFieldState(group, feedback, 'success', 'Passwords match');
            validation.confirmPassword = true;
        }
        updateSubmitButton();
    });
    
    // Helper functions
    function setFieldState(group, feedback, state, message) {
        // Remove existing states
        group.classList.remove('success', 'error', 'warning');
        feedback.classList.remove('visible', 'success', 'error', 'warning');
        
        if (state && message) {
            group.classList.add(state);
            feedback.classList.add('visible', state);
            feedback.textContent = message;
        } else {
            feedback.textContent = '';
        }
    }
    
    function calculatePasswordStrength(password) {
        let score = 0;
        let feedback = [];
        
        // Length check
        if (password.length >= 8) score++;
        else feedback.push('at least 8 characters');
        
        // Lowercase check
        if (/[a-z]/.test(password)) score++;
        else feedback.push('lowercase letters');
        
        // Uppercase check
        if (/[A-Z]/.test(password)) score++;
        else feedback.push('uppercase letters');
        
        // Number check
        if (/\d/.test(password)) score++;
        else feedback.push('numbers');
        
        // Special character check
        if (/[^a-zA-Z\d]/.test(password)) score++;
        else feedback.push('special characters');
        
        const levels = ['weak', 'weak', 'fair', 'good', 'strong'];
        const texts = [
            'Very weak password',
            'Weak password',
            'Fair password',
            'Good password',
            'Strong password'
        ];
        
        return {
            score: score,
            level: levels[score] || 'weak',
            text: texts[score] || 'Very weak password'
        };
    }
    
    function updateSubmitButton() {
        const allValid = Object.values(validation).every(v => v === true);
        submitBtn.disabled = !allValid;
    }
    
    // Ripple effect
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
    
    // Initial validation state
    updateSubmitButton();
});
</script>

<?php
// Include footer
include __DIR__ . '/../templates/partials/footer.php';
?>

