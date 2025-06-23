<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Add Task";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages

$message = ""; // Local message for form errors
$error = false;
$step_id = null;
$step_title = "";
$course_title = "";

// Check if step ID is provided
if (!isset($_GET['step_id']) || !is_numeric($_GET['step_id'])) {
    $_SESSION['message'] = "Step ID is required to add a task.";
    $_SESSION['error'] = true;
    header("Location: manage_courses.php");
    exit;
}

$step_id = intval($_GET['step_id']);

// Fetch step and course title for context
$stmtStep = $conn->prepare("SELECT s.title AS step_title, c.title AS course_title, s.course_id 
                         FROM steps s 
                         JOIN courses c ON s.course_id = c.course_id 
                         WHERE s.step_id = :step_id");
$stmtStep->bindParam(':step_id', $step_id, PDO::PARAM_INT);
$stmtStep->execute();
$stepData = $stmtStep->fetch(PDO::FETCH_ASSOC);
if (!$stepData) {
    $_SESSION['message'] = "Invalid step ID specified.";
    $_SESSION['error'] = true;
    header("Location: manage_courses.php");
    exit;
}
$step_title = $stepData['step_title'];
$course_title = $stepData['course_title'];
$course_id = $stepData['course_id']; // For redirect back to steps

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $task_type = $_POST['task_type'] ?? 'quiz'; // Default to quiz
    $content = trim($_POST['content'] ?? ''); // Content could be quiz questions (JSON?), assignment details, etc.
    $posted_step_id = intval($_POST['step_id'] ?? 0);

    if ($posted_step_id !== $step_id) {
        $message = "Step ID mismatch.";
        $error = true;
    } elseif (empty($title) || empty($content)) {
        $message = "Title and Content/Details are required.";
        $error = true;
    } elseif (!in_array($task_type, ['quiz', 'assignment', 'info'])) { // Added 'info' type
        $message = "Invalid task type selected.";
        $error = true;
    } else {
        // Insert task
        $stmt = $conn->prepare("INSERT INTO tasks (step_id, title, task_type, content) VALUES (:step_id, :title, :task_type, :content)");
        $stmt->bindParam(':step_id', $step_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':task_type', $task_type);
        $stmt->bindParam(':content', $content);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Task added successfully.";
            $_SESSION['error'] = false;
            header("Location: manage_tasks.php?step_id=" . $step_id);
            exit;
        } else {
            $message = "Failed to add task. Please try again.";
            $error = true;
        }
    }
}

?>

<div class="form-wrapper">
    <h2 class="text-center mb-4">Add New Task to Step: <span class="text-primary">'<?php echo htmlspecialchars($step_title); ?>'</span> (Course: <?php echo htmlspecialchars($course_title); ?>)</h2>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $error ? 'alert-danger' : 'alert-info'; ?>" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="add_task.php?step_id=<?php echo $step_id; ?>" method="post">
        <input type="hidden" name="step_id" value="<?php echo $step_id; ?>">
        
        <div class="form-group mb-3">
            <label for="title" class="form-label">Task Title:</label>
            <input type="text" id="title" name="title" class="form-control" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
        </div>
        
        <div class="form-group mb-3">
            <label for="task_type" class="form-label">Task Type:</label>
            <select id="task_type" name="task_type" class="form-control">
                <option value="quiz" <?php echo (isset($_POST['task_type']) && $_POST['task_type'] == 'quiz') ? 'selected' : ''; ?>>Quiz</option>
                <option value="assignment" <?php echo (isset($_POST['task_type']) && $_POST['task_type'] == 'assignment') ? 'selected' : ''; ?>>Assignment</option>
                <option value="info" <?php echo (isset($_POST['task_type']) && $_POST['task_type'] == 'info') ? 'selected' : ''; ?>>Info/Reading</option>
            </select>
        </div>
        
        <div class="form-group mb-3">
            <label for="content" class="form-label">Content/Details:</label>
            <textarea id="content" name="content" class="form-control" rows="10" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
            <div class="form-text">For Quizzes, consider using JSON format. For Assignments, provide instructions. For Info, provide reading material.</div>
        </div>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="manage_tasks.php?step_id=<?php echo $step_id; ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Add Task</button>
        </div>
    </form>
</div>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

