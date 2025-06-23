<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('student'); // Ensure the user is a student

$student_id = $_SESSION["user_id"];
$pageTitle = "My Courses";
// Use the main header for consistency
include __DIR__ . 
    '/../templates/partials/header.php'; 

// Fetch enrolled courses for the student
$stmt = $conn->prepare("SELECT c.course_id, c.title, c.description, c.image_path 
                           FROM courses c
                           JOIN enrollments e ON c.course_id = e.course_id
                           WHERE e.user_id = :student_id
                           ORDER BY e.enrollment_date DESC");
$stmt->bindParam(":student_id", $student_id, PDO::PARAM_INT);
$stmt->execute();
$enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-4">
    <h2 class="mb-4 text-center">My Enrolled Courses</h2>

    <?php // Messages are handled by header.php ?>

    <?php if (empty($enrolled_courses)): ?>
        <div class="alert alert-info text-center" role="alert">
            You are not enrolled in any courses yet.
        </div>
        <div class="text-center mt-3">
            <a href="../public/index.php" class="btn btn-primary">Browse Course Catalog</a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($enrolled_courses as $course): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm course-card-student">
                        <?php 
                        $image_url = 'https://archive.org/download/placeholder-image//placeholder-image.jpg'; // Default image
                        if (!empty($course["image_path"])) {
                            // Ensure the path starts correctly, assuming image_path is relative to /public
                            $relative_path = ltrim($course["image_path"], '/');
                            $potential_path = __DIR__ . '/../public/' . $relative_path;
                            // Basic check if file exists, might need refinement based on actual structure
                            if (file_exists($potential_path)) { 
                                $image_url = '../public/' . htmlspecialchars($relative_path);
                            }
                        }
                        ?>
                        <img src="<?php echo $image_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($course["title"]); ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($course["title"]); ?></h5>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars(substr($course["description"], 0, 100)) . (strlen($course["description"]) > 100 ? '...' : ''); ?></p>
                            <a href="view_course.php?id=<?php echo $course["course_id"]; ?>" class="btn btn-primary mt-auto align-self-start">Go to Course</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Use the main footer for consistency
include __DIR__ . 
    '/../templates/partials/footer.php'; 
?>

