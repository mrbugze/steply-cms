<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor'); // Ensure user is an instructor

$pageTitle = "Add Step";
include __DIR__ . 
    '/../templates/partials/instructor_header.php';

$message = "";
$error = false;
$course_id = null;
$course_title = "";
$instructor_id = $_SESSION['user_id'];

// Check if course ID is provided
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header("Location: manage_courses.php?error=Course ID is required to add a step.");
    exit;
}

$course_id = intval($_GET['course_id']);

// Verify course ownership and fetch title
$stmtCourse = $conn->prepare("SELECT title FROM courses WHERE course_id = :course_id AND instructor_id = :instructor_id");
$stmtCourse->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmtCourse->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmtCourse->execute();
$courseData = $stmtCourse->fetch(PDO::FETCH_ASSOC);
if (!$courseData) {
    header("Location: manage_courses.php?error=Invalid course ID or permission denied.");
    exit;
}
$course_title = $courseData['title'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $step_order = isset($_POST['step_order']) && is_numeric($_POST['step_order']) ? intval($_POST['step_order']) : 0;
    $posted_course_id = intval($_POST['course_id'] ?? 0);

    if ($posted_course_id !== $course_id) {
        $message = "Course ID mismatch.";
        $error = true;
    } elseif (empty($title) || empty($content)) {
        $message = "Title and Content are required.";
        $error = true;
    } else {
        // Insert step
        $stmt = $conn->prepare("INSERT INTO steps (course_id, title, content, step_order) VALUES (:course_id, :title, :content, :step_order)");
        $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':step_order', $step_order, PDO::PARAM_INT);

        if ($stmt->execute()) {
            header("Location: manage_steps.php?course_id=" . $course_id . "&message=Step added successfully.");
            exit;
        } else {
            $message = "Failed to add step. Please try again.";
            $error = true;
        }
    }
}

?>

<h2>Add New Step to Course: <?php echo htmlspecialchars($course_title); ?> (ID: <?php echo $course_id; ?>)</h2>

<?php if (!empty($message)): ?>
    <div class="message <?php echo $error ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<form action="add_step.php?course_id=<?php echo $course_id; ?>" method="post" class="form-container">
    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
    <div class="form-group">
        <label for="title">Step Title:</label>
        <input type="text" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
    </div>
    <div class="form-group">
        <label for="content">Content:</label>
        <textarea id="content" name="content" rows="10" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
        <!-- Consider using a Rich Text Editor here in a real application -->
    </div>
    <div class="form-group">
        <label for="step_order">Step Order:</label>
        <input type="number" id="step_order" name="step_order" value="<?php echo isset($_POST['step_order']) ? htmlspecialchars($_POST['step_order']) : '0'; ?>" required>
        <small>Steps are displayed in ascending order.</small>
    </div>
    <button type="submit" class="button">Add Step</button>
    <a href="manage_steps.php?course_id=<?php echo $course_id; ?>" class="button-secondary">Cancel</a>
</form>

<?php
include __DIR__ . 
    '/../templates/partials/instructor_footer.php';
?>

