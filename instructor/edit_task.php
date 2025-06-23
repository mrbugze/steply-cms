<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor'); // Ensure user is an instructor

$pageTitle = "Edit Task";
include __DIR__ . 
    '/../templates/partials/instructor_header.php';

$message = "";
$error = false;
$task = null;
$step_title = "";
$course_title = "";
$course_id = null;
$instructor_id = $_SESSION['user_id'];

// Check if task ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_courses.php?error=Invalid task ID.");
    exit;
}

$task_id = intval($_GET['id']);

// Fetch task data along with step/course titles, ensuring ownership via instructor
$stmt = $conn->prepare("SELECT t.task_id, t.title, t.description, t.task_type, t.step_id, 
                               s.title AS step_title, s.course_id, c.title AS course_title 
                        FROM tasks t 
                        JOIN steps s ON t.step_id = s.step_id 
                        JOIN courses c ON s.course_id = c.course_id 
                        WHERE t.task_id = :task_id AND c.instructor_id = :instructor_id");
$stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
$stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmt->execute();
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    header("Location: manage_courses.php?error=Task not found or you do not have permission to edit it.");
    exit;
}
$step_id = $task['step_id']; // Get step ID for redirects and context
$step_title = $task['step_title'];
$course_id = $task['course_id'];
$course_title = $task['course_title'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $task_type = trim($_POST['task_type'] ?? 'assignment');

    if (empty($title)) {
        $message = "Task Title is required.";
        $error = true;
    } elseif (!in_array($task_type, ['assignment', 'quiz', 'test'])) { // Validate task type
        $message = "Invalid task type selected.";
        $error = true;
    } else {
        // Update task
        $stmtUpdate = $conn->prepare("UPDATE tasks SET title = :title, description = :description, task_type = :task_type WHERE task_id = :task_id");
        // Ownership was already verified when fetching the task
        $stmtUpdate->bindParam(':title', $title);
        $stmtUpdate->bindParam(':description', $description);
        $stmtUpdate->bindParam(':task_type', $task_type);
        $stmtUpdate->bindParam(':task_id', $task_id, PDO::PARAM_INT);

        if ($stmtUpdate->execute()) {
            header("Location: manage_tasks.php?step_id=" . $step_id . "&message=Task updated successfully.");
            exit;
        } else {
            $message = "Failed to update task. Please try again.";
            $error = true;
        }
    }
    // If there was an error, repopulate task data with submitted values
    $task['title'] = $title;
    $task['description'] = $description;
    $task['task_type'] = $task_type;
}

?>

<h2>Edit Task (ID: <?php echo htmlspecialchars($task['task_id']); ?>) for Step: <?php echo htmlspecialchars($step_title); ?></h2>

<?php if (!empty($message)): ?>
    <div class="message <?php echo $error ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<form action="edit_task.php?id=<?php echo $task['task_id']; ?>" method="post" class="form-container">
    <div class="form-group">
        <label for="title">Task Title:</label>
        <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($task['title']); ?>">
    </div>
    <div class="form-group">
        <label for="description">Description/Instructions:</label>
        <textarea id="description" name="description" rows="5"><?php echo htmlspecialchars($task['description']); ?></textarea>
    </div>
    <div class="form-group">
        <label for="task_type">Task Type:</label>
        <select id="task_type" name="task_type" required>
            <option value="assignment" <?php echo ($task['task_type'] == 'assignment') ? 'selected' : ''; ?>>Assignment</option>
            <option value="quiz" <?php echo ($task['task_type'] == 'quiz') ? 'selected' : ''; ?>>Quiz</option>
            <option value="test" <?php echo ($task['task_type'] == 'test') ? 'selected' : ''; ?>>Test</option>
        </select>
    </div>
    <!-- Add fields for quiz questions/options if type is quiz/test -->
    <button type="submit" class="button">Update Task</button>
    <a href="manage_tasks.php?step_id=<?php echo $step_id; ?>" class="button-secondary">Cancel</a>
</form>

<?php
include __DIR__ . 
    '/../templates/partials/instructor_footer.php';
?>

