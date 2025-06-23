<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('student'); // Ensure user is a student and logged in

$userId = $auth->getUserId();
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    $_SESSION['message'] = "Invalid course ID.";
    $_SESSION['error'] = true;
    header("Location: my_courses.php");
    exit;
}

// Function to generate video embed code (using Bootstrap ratio)
function getVideoEmbed($url) {
    if (empty($url)) return '';

    $embedHtml = '';
    // Check for YouTube
    if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]+)/', $url, $matches)) {
        $videoId = $matches[1];
        $embedHtml = '<div class="ratio ratio-16x9 mb-3"><iframe src="https://www.youtube.com/embed/' . $videoId . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
    } 
    // Add checks for other platforms like Vimeo if needed
    // else if (preg_match(...)) { ... }
    
    // Basic fallback link if no specific platform matched but URL exists
    elseif (filter_var($url, FILTER_VALIDATE_URL)) {
         $embedHtml = '<p class="mb-3"><a href="' . htmlspecialchars($url) . '" target="_blank" class="btn btn-outline-secondary btn-sm">Watch Video (External Link)</a></p>';
    }

    return $embedHtml;
}

// Verify enrollment
try {
    $stmt_enroll = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE user_id = :user_id AND course_id = :course_id");
    $stmt_enroll->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt_enroll->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt_enroll->execute();

    if (!$stmt_enroll->fetch()) {
        $_SESSION['message'] = "You are not enrolled in this course.";
        $_SESSION['error'] = true;
        header("Location: /cms/public/view_course_details.php?id=" . $course_id);
        exit;
    }

    // Fetch course details
    $stmt_course = $conn->prepare("SELECT title FROM courses WHERE course_id = :course_id");
    $stmt_course->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt_course->execute();
    $course = $stmt_course->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        $_SESSION['message'] = "Course not found.";
        $_SESSION['error'] = true;
        header("Location: my_courses.php");
        exit;
    }

    // Fetch course steps (including video_path) and tasks
    $stmt_steps = $conn->prepare("SELECT step_id, title, content, video_path, step_order FROM steps WHERE course_id = :course_id ORDER BY step_order ASC");
    $stmt_steps->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt_steps->execute();
    $steps = $stmt_steps->fetchAll(PDO::FETCH_ASSOC);

    $courseStructure = [];
    foreach ($steps as $step) {
        $stmt_tasks = $conn->prepare("SELECT task_id, title, description FROM tasks WHERE step_id = :step_id");
        $stmt_tasks->bindParam(':step_id', $step['step_id'], PDO::PARAM_INT);
        $stmt_tasks->execute();
        $tasks = $stmt_tasks->fetchAll(PDO::FETCH_ASSOC);
        $step['tasks'] = $tasks;
        $courseStructure[] = $step;
    }

} catch (PDOException $e) {
    error_log("Error fetching course content: " . $e->getMessage());
    $_SESSION['message'] = "Could not load course content due to a database error.";
    $_SESSION['error'] = true;
    header("Location: my_courses.php");
    exit;
}

$pageTitle = "Course: " . htmlspecialchars($course['title']);
// Use the main header for consistency
include __DIR__ . 
    '/../templates/partials/header.php'; 
?>

<div class="container mt-4">
    <h1 class="mb-4 text-center display-5"><?php echo htmlspecialchars($course['title']); ?></h1>

    <?php // Messages are handled by header.php ?>

    <div class="accordion" id="courseAccordion">
        <?php if (empty($courseStructure)): ?>
            <div class="alert alert-warning" role="alert">
                This course does not have any content yet.
            </div>
        <?php else: ?>
            <?php foreach ($courseStructure as $index => $step): 
                $accordionId = "step-" . $step['step_id'];
                $isFirst = ($index === 0);
            ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-<?php echo $accordionId; ?>">
                        <button class="accordion-button <?php echo $isFirst ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $accordionId; ?>" aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>" aria-controls="collapse-<?php echo $accordionId; ?>">
                            Step <?php echo htmlspecialchars($step['step_order']); ?>: <?php echo htmlspecialchars($step['title']); ?>
                        </button>
                    </h2>
                    <div id="collapse-<?php echo $accordionId; ?>" class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>" aria-labelledby="heading-<?php echo $accordionId; ?>" data-bs-parent="#courseAccordion">
                        <div class="accordion-body">
                            <?php echo getVideoEmbed($step["video_path"]); ?>
                            <div class="step-content mb-3">
                                <?php echo nl2br(htmlspecialchars($step["content"])); ?>
                            </div>
                            
                            <?php if (!empty($step['tasks'])): ?>
                                <h5 class="mt-4">Tasks for this step:</h5>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($step['tasks'] as $task): ?>
                                        <li class="list-group-item">
                                            <strong><?php echo htmlspecialchars($task["title"]); ?></strong>
                                            <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                                            <!-- Add task completion status/interaction here if needed -->
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted fst-italic">No tasks defined for this step.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="mt-4">
        <a href="my_courses.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
    </div>
</div>

<?php
// Use the main footer for consistency
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

