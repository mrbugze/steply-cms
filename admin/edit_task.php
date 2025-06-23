<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Edit Task";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages

$message = ""; // Local message for form errors
$error = false;
$task = null;
$step_title = "";
$course_title = "";

// Check if task ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid task ID.";
    $_SESSION['error'] = true;
    header("Location: manage_courses.php");
    exit;
}

$task_id = intval($_GET['id']);

// Fetch task data along with step and course context
$stmt = $conn->prepare("SELECT t.task_id, t.title, t.task_type, t.content, t.step_id, s.title AS step_title, c.title AS course_title 
                        FROM tasks t 
                        JOIN steps s ON t.step_id = s.step_id 
                        JOIN courses c ON s.course_id = c.course_id 
                        WHERE t.task_id = :task_id");
$stmt->bindParam(':task_id', $task_id, PDO::PARAM_INT);
$stmt->execute();
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    $_SESSION['message'] = "Task not found.";
    $_SESSION['error'] = true;
    header("Location: manage_courses.php");
    exit;
}
$step_id = $task['step_id']; // Get step ID for redirects and context
$step_title = $task['step_title'];
$course_title = $task['course_title'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $task_type = $_POST['task_type'] ?? 'quiz';
    $content = trim($_POST['content'] ?? '');

    if (empty($title) || empty($content)) {
        $message = "Title and Content/Details are required.";
        $error = true;
    } elseif (!in_array($task_type, ['quiz', 'assignment', 'info'])) {
        $message = "Invalid task type selected.";
        $error = true;
    } else {
        // Update task
        $stmtUpdate = $conn->prepare("UPDATE tasks SET title = :title, task_type = :task_type, content = :content WHERE task_id = :task_id");
        $stmtUpdate->bindParam(':title', $title);
        $stmtUpdate->bindParam(':task_type', $task_type);
        $stmtUpdate->bindParam(':content', $content);
        $stmtUpdate->bindParam(':task_id', $task_id, PDO::PARAM_INT);

        if ($stmtUpdate->execute()) {
            $_SESSION['message'] = "Task updated successfully.";
            $_SESSION['error'] = false;
            header("Location: manage_tasks.php?step_id=" . $step_id);
            exit;
        } else {
            $message = "Failed to update task. Please try again.";
            $error = true;
        }
    }
    // If there was an error, repopulate task data with submitted values
    $task['title'] = $title;
    $task['task_type'] = $task_type;
    $task['content'] = $content;
}

?>

<div class="form-wrapper">
    <h2 class="text-center mb-4">Edit Task (ID: <?php echo htmlspecialchars($task['task_id']); ?>) for Step: <span class="text-primary">'<?php echo htmlspecialchars($step_title); ?>'</span></h2>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $error ? 'alert-danger' : 'alert-info'; ?>" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="edit_task.php?id=<?php echo $task['task_id']; ?>" method="post">
        <div class="form-group mb-3">
            <label for="title" class="form-label">Task Title:</label>
            <input type="text" id="title" name="title" class="form-control" required value="<?php echo htmlspecialchars($task['title']); ?>">
        </div>
        <div class="form-group mb-3">
            <label for="task_type" class="form-label">Task Type:</label>
            <select id="task_type" name="task_type" class="form-control">
                <option value="quiz" <?php echo ($task['task_type'] == 'quiz') ? 'selected' : ''; ?>>Quiz</option>
                <option value="assignment" <?php echo ($task['task_type'] == 'assignment') ? 'selected' : ''; ?>>Assignment</option>
                <option value="info" <?php echo ($task['task_type'] == 'info') ? 'selected' : ''; ?>>Info/Reading</option>
            </select>
        </div>
        <div class="form-group mb-3">
            <label for="content" class="form-label">Content/Details:</label>
            <textarea id="content" name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($task['content']); ?></textarea>
            <div class="form-text">For Quizzes, consider JSON. For Assignments, instructions. For Info, reading material.</div>
        </div>
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="manage_tasks.php?step_id=<?php echo $step_id; ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Task</button>
        </div>
    </form>
</div>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

