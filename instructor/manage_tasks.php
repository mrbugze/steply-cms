<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor'); // Ensure user is an instructor

$pageTitle = "Manage Tasks";
include __DIR__ . 
    '/../templates/partials/instructor_header.php';

$instructor_id = $_SESSION['user_id'];
$step_id = null;
$step_title = "";
$course_title = "";
$course_id = null;
$tasks = [];

// Check if a specific step ID is provided
if (isset($_GET['step_id']) && is_numeric($_GET['step_id'])) {
    $step_id = intval($_GET['step_id']);

    // Verify step ownership via course and fetch step/course titles
    $stmtStep = $conn->prepare("SELECT s.title AS step_title, s.course_id, c.title AS course_title 
                             FROM steps s 
                             JOIN courses c ON s.course_id = c.course_id 
                             WHERE s.step_id = :step_id AND c.instructor_id = :instructor_id");
    $stmtStep->bindParam(':step_id', $step_id, PDO::PARAM_INT);
    $stmtStep->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
    $stmtStep->execute();
    $stepData = $stmtStep->fetch(PDO::FETCH_ASSOC);

    if ($stepData) {
        $step_title = $stepData['step_title'];
        $course_id = $stepData['course_id'];
        $course_title = $stepData['course_title'];

        // Fetch tasks for the specific step
        $stmt = $conn->prepare("SELECT task_id, title, description, task_type 
                                FROM tasks
                                WHERE step_id = :step_id 
                                ORDER BY task_id ASC"); // Or some other logical order
        $stmt->bindParam(':step_id', $step_id, PDO::PARAM_INT);
        $stmt->execute();
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // If step ID is invalid or doesn't belong to instructor's course, redirect
        header("Location: manage_courses.php?error=Invalid step selected or permission denied.");
        exit;
    }
} else {
    // If no step ID is provided, redirect back to course management
    header("Location: manage_courses.php?error=Please select a step to manage its tasks.");
    exit;
}

?>

<h2>Manage Tasks for Step: <?php echo htmlspecialchars($step_title); ?> (Course: <?php echo htmlspecialchars($course_title); ?>)</h2>

<?php if (isset($_GET['message'])): ?>
    <div class="message success"><?php echo htmlspecialchars($_GET['message']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="message error"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>

<a href="add_task.php?step_id=<?php echo $step_id; ?>" class="button">Add New Task</a>
<a href="manage_steps.php?course_id=<?php echo $course_id; ?>" class="button-secondary">Back to Steps</a>

<table class="table-styled">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Type</th>
            <th>Description Snippet</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($tasks)): ?>
            <tr>
                <td colspan="5">No tasks found for this step.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['task_id']); ?></td>
                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($task['task_type'])); ?></td>
                    <td><?php echo htmlspecialchars(substr(strip_tags($task['description']), 0, 100)) . (strlen(strip_tags($task['description'])) > 100 ? '...' : ''); ?></td>
                    <td>
                        <a href="edit_task.php?id=<?php echo $task['task_id']; ?>" class="button-small">Edit</a>
                        <a href="delete_task.php?id=<?php echo $task['task_id']; ?>&step_id=<?php echo $step_id; // Pass step_id for redirect ?>" class="button-small delete" onclick="return confirm('Are you sure you want to delete this task?');">Delete</a>
                        <!-- Add link to view/manage submissions if applicable -->
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
include __DIR__ . 
    '/../templates/partials/instructor_footer.php';
?>

