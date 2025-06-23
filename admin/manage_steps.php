<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Manage Steps";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages

$course_id = null;
$course_title = "All Courses";
$steps = [];

// Check if a specific course ID is provided to filter steps
if (isset($_GET['course_id']) && is_numeric($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);

    // Fetch course title for context
    $stmtCourse = $conn->prepare("SELECT title FROM courses WHERE course_id = :course_id");
    $stmtCourse->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmtCourse->execute();
    $courseData = $stmtCourse->fetch(PDO::FETCH_ASSOC);
    if ($courseData) {
        $course_title = $courseData['title'];
    } else {
        // If course ID is invalid, redirect
        $_SESSION['message'] = "Invalid course ID specified for steps.";
        $_SESSION['error'] = true;
        header("Location: manage_courses.php");
        exit;
    }

    // Fetch steps for the specific course
    $stmt = $conn->prepare("SELECT s.step_id, s.title, s.content, s.step_order, s.course_id, c.title AS course_title 
                            FROM steps s
                            JOIN courses c ON s.course_id = c.course_id
                            WHERE s.course_id = :course_id 
                            ORDER BY s.step_order ASC");
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch all steps if no specific course is selected
    $stmt = $conn->prepare("SELECT s.step_id, s.title, s.content, s.step_order, s.course_id, c.title AS course_title 
                            FROM steps s
                            JOIN courses c ON s.course_id = c.course_id
                            ORDER BY c.title ASC, s.step_order ASC");
    $stmt->execute();
    $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Steps <?php echo $course_id ? 'for Course: <span class="text-primary">' . htmlspecialchars($course_title) . '</span> (ID: ' . $course_id . ')' : ''; ?></h2>
        <div>
            <?php if ($course_id): // Only show Add button if a course is selected ?>
                <a href="add_step.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">Add New Step</a>
            <?php endif; ?>
            <a href="manage_courses.php" class="btn btn-outline-secondary">Back to Courses</a>
        </div>
    </div>

    <?php // Messages are handled by header.php ?>

    <div class="table-responsive shadow-sm rounded">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <?php if (!$course_id) echo '<th>Course</th>'; // Show course column only if viewing all steps ?>
                    <th>Order</th>
                    <th>Title</th>
                    <th>Content Snippet</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($steps)): ?>
                    <tr>
                        <td colspan="<?php echo $course_id ? 5 : 6; ?>" class="text-center text-muted py-4">No steps found<?php echo $course_id ? ' for this course' : ''; ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($steps as $step): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($step['step_id']); ?></td>
                            <?php if (!$course_id) echo '<td>' . htmlspecialchars($step['course_title']) . ' <span class="text-muted">(ID: ' . $step['course_id'] . ')</span></td>'; ?>
                            <td><?php echo htmlspecialchars($step['step_order']); ?></td>
                            <td><?php echo htmlspecialchars($step['title']); ?></td>
                            <td><?php echo htmlspecialchars(substr(strip_tags($step['content']), 0, 80)) . (strlen(strip_tags($step['content'])) > 80 ? '...' : ''); ?></td>
                            <td class="text-end actions">
                                <a href="edit_step.php?id=<?php echo $step['step_id']; ?>" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                                <a href="delete_step.php?id=<?php echo $step['step_id']; ?>&course_id=<?php echo $step['course_id']; // Pass course_id for redirect ?>" class="btn btn-sm btn-outline-danger me-1" onclick="return confirm('Are you sure you want to delete this step and its tasks?');">Delete</a>
                                <a href="manage_tasks.php?step_id=<?php echo $step['step_id']; ?>" class="btn btn-sm btn-outline-info">Tasks</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

