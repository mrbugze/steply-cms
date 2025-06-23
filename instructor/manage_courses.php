<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor'); // Ensure user is an instructor

$pageTitle = "Manage My Courses";
// Use the main header for consistency
include __DIR__ . 
    '/../templates/partials/header.php'; 

$instructor_id = $_SESSION['user_id'];

// Fetch courses assigned to this instructor
$stmt = $conn->prepare("SELECT course_id, title, description, price, created_at 
                        FROM courses 
                        WHERE instructor_id = :instructor_id 
                        ORDER BY created_at DESC");
$stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage My Courses</h2>
        <a href="add_course.php" class="btn btn-primary">Add New Course</a>
    </div>

    <?php // Messages are handled by header.php ?>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courses)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">You have not created any courses yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_id']); ?></td>
                            <td><?php echo htmlspecialchars($course['title']); ?></td>
                            <td><?php echo htmlspecialchars(substr($course['description'], 0, 80)) . (strlen($course['description']) > 80 ? '...' : ''); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format($course['price'], 2)); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($course['created_at']))); ?></td>
                            <td class="actions">
                                <a href="edit_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <a href="manage_steps.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-info">Steps</a>
                                <a href="manage_students.php?course_id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-warning">Students</a>
                                <a href="delete_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this course and all its steps/tasks?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Use the main footer for consistency
include __DIR__ . 
    '/../templates/partials/footer.php'; 
?>

