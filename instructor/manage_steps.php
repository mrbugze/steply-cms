<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor'); // Ensure user is an instructor

$pageTitle = "Manage Steps";
include __DIR__ . 
    '/../templates/partials/header.php';

$instructor_id = $_SESSION['user_id'];
$course_id = null;
$course_title = "";
$steps = [];

// Check if a specific course ID is provided and belongs to the instructor
if (isset($_GET['course_id']) && is_numeric($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);

    // Verify course ownership and fetch title
    $stmtCourse = $conn->prepare("SELECT title FROM courses WHERE course_id = :course_id AND instructor_id = :instructor_id");
    $stmtCourse->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmtCourse->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
    $stmtCourse->execute();
    $courseData = $stmtCourse->fetch(PDO::FETCH_ASSOC);

    if ($courseData) {
        $course_title = $courseData['title'];

        // Fetch steps for the specific course
        $stmt = $conn->prepare("SELECT step_id, title, content, step_order 
                                FROM steps
                                WHERE course_id = :course_id 
                                ORDER BY step_order ASC");
        $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // If course ID is invalid or doesn't belong to instructor, redirect
        header("Location: manage_courses.php?error=Invalid course selected or permission denied.");
        exit;
    }
} else {
    // If no course ID is provided, redirect back to course management
    header("Location: manage_courses.php?error=Please select a course to manage its steps.");
    exit;
}

?>

<h2>Manage Steps for Course: <?php echo htmlspecialchars($course_title); ?> (ID: <?php echo $course_id; ?>)</h2>

<?php if (isset($_GET['message'])): ?>
    <div class="message success"><?php echo htmlspecialchars($_GET['message']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="message error"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>

<a href="add_step.php?course_id=<?php echo $course_id; ?>" class="button">Add New Step</a>
<a href="manage_courses.php" class="button-secondary">Back to My Courses</a>

<table class="table-styled">
    <thead>
        <tr>
            <th>ID</th>
            <th>Order</th>
            <th>Title</th>
            <th>Content Snippet</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($steps)): ?>
            <tr>
                <td colspan="5">No steps found for this course.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($steps as $step): ?>
                <tr>
                    <td><?php echo htmlspecialchars($step['step_id']); ?></td>
                    <td><?php echo htmlspecialchars($step['step_order']); ?></td>
                    <td><?php echo htmlspecialchars($step['title']); ?></td>
                    <td><?php echo htmlspecialchars(substr(strip_tags($step['content']), 0, 100)) . (strlen(strip_tags($step['content'])) > 100 ? '...' : ''); ?></td>
                    <td>
                        <a href="edit_step.php?id=<?php echo $step['step_id']; ?>" class="button-small">Edit</a>
                        <a href="delete_step.php?id=<?php echo $step['step_id']; ?>&course_id=<?php echo $course_id; // Pass course_id for redirect ?>" class="button-small delete" onclick="return confirm('Are you sure you want to delete this step and its tasks?');">Delete</a>
                        <a href="manage_tasks.php?step_id=<?php echo $step['step_id']; ?>" class="button-small">Manage Tasks</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

