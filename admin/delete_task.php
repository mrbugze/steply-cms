<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

// Check if task ID is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_courses.php?error=Invalid task ID.");
    exit;
}

$task_id_to_delete = intval($_GET['id']);
$step_id_redirect = isset($_GET['step_id']) ? intval($_GET['step_id']) : null;

// Check if the task exists before attempting deletion
$stmtCheck = $conn->prepare("SELECT task_id, step_id FROM tasks WHERE task_id = :task_id");
$stmtCheck->bindParam(':task_id', $task_id_to_delete, PDO::PARAM_INT);
$stmtCheck->execute();
$taskData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$taskData) {
    // If step ID was passed for redirect, use it, otherwise go to general courses page
    $redirect_url = $step_id_redirect ? "manage_tasks.php?step_id=" . $step_id_redirect : "manage_courses.php";
    header("Location: " . $redirect_url . "?error=Task not found.");
    exit;
}

// Use the actual step ID from the task for accurate redirect if not provided
if ($step_id_redirect === null) {
    $step_id_redirect = $taskData['step_id'];
}

// Proceed with deletion
try {
    $stmtDelete = $conn->prepare("DELETE FROM tasks WHERE task_id = :task_id");
    $stmtDelete->bindParam(':task_id', $task_id_to_delete, PDO::PARAM_INT);

    if ($stmtDelete->execute()) {
        header("Location: manage_tasks.php?step_id=" . $step_id_redirect . "&message=Task deleted successfully.");
        exit;
    } else {
        header("Location: manage_tasks.php?step_id=" . $step_id_redirect . "&error=Failed to delete task.");
        exit;
    }
} catch (PDOException $e) {
    // Log the error in a real application: error_log("Error deleting task: " . $e->getMessage());
    header("Location: manage_tasks.php?step_id=" . $step_id_redirect . "&error=An error occurred during deletion.");
    exit;
}

?>

