<?php
require_once __DIR__ . '/../config/db.php';

$pageTitle = "Reset Admin Password";
include __DIR__ . '/../templates/partials/header.php';

$username_to_reset = 'admin';
$new_password = 'password123';

// Generate the hash for the new password
$new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);

if ($new_password_hash === false) {
    $error_message = "Error: Failed to hash the new password.";
} else {
    $success_message = "";
    $error_message = "";
    
    // Prepare the UPDATE statement
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    if (!$stmt) {
        $error_message = "Database Error: Prepare failed - (" . $conn->errno . ") " . $conn->error;
    } else {
        // Bind parameters and execute
        $stmt->bind_param("ss", $new_password_hash, $username_to_reset);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success_message = "Password for user '" . htmlspecialchars($username_to_reset) . "' has been updated successfully.";
                
                // Verify the update
                $stmt_verify = $conn->prepare("SELECT password_hash FROM users WHERE username = ?");
                if ($stmt_verify) {
                    $stmt_verify->bind_param("s", $username_to_reset);
                    $stmt_verify->execute();
                    $result_verify = $stmt_verify->get_result();
                    if ($result_verify->num_rows == 1) {
                        $user_verify = $result_verify->fetch_assoc();
                        $stored_hash_verify = $user_verify['password_hash'];
                        if (password_verify($new_password, $stored_hash_verify)) {
                            $success_message .= " Verification successful!";
                        } else {
                            $error_message = "Password updated but verification failed.";
                        }
                    }
                    $stmt_verify->close();
                }
            } else {
                $error_message = "Query executed, but no rows were updated. User '" . htmlspecialchars($username_to_reset) . "' might not exist.";
            }
        } else {
            $error_message = "Failed to execute the update query - (" . $stmt->errno . ") " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1 class="auth-title">Admin Password Reset</h1>
            <p class="auth-subtitle">Reset the admin password to default credentials</p>
        </div>

        <div class="reset-info">
            <div class="info-item">
                <span class="info-label">Username:</span>
                <span class="info-value"><?php echo htmlspecialchars($username_to_reset); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">New Password:</span>
                <span class="info-value"><?php echo htmlspecialchars($new_password); ?></span>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <div class="alert-icon">✓</div>
                <div class="alert-content">
                    <h4>Success!</h4>
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <div class="alert-icon">⚠</div>
                <div class="alert-content">
                    <h4>Error</h4>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="reset-actions">
            <a href="/cms/public/login.php" class="btn btn-primary ripple-effect">
                <span>Go to Login</span>
                <div class="ripple"></div>
            </a>
            <a href="/cms/public/index.php" class="btn btn-outline ripple-effect">
                <span>Back to Home</span>
                <div class="ripple"></div>
            </a>
        </div>
    </div>
</div>

<!-- Include modern JavaScript -->
<script src="/cms/public/js/modern-interactions.js"></script>
<script src="/cms/public/js/microinteractions.js"></script>

<style>
/* Reset page specific styles */
.reset-info {
    background: var(--bg-secondary);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    margin-bottom: var(--space-6);
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-3) 0;
    border-bottom: 1px solid var(--border-color);
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 500;
    color: var(--text-secondary);
}

.info-value {
    font-weight: 600;
    color: var(--text-primary);
    font-family: var(--font-mono);
}

.alert {
    display: flex;
    align-items: flex-start;
    gap: var(--space-4);
    padding: var(--space-4);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-6);
}

.alert-success {
    background: var(--success-50);
    border: 1px solid var(--success-200);
    color: var(--success-800);
}

.alert-error {
    background: var(--danger-50);
    border: 1px solid var(--danger-200);
    color: var(--danger-800);
}

.alert-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.alert-success .alert-icon {
    background: var(--success-500);
    color: white;
}

.alert-error .alert-icon {
    background: var(--danger-500);
    color: white;
}

.alert-content h4 {
    margin: 0 0 var(--space-1) 0;
    font-size: 1rem;
    font-weight: 600;
}

.alert-content p {
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.5;
}

.reset-actions {
    display: flex;
    gap: var(--space-4);
    justify-content: center;
    flex-wrap: wrap;
}

.reset-actions .btn {
    flex: 1;
    min-width: 140px;
}

/* Responsive design */
@media (max-width: 480px) {
    .reset-actions {
        flex-direction: column;
    }
    
    .reset-actions .btn {
        width: 100%;
    }
    
    .info-item {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-1);
    }
}
</style>

<?php
include __DIR__ . '/../templates/partials/footer.php';
?>

