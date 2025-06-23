<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

// Check if step ID is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_courses.php?error=Invalid step ID.");
    exit;
}

$step_id_to_delete = intval($_GET['id']);
$course_id_redirect = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;

// Check if the step exists before attempting deletion
$stmtCheck = $conn->prepare("SELECT step_id, course_id FROM steps WHERE step_id = :step_id");
$stmtCheck->bindParam(':step_id', $step_id_to_delete, PDO::PARAM_INT);
$stmtCheck->execute();
$stepData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if (!$stepData) {
    // If course ID was passed for redirect, use it, otherwise go to general courses page
    $redirect_url = $course_id_redirect ? "manage_steps.php?course_id=" . $course_id_redirect : "manage_courses.php";
    header("Location: " . $redirect_url . "?error=Step not found.");
    exit;
}

// Use the actual course ID from the step for accurate redirect if not provided
if ($course_id_redirect === null) {
    $course_id_redirect = $stepData['course_id'];
}

// Proceed with deletion (including related tasks)
try {
    $conn->beginTransaction();

    // 1. Delete related tasks first
    $stmtTasks = $conn->prepare("DELETE FROM tasks WHERE step_id = :step_id");
    $stmtTasks->bindParam(':step_id', $step_id_to_delete, PDO::PARAM_INT);
    $stmtTasks->execute();

    // 2. Delete the step itself
    $stmtDelete = $conn->prepare("DELETE FROM steps WHERE step_id = :step_id");
    $stmtDelete->bindParam(':step_id', $step_id_to_delete, PDO::PARAM_INT);

    if ($stmtDelete->execute()) {
        $conn->commit();
        header("Location: manage_steps.php?course_id=" . $course_id_redirect . "&message=Step and related tasks deleted successfully.");
        exit;
    } else {
        $conn->rollBack();
        header("Location: manage_steps.php?course_id=" . $course_id_redirect . "&error=Failed to delete step.");
        exit;
    }
} catch (PDOException $e) {
    $conn->rollBack();
    // Log the error in a real application: error_log("Error deleting step: " . $e->getMessage());
    header("Location: manage_steps.php?course_id=" . $course_id_redirect . "&error=An error occurred during deletion.");
    exit;
}

?>

