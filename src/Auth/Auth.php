<?php

class Auth {
    private $conn; // PDO connection object
    private $user_id;
    private $username;
    private $role;

    public function __construct($db_connection) {
        $this->conn = $db_connection; // Expecting a PDO object
        // Session should be started by config/db.php
        $this->checkLogin();
    }

    public function register($username, $email, $password) {
        // Basic validation
        if (empty($username) || empty($email) || empty($password)) {
            return "All fields are required.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format.";
        }

        try {
            // Check if username or email already exists
            $stmt = $this->conn->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            if ($stmt->fetch()) {
                return "Username or email already exists.";
            }

            // Hash the password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            if ($password_hash === false) {
                error_log("Password hashing failed.");
                return "Error processing registration.";
            }

            // Insert new user (default role is student)
            $stmt = $this->conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, 'student')");
            $stmt->bindParam(":username", $username);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password_hash", $password_hash);

            if ($stmt->execute()) {
                $new_user_id = $this->conn->lastInsertId();

                // Create a wallet for the new user
                $stmt_wallet = $this->conn->prepare("INSERT INTO wallets (user_id, balance, currency) VALUES (:user_id, 0.00, 'USD')");
                $stmt_wallet->bindParam(":user_id", $new_user_id);
                if ($stmt_wallet->execute()) {
                    return true; // Registration successful
                } else {
                    error_log("Execute failed for wallet: " . implode(" ", $stmt_wallet->errorInfo()));
                    // Consider cleanup if wallet creation fails
                    return "User registered, but failed to create wallet.";
                }
            } else {
                error_log("Execute failed for user: " . implode(" ", $stmt->errorInfo()));
                return "Registration failed. Please try again.";
            }
        } catch (PDOException $e) {
            error_log("Database error during registration: " . $e->getMessage());
            return "Database error during registration. Please check logs.";
        }
    }

    public function login($usernameOrEmail, $password) {
        if (empty($usernameOrEmail) || empty($password)) {
            return "Username/Email and password are required.";
        }

        try {
            // Check if input is email or username
            $field = filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            $sql = "SELECT user_id, username, password_hash, role FROM users WHERE $field = :identifier";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(":identifier", $usernameOrEmail);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user["password_hash"])) {
                // Password is correct, set session variables
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];
                $this->user_id = $user["user_id"];
                $this->username = $user["username"];
                $this->role = $user["role"];
                return true; // Login successful
            } else {
                return "Invalid username/email or password.";
            }
        } catch (PDOException $e) {
            error_log("Database error during login: " . $e->getMessage());
            return "Database error during login. Please check logs.";
        }
    }

    public function logout() {
        // Unset all session variables
        $_SESSION = array();

        // Destroy the session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), 
                '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        // Reset object properties
        $this->user_id = null;
        $this->username = null;
        $this->role = null;
        
    }

    public function isLoggedIn() {
        return isset($this->user_id);
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getRole() {
        return $this->role;
    }

    private function checkLogin() {
        if (isset($_SESSION["user_id"])) {
            // Optionally, verify the user still exists and role hasn't changed in DB
            $this->user_id = $_SESSION["user_id"];
            $this->username = $_SESSION["username"];
            $this->role = $_SESSION["role"];
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the current user has the required role(s).
     * Redirects to login page if not logged in, or shows an error if role doesn't match.
     *
     * @param array|string $requiredRoles Role(s) required. Can be a string for a single role or an array for multiple allowed roles.
     * @param string $redirectPath Path to redirect if not logged in (usually login page).
     */
    public function checkRole($requiredRoles, $redirectPath = 
        '/cms/public/login.php') {
        if (!$this->isLoggedIn()) {
            header("Location: " . $redirectPath . "?error=login_required");
            exit;
        }

        if (!is_array($requiredRoles)) {
            $requiredRoles = [$requiredRoles];
        }

        if (!in_array($this->role, $requiredRoles)) {
            // Optionally, redirect to a specific 'access denied' page or just show a message
            // For simplicity, we'll redirect to their respective dashboard with an error message
            $dashboardPath = 
                '/cms/' . $this->role . '/index.php';
            $_SESSION["error_message"] = "You do not have permission to access this page.";
            // Ensure no output before header
            if (!headers_sent()) {
                 header("Location: " . $dashboardPath);
            } else {
                // Fallback if headers already sent
                echo "<p>Access Denied. Redirecting...</p><script>window.location.href='" . $dashboardPath . "';</script>";
            }
            exit;
            // Or: die("Access Denied. You do not have the required permissions.");
        }
        // If the role is correct, execution continues
    }

    // Helper function to get user details (can be expanded)
    public function getUserDetails($userId) {
        try {
            $stmt = $this->conn->prepare("SELECT user_id, username, email, role, created_at FROM users WHERE user_id = :user_id");
            $stmt->bindParam(":user_id", $userId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? $user : null;
        } catch (PDOException $e) {
            error_log("Database error fetching user details: " . $e->getMessage());
            return null;
        }
    }
}
?>
