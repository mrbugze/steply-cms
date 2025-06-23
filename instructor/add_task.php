<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor'); // Ensure user is an instructor

$pageTitle = "Add Task";
include __DIR__ . 
    '/../templates/partials/instructor_header.php';

$message = "";
$error = false;
$step_id = null;
$step_title = "";
$course_title = "";
$course_id = null;
$instructor_id = $_SESSION['user_id'];

// Check if step ID is provided
if (!isset($_GET['step_id']) || !is_numeric($_GET['step_id'])) {
    header("Location: manage_courses.php?error=Step ID is required to add a task.");
    exit;
}

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
if (!$stepData) {
    header("Location: manage_courses.php?error=Invalid step ID or permission denied.");
    exit;
}
$step_title = $stepData['step_title'];
$course_id = $stepData['course_id'];
$course_title = $stepData['course_title'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $task_type = trim($_POST['task_type'] ?? 'assignment'); // Default to 'assignment'
    $posted_step_id = intval($_POST['step_id'] ?? 0);

    if ($posted_step_id !== $step_id) {
        $message = "Step ID mismatch.";
        $error = true;
    } elseif (empty($title)) {
        $message = "Task Title is required.";
        $error = true;
    } elseif (!in_array($task_type, ['assignment', 'quiz', 'test'])) { // Validate task type
        $message = "Invalid task type selected.";
        $error = true;
    } else {
        // Insert task
        $stmt = $conn->prepare("INSERT INTO tasks (step_id, title, description, task_type) VALUES (:step_id, :title, :description, :task_type)");
        $stmt->bindParam(':step_id', $step_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':task_type', $task_type);

        if ($stmt->execute()) {
            header("Location: manage_tasks.php?step_id=" . $step_id . "&message=Task added successfully.");
            exit;
        } else {
            $message = "Failed to add task. Please try again.";
            $error = true;
        }
    }
}

?>

<h2>Add New Task to Step: <?php echo htmlspecialchars($step_title); ?> (Course: <?php echo htmlspecialchars($course_title); ?>)</h2>

<?php if (!empty($message)): ?>
    <div class="message <?php echo $error ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<form action="add_task.php?step_id=<?php echo $step_id; ?>" method="post" class="form-container">
    <input type="hidden" name="step_id" value="<?php echo $step_id; ?>">
    <div class="form-group">
        <label for="title">Task Title:</label>
        <input type="text" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
    </div>
    <div class="form-group">
        <label for="description">Description/Instructions:</label>
        <textarea id="description" name="description" rows="5"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
    </div>
    <div class="form-group">
        <label for="task_type">Task Type:</label>
        <select id="task_type" name="task_type" required>
            <option value="assignment" <?php echo (isset($_POST['task_type']) && $_POST['task_type'] == 'assignment') ? 'selected' : ''; ?>>Assignment</option>
            <option value="quiz" <?php echo (isset($_POST['task_type']) && $_POST['task_type'] == 'quiz') ? 'selected' : ''; ?>>Quiz</option>
            <option value="test" <?php echo (isset($_POST['task_type']) && $_POST['task_type'] == 'test') ? 'selected' : ''; ?>>Test</option>
        </select>
    </div>
    <!-- Add fields for quiz questions/options if type is quiz/test - requires more complex logic -->
    <button type="submit" class="button">Add Task</button>
    <a href="manage_tasks.php?step_id=<?php echo $step_id; ?>" class="button-secondary">Cancel</a>
</form>

<?php
include __DIR__ . 
    '/../templates/partials/instructor_footer.php';
?>

