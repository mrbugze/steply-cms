<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Edit Step";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages

$message = ""; // Local message for form errors
$error = false;
$step = null;
$course_title = "";

// Check if step ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid step ID.";
    $_SESSION['error'] = true;
    header("Location: manage_courses.php");
    exit;
}

$step_id = intval($_GET['id']);

// Fetch step data along with course title and video path
$stmt = $conn->prepare("SELECT s.step_id, s.title, s.content, s.video_path, s.step_order, s.course_id, c.title AS course_title 
                        FROM steps s 
                        JOIN courses c ON s.course_id = c.course_id 
                        WHERE s.step_id = :step_id"); // Added video_path
$stmt->bindParam(':step_id', $step_id, PDO::PARAM_INT);
$stmt->execute();
$step = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$step) {
    $_SESSION['message'] = "Step not found.";
    $_SESSION['error'] = true;
    header("Location: manage_courses.php");
    exit;
}
$course_id = $step['course_id']; // Get course ID for redirects and context
$course_title = $step['course_title'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $video_path = trim($_POST['video_path'] ?? ''); // Added video path
    $step_order = isset($_POST['step_order']) && is_numeric($_POST['step_order']) ? intval($_POST['step_order']) : 0;

    if (empty($title) || empty($content)) {
        $message = "Title and Content are required.";
        $error = true;
    } else {
        // Update step
        $stmtUpdate = $conn->prepare("UPDATE steps SET title = :title, content = :content, video_path = :video_path, step_order = :step_order WHERE step_id = :step_id"); // Added video_path
        $stmtUpdate->bindParam(':title', $title);
        $stmtUpdate->bindParam(':content', $content);
        $stmtUpdate->bindParam(':video_path', $video_path);
        $stmtUpdate->bindParam(':step_order', $step_order, PDO::PARAM_INT);
        $stmtUpdate->bindParam(':step_id', $step_id, PDO::PARAM_INT);

        if ($stmtUpdate->execute()) {
            $_SESSION['message'] = "Step updated successfully.";
            $_SESSION['error'] = false;
            header("Location: manage_steps.php?course_id=" . $course_id);
            exit;
        } else {
            $message = "Failed to update step. Please try again.";
            $error = true;
        }
    }
    // If there was an error, repopulate step data with submitted values
    $step['title'] = $title;
    $step['content'] = $content;
    $step['video_path'] = $video_path;
    $step['step_order'] = $step_order;
}

?>

<div class="form-wrapper">
    <h2 class="text-center mb-4">Edit Step (ID: <?php echo htmlspecialchars($step['step_id']); ?>) for Course: <span class="text-primary"><?php echo htmlspecialchars($course_title); ?></span></h2>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $error ? 'alert-danger' : 'alert-info'; ?>" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="edit_step.php?id=<?php echo $step['step_id']; ?>" method="post">
        <div class="form-group mb-3">
            <label for="title" class="form-label">Step Title:</label>
            <input type="text" id="title" name="title" class="form-control" required value="<?php echo htmlspecialchars($step['title']); ?>">
        </div>
        <div class="form-group mb-3">
            <label for="content" class="form-label">Content:</label>
            <textarea id="content" name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($step['content']); ?></textarea>
            <div class="form-text">Basic HTML is allowed.</div>
        </div>
        <div class="form-group mb-3">
            <label for="video_path" class="form-label">Video Path (Optional):</label>
            <input type="text" id="video_path" name="video_path" class="form-control" value="<?php echo htmlspecialchars($step['video_path'] ?? ''); ?>" placeholder="/videos/course/step1.mp4">
            <div class="form-text">Relative path starting from /cms/public. E.g., /videos/lesson1.mp4</div>
        </div>
        <div class="form-group mb-3">
            <label for="step_order" class="form-label">Step Order:</label>
            <input type="number" id="step_order" name="step_order" class="form-control" value="<?php echo htmlspecialchars($step['step_order']); ?>" required>
            <div class="form-text">Steps are displayed in ascending order (0, 1, 2...).</div>
        </div>
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="manage_steps.php?course_id=<?php echo $course_id; ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Step</button>
        </div>
    </form>
</div>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

