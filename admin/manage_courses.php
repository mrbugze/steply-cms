<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Manage Courses";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages

// Fetch all courses with instructor names
$stmt = $conn->prepare("SELECT c.course_id, c.title, c.description, c.price, c.instructor_id, u.username AS instructor_name, c.created_at 
                        FROM courses c 
                        LEFT JOIN users u ON c.instructor_id = u.user_id 
                        ORDER BY c.created_at DESC"); // Removed instructor role check to show all courses
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="container mt-4">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Courses</h2>
    <a href="add_course.php" class="btn btn-primary">Add New Course</a>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="thead-light">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Description</th>
                <th>Price</th>
                <th>Instructor</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($courses)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">No courses found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_id']); ?></td>
                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                        <td><?php echo htmlspecialchars(substr($course['description'], 0, 80)) . (strlen($course['description']) > 80 ? '...' : ''); ?></td>
                        <td>$<?php echo number_format($course['price'], 2); ?></td>
                        <td><?php echo $course['instructor_id'] ? htmlspecialchars($course['instructor_name']) . ' (ID: ' . $course['instructor_id'] . ')' : '<span class="text-muted">Not Assigned</span>'; ?></td>
                        <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($course['created_at']))); ?></td>
                        <td class="actions">
                            <a href="edit_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <a href="delete_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this course and all its steps/tasks?');">Delete</a>
                            <a href="manage_steps.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-info">Steps</a>
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

