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

$user_id_to_delete = intval($_GET['id']);
$current_user_id = $_SESSION['user_id'];

// Prevent admin from deleting themselves
if ($user_id_to_delete === $current_user_id) {
    header("Location: manage_users.php?error=You cannot delete your own account.");
    exit;
}

// Check if the user exists before attempting deletion
$stmtCheck = $conn->prepare("SELECT user_id FROM users WHERE user_id = :user_id");
$stmtCheck->bindParam(':user_id', $user_id_to_delete, PDO::PARAM_INT);
$stmtCheck->execute();
if (!$stmtCheck->fetch()) {
    header("Location: manage_users.php?error=User not found.");
    exit;
}

// Proceed with deletion (consider related data - cascading deletes or manual cleanup might be needed in a real app)
try {
    $conn->beginTransaction();

    // Example: Delete related wallet first (adjust based on actual schema constraints)
    $stmtWallet = $conn->prepare("DELETE FROM wallets WHERE user_id = :user_id");
    $stmtWallet->bindParam(':user_id', $user_id_to_delete, PDO::PARAM_INT);
    $stmtWallet->execute();

    // Delete the user
    $stmtDelete = $conn->prepare("DELETE FROM users WHERE user_id = :user_id");
    $stmtDelete->bindParam(':user_id', $user_id_to_delete, PDO::PARAM_INT);
    
    if ($stmtDelete->execute()) {
        $conn->commit();
        header("Location: manage_users.php?message=User deleted successfully.");
        exit;
    } else {
        $conn->rollBack();
        header("Location: manage_users.php?error=Failed to delete user.");
        exit;
    }
} catch (PDOException $e) {
    $conn->rollBack();
    // Log the error in a real application: error_log("Error deleting user: " . $e->getMessage());
    header("Location: manage_users.php?error=An error occurred during deletion. Check related data.");
    exit;
}

?>

