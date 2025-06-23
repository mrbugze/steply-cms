<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor'); // Ensure user is an instructor

$pageTitle = "Edit Step";
include __DIR__ . 
    '/../templates/partials/instructor_header.php';

$message = "";
$error = false;
$step = null;
$course_title = "";
$instructor_id = $_SESSION['user_id'];

// Check if step ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_courses.php?error=Invalid step ID.");
    exit;
}

$step_id = intval($_GET['id']);

// Fetch step data along with course title, ensuring the course belongs to the instructor
$stmt = $conn->prepare("SELECT s.step_id, s.title, s.content, s.step_order, s.course_id, c.title AS course_title 
                        FROM steps s 
                        JOIN courses c ON s.course_id = c.course_id 
                        WHERE s.step_id = :step_id AND c.instructor_id = :instructor_id");
$stmt->bindParam(':step_id', $step_id, PDO::PARAM_INT);
$stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmt->execute();
$step = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$step) {
    header("Location: manage_courses.php?error=Step not found or you do not have permission to edit it.");
    exit;
}
$course_id = $step['course_id']; // Get course ID for redirects and context
$course_title = $step['course_title'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $step_order = isset($_POST['step_order']) && is_numeric($_POST['step_order']) ? intval($_POST['step_order']) : 0;

    if (empty($title) || empty($content)) {
        $message = "Title and Content are required.";
        $error = true;
    } else {
        // Update step
        $stmtUpdate = $conn->prepare("UPDATE steps SET title = :title, content = :content, step_order = :step_order WHERE step_id = :step_id");
        // We don't need to check instructor_id again here, as we verified ownership when fetching the step
        $stmtUpdate->bindParam(':title', $title);
        $stmtUpdate->bindParam(':content', $content);
        $stmtUpdate->bindParam(':step_order', $step_order, PDO::PARAM_INT);
        $stmtUpdate->bindParam(':step_id', $step_id, PDO::PARAM_INT);

        if ($stmtUpdate->execute()) {
            header("Location: manage_steps.php?course_id=" . $course_id . "&message=Step updated successfully.");
            exit;
        } else {
            $message = "Failed to update step. Please try again.";
            $error = true;
        }
    }
    // If there was an error, repopulate step data with submitted values
    $step['title'] = $title;
    $step['content'] = $content;
    $step['step_order'] = $step_order;
}

?>

<h2>Edit Step (ID: <?php echo htmlspecialchars($step['step_id']); ?>) for Course: <?php echo htmlspecialchars($course_title); ?></h2>

<?php if (!empty($message)): ?>
    <div class="message <?php echo $error ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<form action="edit_step.php?id=<?php echo $step['step_id']; ?>" method="post" class="form-container">
    <div class="form-group">
        <label for="title">Step Title:</label>
        <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($step['title']); ?>">
    </div>
    <div class="form-group">
        <label for="content">Content:</label>
        <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($step['content']); ?></textarea>
        <!-- Consider using a Rich Text Editor here -->
    </div>
    <div class="form-group">
        <label for="step_order">Step Order:</label>
        <input type="number" id="step_order" name="step_order" value="<?php echo htmlspecialchars($step['step_order']); ?>" required>
        <small>Steps are displayed in ascending order.</small>
    </div>
    <button type="submit" class="button">Update Step</button>
    <a href="manage_steps.php?course_id=<?php echo $course_id; ?>" class="button-secondary">Cancel</a>
</form>

<?php
include __DIR__ . 
    '/../templates/partials/instructor_footer.php';
?>

