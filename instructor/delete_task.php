<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor'); // Ensure user is an instructor

$instructor_id = $_SESSION['user_id'];

// Check if task ID is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_courses.php?error=Invalid task ID.");
    exit;
}

$task_id_to_delete = intval($_GET['id']);
$step_id_redirect = isset($_GET['step_id']) ? intval($_GET['step_id']) : null;

// Check if the task exists and belongs to a step in a course owned by the instructor
$stmtCheck = $conn->prepare("SELECT t.task_id, s.step_id, s.course_id 
                        FROM tasks t 
                        JOIN steps s ON t.step_id = s.step_id 
                        JOIN courses c ON s.course_id = c.course_id 
                        WHERE t.task_id = :task_id AND c.instructor_id = :instructor_id");
$stmtCheck->bindParam(':task_id', $task_id_to_delete, PDO::PARAM_INT);
$stmtCheck->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmtCheck->execute();
$taskData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$taskData) {
    // If step ID was passed for redirect, use it, otherwise go to general courses page
    $redirect_url = $step_id_redirect ? "manage_tasks.php?step_id=" . $step_id_redirect : "manage_courses.php";
    header("Location: " . $redirect_url . "?error=Task not found or permission denied.");
    exit;
}

// Use the actual step ID from the task for accurate redirect if not provided
if ($step_id_redirect === null) {
    $step_id_redirect = $taskData['step_id'];
}

// Proceed with deletion
try {
    $conn->beginTransaction();

    // Delete the task itself
    $stmtDelete = $conn->prepare("DELETE FROM tasks WHERE task_id = :task_id");
    // Ownership was already verified
    $stmtDelete->bindParam(':task_id', $task_id_to_delete, PDO::PARAM_INT);

    if ($stmtDelete->execute()) {
        $conn->commit();
        header("Location: manage_tasks.php?step_id=" . $step_id_redirect . "&message=Task deleted successfully.");
        exit;
    } else {
        $conn->rollBack();
        header("Location: manage_tasks.php?step_id=" . $step_id_redirect . "&error=Failed to delete task.");
        exit;
    }
} catch (PDOException $e) {
    $conn->rollBack();
    // Log the error in a real application: error_log("Error deleting task: " . $e->getMessage());
    header("Location: manage_tasks.php?step_id=" . $step_id_redirect . "&error=An error occurred during deletion.");
    exit;
}

?>

