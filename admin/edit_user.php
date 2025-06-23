<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Edit User";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages

$message = ""; // Local message for form errors
$error = false;
$user = null;

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid user ID.";
    $_SESSION['error'] = true;
    header("Location: manage_users.php");
    exit;
}

$user_id = intval($_GET['id']);

// Fetch user data
$stmt = $conn->prepare("SELECT user_id, username, email, role FROM users WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['message'] = "User not found.";
    $_SESSION['error'] = true;
    header("Location: manage_users.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'student';
    $password = $_POST['password'] ?? ''; // Optional: only update if provided

    if (empty($username) || empty($email)) {
        $message = "Username and Email are required.";
        $error = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $error = true;
    } elseif (!in_array($role, ['student', 'instructor', 'admin'])) {
        $message = "Invalid role selected.";
        $error = true;
    } else {
        // Check if username or email already exists (excluding the current user)
        $stmtCheck = $conn->prepare("SELECT user_id FROM users WHERE (username = :username OR email = :email) AND user_id != :user_id");
        $stmtCheck->bindParam(':username', $username);
        $stmtCheck->bindParam(':email', $email);
        $stmtCheck->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmtCheck->execute();
        if ($stmtCheck->fetch()) {
            $message = "Username or Email already exists for another user.";
            $error = true;
        } else {
            // Prepare update statement
            $sql = "UPDATE users SET username = :username, email = :email, role = :role";
            $params = [
                ':username' => $username,
                ':email' => $email,
                ':role' => $role,
                ':user_id' => $user_id
            ];

            // Update password only if a new one is provided
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", password_hash = :password_hash";
                $params[':password_hash'] = $password_hash;
            }

            $sql .= " WHERE user_id = :user_id";

            $stmtUpdate = $conn->prepare($sql);

            if ($stmtUpdate->execute($params)) {
                $_SESSION['message'] = "User updated successfully.";
                $_SESSION['error'] = false;
                header("Location: manage_users.php");
                exit;
            } else {
                error_log("Error updating user: " . implode(", ", $stmtUpdate->errorInfo()));
                $message = "Failed to update user. Please try again.";
                $error = true;
            }
        }
    }
    // If there was an error, repopulate user data with submitted values for the form
    $user['username'] = $username;
    $user['email'] = $email;
    $user['role'] = $role;
}

?>

<div class="form-wrapper">
    <h2 class="text-center mb-4">Edit User (ID: <?php echo htmlspecialchars($user['user_id']); ?>)</h2>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $error ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="edit_user.php?id=<?php echo $user['user_id']; ?>" method="post">
        <div class="form-group mb-3">
            <label for="username" class="form-label">Username:</label>
            <input type="text" id="username" name="username" class="form-control" required value="<?php echo htmlspecialchars($user['username']); ?>">
        </div>
        <div class="form-group mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user['email']); ?>">
        </div>
        <div class="form-group mb-3">
            <label for="password" class="form-label">New Password:</label>
            <input type="password" id="password" name="password" class="form-control">
            <div class="form-text">Leave blank to keep the current password.</div>
        </div>
        <div class="form-group mb-3">
            <label for="role" class="form-label">Role:</label>
            <select id="role" name="role" class="form-control">
                <option value="student" <?php echo ($user['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                <option value="instructor" <?php echo ($user['role'] == 'instructor') ? 'selected' : ''; ?>>Instructor</option>
                <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>
        <div class="d-flex justify-content-end mt-4">
            <a href="manage_users.php" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Update User</button>
        </div>
    </form>
</div>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

