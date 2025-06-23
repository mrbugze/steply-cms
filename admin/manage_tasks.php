<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "View All Tasks"; // Changed title
include __DIR__ . 
    '/../templates/partials/header.php';

$tasks = [];

// Fetch all tasks with their course and step context
$stmt = $conn->prepare("SELECT t.task_id, t.title AS task_title, t.task_type, t.task_data, 
                        s.step_id, s.title AS step_title, 
                        c.course_id, c.title AS course_title
                        FROM tasks t
                        JOIN steps s ON t.step_id = s.step_id
                        JOIN courses c ON s.course_id = c.course_id
                        ORDER BY c.title ASC, s.step_order ASC, t.task_id ASC");
$stmt->execute();
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-4">
    <h2 class="text-center mb-4">View All Tasks</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert <?php echo ($_SESSION['error'] ?? false) ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['error']); ?>
    <?php endif; ?>

    <p class="text-muted mb-3">This page provides an overview of all tasks across all courses and steps. Task management (add, edit, delete) is now performed directly within the course editing interface for instructors and admins.</p>
    
    <div class="d-flex justify-content-start mb-3">
        <a href="manage_courses.php" class="btn btn-secondary">Back to Manage Courses</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">All System Tasks</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Task ID</th>
                        <th scope="col">Course</th>
                        <th scope="col">Step</th>
                        <th scope="col">Task Title</th>
                        <th scope="col">Type</th>
                        <th scope="col">Content Snippet</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr>
                            <td colspan="7" class="text-center p-4">No tasks found in the system.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['task_id']); ?></td>
                                <td>
                                    <a href="edit_course.php?id=<?php echo $task['course_id']; ?>">
                                        <?php echo htmlspecialchars($task['course_title']); ?> (ID: <?php echo $task['course_id']; ?>)
                                    </a>
                                </td>
                                <td>
                                    <a href="edit_course.php?id=<?php echo $task['course_id']; ?>#step-<?php echo $task['step_id']; ?>">
                                        <?php echo htmlspecialchars($task['step_title']); ?> (ID: <?php echo $task['step_id']; ?>)
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($task['task_title']); ?></td>
                                <td><?php echo htmlspecialchars($task['task_type']); ?></td>
                                <td><?php echo htmlspecialchars(substr(strip_tags($task['content'] ?? ''), 0, 70)) . (strlen(strip_tags($task['content'] ?? '')) > 70 ? '...' : ''); ?></td>
                                <td>
                                    <a href="../instructor/edit_course.php?id=<?php echo $task['course_id']; ?>#step-<?php echo $task['step_id']; ?>" class="btn btn-sm btn-outline-primary">View/Edit in Course</a>
                                    <!-- Direct edit/delete removed as tasks are managed within course context -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

