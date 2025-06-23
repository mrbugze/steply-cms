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

$user_id_to_demote = intval($_GET['id']);

// Check if the user exists and is currently an instructor
$stmtCheck = $conn->prepare("SELECT user_id, role FROM users WHERE user_id = :user_id AND role = 'instructor'");
$stmtCheck->bindParam(':user_id', $user_id_to_demote, PDO::PARAM_INT);
$stmtCheck->execute();
if (!$stmtCheck->fetch()) {
    header("Location: manage_users.php?error=User not found or is not an instructor.");
    exit;
}

// Proceed with demotion
$stmtDemote = $conn->prepare("UPDATE users SET role = 'student' WHERE user_id = :user_id");
$stmtDemote->bindParam(':user_id', $user_id_to_demote, PDO::PARAM_INT);

if ($stmtDemote->execute()) {
    header("Location: manage_users.php?message=User demoted to Student successfully.");
    exit;
} else {
    header("Location: manage_users.php?error=Failed to demote user.");
    exit;
}
?>

