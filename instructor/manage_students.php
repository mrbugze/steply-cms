<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor'); // Ensure user is an instructor

$pageTitle = "Manage My Students";
include __DIR__ . 
    '/../templates/partials/header.php';

$instructor_id = $_SESSION['user_id'];
$students = [];
$filter_course_id = isset($_GET['course_id']) && is_numeric($_GET['course_id']) ? intval($_GET['course_id']) : null;

// Fetch students enrolled in courses taught by this instructor
$sql = "SELECT DISTINCT u.user_id, u.username, u.email, c.course_id, c.title AS course_title, e.enrollment_date 
        FROM users u 
        JOIN enrollments e ON u.user_id = e.user_id 
        JOIN courses c ON e.course_id = c.course_id 
        WHERE c.instructor_id = :instructor_id";

if ($filter_course_id) {
    $sql .= " AND c.course_id = :filter_course_id";
}

$sql .= " ORDER BY c.title ASC, u.username ASC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
if ($filter_course_id) {
    $stmt->bindParam(':filter_course_id', $filter_course_id, PDO::PARAM_INT);
}
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch instructor's courses for the filter dropdown
$stmtCourses = $conn->prepare("SELECT course_id, title FROM courses WHERE instructor_id = :instructor_id ORDER BY title ASC");
$stmtCourses->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmtCourses->execute();
$instructor_courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

?><div class="container mt-4">


<h2>Manage My Students</h2>

<form action="manage_students.php" method="get" class="filter-form">
    <label for="course_filter">Filter by Course:</label>
    <select name="course_id" id="course_filter" onchange="this.form.submit()">
        <option value="">All My Courses</option>
        <?php foreach ($instructor_courses as $course): ?>
            <option value="<?php echo $course['course_id']; ?>" <?php echo ($filter_course_id == $course['course_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($course['title']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <noscript><button type="submit">Filter</button></noscript>
</form>

<table class="table table-striped table-hover" >
    <thead class="thead-light">
        <tr>
            <th>Student ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Course Enrolled In</th>
            <th>Enrollment Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($students)): ?>
            <tr>
                <td colspan="6">No students found<?php echo $filter_course_id ? ' for this course' : ''; ?>.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo htmlspecialchars($student['course_title']); ?> (ID: <?php echo $student['course_id']; ?>)</td>
                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($student['enrollment_date']))); ?></td>
                    <td>
                        <!-- Add actions like viewing progress, messaging, etc. if needed -->
                        <small>No actions available yet.</small>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>
<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

