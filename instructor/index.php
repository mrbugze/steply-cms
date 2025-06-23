<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor'); // Ensure user is an instructor

$pageTitle = "Instructor Dashboard";
// Use the main header now for consistency, assuming it handles instructor nav
include __DIR__ . 
    '/../templates/partials/header.php'; 

$instructor_id = $_SESSION['user_id'];

// Fetch some stats for the dashboard

// Count assigned courses
$stmtCourses = $conn->prepare("SELECT COUNT(*) AS course_count FROM courses WHERE instructor_id = :instructor_id");
$stmtCourses->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmtCourses->execute();
$course_count = $stmtCourses->fetchColumn();

// Count unique students enrolled in the instructor's courses
$stmtStudents = $conn->prepare("SELECT COUNT(DISTINCT e.user_id) AS student_count 
                             FROM enrollments e 
                             JOIN courses c ON e.course_id = c.course_id 
                             WHERE c.instructor_id = :instructor_id");
$stmtStudents->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmtStudents->execute();
$student_count = $stmtStudents->fetchColumn();

?>

<div class="container mt-4">
    <h2 class="mb-4">Welcome, <span class="text-primary"><?php echo htmlspecialchars($_SESSION['username']); ?></span>!</h2>

    <div class="row g-4">
        <div class="col-md-6" style="
    margin-bottom: 16px;">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Courses Assigned</h5>
                    <p class="card-text display-4 text-primary"><?php echo $course_count; ?></p>
                    <a href="manage_courses.php" class="btn btn-outline-primary">Manage Courses</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Students Enrolled</h5>
                    <p class="card-text display-4 text-info"><?php echo $student_count; ?></p>
                    <a href="manage_students.php" class="btn btn-outline-info">View Students</a> 
                    </div>
            </div>
        </div>
        <!-- Add more summary cards as needed -->
    </div>

    <div class="mt-5" style="
    margin-top: 26px;">
        <h4>Quick Actions</h4>
        <a href="add_course.php" class="btn btn-success">Create New Course</a>
        <!-- Add other relevant quick action links -->
    </div>

</div>

<?php
// Use the main footer now for consistency
include __DIR__ . 
    '/../templates/partials/footer.php'; 
?>

