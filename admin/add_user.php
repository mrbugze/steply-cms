<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Add User";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages

$message = ""; // Local message for form errors, session used for success redirect
$error = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student'; // Default to student

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = "All fields are required.";
        $error = true;
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $error = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $error = true;
    } elseif (!in_array($role, ['student', 'instructor', 'admin'])) {
        $message = "Invalid role selected.";
        $error = true;
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->fetch()) {
            $message = "Username or Email already exists.";
            $error = true;
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $conn->beginTransaction();
            try {
                // Insert user
                $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password_hash', $password_hash);
                $stmt->bindParam(':role', $role);
                $stmt->execute();
                $newUserId = $conn->lastInsertId();

                // Initialize wallet for the new user
                if ($newUserId) {
                    $walletStmt = $conn->prepare("INSERT INTO wallets (user_id, balance) VALUES (:user_id, 0.00)");
                    $walletStmt->bindParam(':user_id', $newUserId);
                    $walletStmt->execute();
                }
                
                $conn->commit();
                $_SESSION['message'] = "User added successfully.";
                $_SESSION['error'] = false;
                header("Location: manage_users.php");
                exit;
            } catch (PDOException $e) {
                $conn->rollBack();
                error_log("Error adding user: " . $e->getMessage());
                $message = "Failed to add user. Please try again.";
                $error = true;
            }
        }
    }
}
?>

<div class="form-wrapper">
    <h2 class="text-center">Add New User</h2>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $error ? 'alert-danger' : 'alert-info'; ?>" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="add_user.php" method="post">
        <div class="form-group mb-3">
            <label for="username" class="form-label">Username:</label>
            <input type="text" id="username" name="username" class="form-control" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>
        <div class="form-group mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
        <div class="form-group mb-3">
            <label for="password" class="form-label">Password:</label>
            <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <div class="form-group mb-3">
            <label for="confirm_password" class="form-label">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>
        <div class="form-group mb-3">
            <label for="role" class="form-label">Role:</label>
            <select id="role" name="role" class="form-control">
                <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                <option value="instructor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Add User</button>
        </div>
    </form>
</div>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

