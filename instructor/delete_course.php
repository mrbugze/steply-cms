<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor'); // Ensure user is an instructor

$instructor_id = $_SESSION['user_id'];

// Check if course ID is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_courses.php?error=Invalid course ID.");
    exit;
}

$course_id_to_delete = intval($_GET['id']);

// Check if the course exists and belongs to the instructor before attempting deletion
$stmtCheck = $conn->prepare("SELECT course_id FROM courses WHERE course_id = :course_id AND instructor_id = :instructor_id");
$stmtCheck->bindParam(':course_id', $course_id_to_delete, PDO::PARAM_INT);
$stmtCheck->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmtCheck->execute();
if (!$stmtCheck->fetch()) {
    header("Location: manage_courses.php?error=Course not found or you do not have permission to delete it.");
    exit;
}

// Proceed with deletion (including related steps and tasks)
try {
    $conn->beginTransaction();

    // 1. Delete related tasks first (tasks belong to steps)
    $stmtTasks = $conn->prepare("DELETE tasks FROM tasks 
                                JOIN steps ON tasks.step_id = steps.step_id 
                                WHERE steps.course_id = :course_id");
    $stmtTasks->bindParam(':course_id', $course_id_to_delete, PDO::PARAM_INT);
    $stmtTasks->execute();

    // 2. Delete related steps
    $stmtSteps = $conn->prepare("DELETE FROM steps WHERE course_id = :course_id");
    $stmtSteps->bindParam(':course_id', $course_id_to_delete, PDO::PARAM_INT);
    $stmtSteps->execute();

    // 3. Delete related enrollments
    $stmtEnrollments = $conn->prepare("DELETE FROM enrollments WHERE course_id = :course_id");
    $stmtEnrollments->bindParam(':course_id', $course_id_to_delete, PDO::PARAM_INT);
    $stmtEnrollments->execute();

    // 4. Delete the course itself
    $stmtDelete = $conn->prepare("DELETE FROM courses WHERE course_id = :course_id AND instructor_id = :instructor_id");
    $stmtDelete->bindParam(':course_id', $course_id_to_delete, PDO::PARAM_INT);
    $stmtDelete->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);

    if ($stmtDelete->execute()) {
        $conn->commit();
        header("Location: manage_courses.php?message=Course and all related data deleted successfully.");
        exit;
    } else {
        $conn->rollBack();
        header("Location: manage_courses.php?error=Failed to delete course.");
        exit;
    }
} catch (PDOException $e) {
    $conn->rollBack();
    // Log the error in a real application: error_log("Error deleting course: " . $e->getMessage());
    header("Location: manage_courses.php?error=An error occurred during deletion. Check database constraints.");
    exit;
}

?>

