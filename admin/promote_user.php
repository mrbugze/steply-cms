<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

// Check if user ID is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_users.php?error=Invalid user ID.");
    exit;
}

$user_id_to_promote = intval($_GET['id']);

// Check if the user exists and is currently a student
$stmtCheck = $conn->prepare("SELECT user_id, role FROM users WHERE user_id = :user_id AND role = 'student'");
$stmtCheck->bindParam(':user_id', $user_id_to_promote, PDO::PARAM_INT);
$stmtCheck->execute();
if (!$stmtCheck->fetch()) {
    header("Location: manage_users.php?error=User not found or is not a student.");
    exit;
}

// Proceed with promotion
$stmtPromote = $conn->prepare("UPDATE users SET role = 'instructor' WHERE user_id = :user_id");
$stmtPromote->bindParam(':user_id', $user_id_to_promote, PDO::PARAM_INT);

if ($stmtPromote->execute()) {
    header("Location: manage_users.php?message=User promoted to Instructor successfully.");
    exit;
} else {
    header("Location: manage_users.php?error=Failed to promote user.");
    exit;
}
?>

