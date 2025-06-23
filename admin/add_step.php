<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Add Step";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages

$message = ""; // Local message for form errors
$error = false;
$course_id = null;
$course_title = "";

// Check if course ID is provided
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    $_SESSION['message'] = "Course ID is required to add a step.";
    $_SESSION['error'] = true;
    header("Location: manage_courses.php");
    exit;
}

$course_id = intval($_GET['course_id']);

// Fetch course title for context
$stmtCourse = $conn->prepare("SELECT title FROM courses WHERE course_id = :course_id");
$stmtCourse->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmtCourse->execute();
$courseData = $stmtCourse->fetch(PDO::FETCH_ASSOC);
if (!$courseData) {
    $_SESSION['message'] = "Invalid course ID specified.";
    $_SESSION['error'] = true;
    header("Location: manage_courses.php");
    exit;
}
$course_title = $courseData['title'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $video_path = trim($_POST['video_path'] ?? ''); // Added video path
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
        $stmt = $conn->prepare("INSERT INTO steps (course_id, title, content, video_path, step_order) VALUES (:course_id, :title, :content, :video_path, :step_order)"); // Added video_path
        $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':video_path', $video_path);
        $stmt->bindParam(':step_order', $step_order, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Step added successfully.";
            $_SESSION['error'] = false;
            header("Location: manage_steps.php?course_id=" . $course_id);
            exit;
        } else {
            error_log("Error adding step: " . implode(", ", $stmt->errorInfo()));
            $message = "Failed to add step. Please try again.";
            $error = true;
        }
    }
}

?>

<div class="form-wrapper">
    <h2 class="text-center mb-4">Add New Step to Course: <span class="text-primary"><?php echo htmlspecialchars($course_title); ?></span> (ID: <?php echo $course_id; ?>)</h2>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $error ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="add_step.php?course_id=<?php echo $course_id; ?>" method="post">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
        
        <div class="form-group mb-3">
            <label for="title" class="form-label">Step Title:</label>
            <input type="text" id="title" name="title" class="form-control" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
        </div>
        
        <div class="form-group mb-3">
            <label for="content" class="form-label">Content:</label>
            <textarea id="content" name="content" class="form-control" rows="10" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
            <div class="form-text">Basic HTML is allowed. Consider using a Rich Text Editor for better experience.</div>
        </div>

        <div class="form-group mb-3">
            <label for="video_path" class="form-label">Video Path (Optional):</label>
            <input type="text" id="video_path" name="video_path" class="form-control" value="<?php echo isset($_POST['video_path']) ? htmlspecialchars($_POST['video_path']) : ''; ?>" placeholder="/videos/course/step1.mp4">
            <div class="form-text">Relative path starting from /cms. E.g., /videos/lesson1.mp4</div>
        </div>
        
        <div class="form-group mb-3">
            <label for="step_order" class="form-label">Step Order:</label>
            <input type="number" id="step_order" name="step_order" class="form-control" value="<?php echo isset($_POST['step_order']) ? htmlspecialchars($_POST['step_order']) : '0'; ?>" required>
            <div class="form-text">Steps are displayed in ascending order (0, 1, 2...).</div>
        </div>
        
        <div class="d-flex justify-content-end mt-4">
            <a href="manage_steps.php?course_id=<?php echo $course_id; ?>" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Add Step</button>
        </div>
    </form>
</div>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

