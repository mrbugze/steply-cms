<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Add New Course (Admin)";
include __DIR__ . 
    '/../templates/partials/header.php';

$message = "";
$error = false;

// Fetch instructors for dropdown
$stmtInstructors = $conn->prepare("SELECT user_id, username FROM users WHERE role = 'instructor'");
$stmtInstructors->execute();
$instructors = $stmtInstructors->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->beginTransaction(); // Start transaction
    try {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = isset($_POST['price']) && is_numeric($_POST['price']) ? floatval($_POST['price']) : 0.00;
        $instructor_id = !empty($_POST['instructor_id']) ? intval($_POST['instructor_id']) : null;
        $image_path = trim($_POST["image_path"] ?? "");

        if (empty($title) || empty($description)) {
            throw new Exception("Title and Description are required.");
        }
        if ($price < 0) {
            throw new Exception("Price cannot be negative.");
        }

        // Validate instructor ID if provided
        if ($instructor_id !== null) {
            $stmtCheckInstructor = $conn->prepare("SELECT user_id FROM users WHERE user_id = :instructor_id AND role = 'instructor'");
            $stmtCheckInstructor->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
            $stmtCheckInstructor->execute();
            if (!$stmtCheckInstructor->fetch()) {
                throw new Exception("Invalid instructor selected.");
            }
        }

        // Insert course
        $stmtCourse = $conn->prepare("INSERT INTO courses (instructor_id, title, description, price, image_path) VALUES (:instructor_id, :title, :description, :price, :image_path)");
        $stmtCourse->bindParam(':instructor_id', $instructor_id, $instructor_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmtCourse->bindParam(':title', $title);
        $stmtCourse->bindParam(':description', $description);
        $stmtCourse->bindParam(':price', $price);
        $stmtCourse->bindParam(":image_path", $image_path);
        $stmtCourse->execute();
        $new_course_id = $conn->lastInsertId();

        // Process steps
        if (isset($_POST['steps']) && is_array($_POST['steps'])) {
            foreach ($_POST['steps'] as $step_data) {
                $step_title = trim($step_data['title'] ?? '');
                $step_content = trim($step_data['content'] ?? '');
                $step_video_path = trim($step_data['video_path'] ?? '');
                $step_order = isset($step_data['order']) && is_numeric($step_data['order']) ? intval($step_data['order']) : 0;

                if (empty($step_title) || empty($step_content)) {
                    continue; 
                }

                $stmtStep = $conn->prepare("INSERT INTO steps (course_id, title, content, video_path, step_order) VALUES (:course_id, :title, :content, :video_path, :step_order)");
                $stmtStep->bindParam(':course_id', $new_course_id, PDO::PARAM_INT);
                $stmtStep->bindParam(':title', $step_title);
                $stmtStep->bindParam(':content', $step_content);
                $stmtStep->bindParam(':video_path', $step_video_path);
                $stmtStep->bindParam(':step_order', $step_order, PDO::PARAM_INT);
                $stmtStep->execute();
                $new_step_id = $conn->lastInsertId();

                // Process tasks for this step
                if (isset($step_data['tasks']) && is_array($step_data['tasks'])) {
                    foreach ($step_data['tasks'] as $task_data) {
                        $task_title = trim($task_data['title'] ?? '');
                        $task_type = trim($task_data['type'] ?? 'assignment');
                        $task_content = trim($task_data['content'] ?? '');

                        if (empty($task_title)) {
                            continue; 
                        }

                        $stmtTask = $conn->prepare("INSERT INTO tasks (step_id, title, task_type, content) VALUES (:step_id, :title, :task_type, :content)");
                        $stmtTask->bindParam(':step_id', $new_step_id, PDO::PARAM_INT);
                        $stmtTask->bindParam(':title', $task_title);
                        $stmtTask->bindParam(':task_type', $task_type);
                        $stmtTask->bindParam(':content', $task_content);
                        $stmtTask->execute();
                    }
                }
            }
        }

        $conn->commit(); 
        $_SESSION['message'] = "Course, steps, and tasks added successfully by admin.";
        $_SESSION['error'] = false;
        header("Location: manage_courses.php");
        exit;

    } catch (Exception $e) {
        $conn->rollBack(); 
        $message = $e->getMessage();
        $error = true;
    }
}

?>

<div class="container mt-4">
    <h2 class="text-center mb-4">Add New Course (Admin)</h2>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $error ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="add_course.php" method="post" id="addCourseFormAdmin" class="form-container card p-4 shadow-sm">
        <fieldset class="mb-4">
            <legend class="h5 mb-3">Course Details</legend>
            <div class="form-group mb-3">
                <label for="title" class="form-label">Course Title:</label>
                <input type="text" id="title" name="title" class="form-control" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
            </div>
            <div class="form-group mb-3">
                <label for="description" class="form-label">Description:</label>
                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-4 form-group mb-3">
                    <label for="price" class="form-label">Price ($):</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : '0.00'; ?>" required>
                </div>
                <div class="col-md-4 form-group mb-3">
                    <label for="instructor_id" class="form-label">Assign Instructor (Optional):</label>
                    <select id="instructor_id" name="instructor_id" class="form-select">
                        <option value="">-- Select Instructor --</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?php echo $instructor['user_id']; ?>" <?php echo (isset($_POST['instructor_id']) && $_POST['instructor_id'] == $instructor['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($instructor['username']); ?> (ID: <?php echo $instructor['user_id']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 form-group mb-3">
                    <label for="image_path" class="form-label">Image Path (Optional):</label>
                    <input type="text" id="image_path" name="image_path" class="form-control" value="<?php echo isset($_POST["image_path"]) ? htmlspecialchars($_POST["image_path"]) : ""; ?>" placeholder="/uploads/course_image.jpg">
                     <div class="form-text">Relative path from /cms. E.g., /uploads/course_image.jpg</div>
                </div>
            </div>
        </fieldset>

        <fieldset class="mb-4">
            <legend class="h5 mb-3 d-flex justify-content-between align-items-center">
                Course Steps
                <button type="button" id="addStepBtnAdmin" class="btn btn-sm btn-outline-success">+ Add Step</button>
            </legend>
            <div id="stepsContainerAdmin">
                <!-- Steps will be added here by JavaScript -->
            </div>
        </fieldset>

        <div class="d-flex justify-content-end mt-4">
            <a href="manage_courses.php" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Add Course with Steps & Tasks</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let stepIndexAdmin = 0;

    document.getElementById('addStepBtnAdmin').addEventListener('click', function () {
        const stepsContainer = document.getElementById('stepsContainerAdmin');
        const stepId = `admin-step-${stepIndexAdmin}`;

        const stepDiv = document.createElement('div');
        stepDiv.classList.add('step-entry', 'card', 'mb-3', 'p-3');
        stepDiv.setAttribute('id', stepId);
        stepDiv.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Step ${stepIndexAdmin + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-step-btn-admin" data-step-id="${stepId}">Remove Step</button>
            </div>
            <div class="form-group mb-2">
                <label for="${stepId}-title" class="form-label">Step Title:</label>
                <input type="text" id="${stepId}-title" name="steps[${stepIndexAdmin}][title]" class="form-control" required>
            </div>
            <div class="form-group mb-2">
                <label for="${stepId}-content" class="form-label">Step Content:</label>
                <textarea id="${stepId}-content" name="steps[${stepIndexAdmin}][content]" class="form-control" rows="3" required></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 form-group mb-2">
                    <label for="${stepId}-video_path" class="form-label">Video Path (Optional):</label>
                    <input type="text" id="${stepId}-video_path" name="steps[${stepIndexAdmin}][video_path]" class="form-control" placeholder="/uploads/step_video.mp4">
                </div>
                <div class="col-md-6 form-group mb-2">
                    <label for="${stepId}-order" class="form-label">Step Order:</label>
                    <input type="number" id="${stepId}-order" name="steps[${stepIndexAdmin}][order]" class="form-control" value="${stepIndexAdmin}" required>
                </div>
            </div>
            <div class="tasks-section mt-2 pt-2 border-top">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">Tasks for this Step</small>
                    <button type="button" class="btn btn-sm btn-outline-info add-task-btn-admin" data-step-index="${stepIndexAdmin}">+ Add Task to Step</button>
                </div>
                <div class="tasks-container-admin" id="tasks-container-admin-${stepIndexAdmin}">
                    <!-- Tasks for this step will be added here -->
                </div>
            </div>
        `;
        stepsContainer.appendChild(stepDiv);
        stepIndexAdmin++;
    });

    document.getElementById('stepsContainerAdmin').addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove-step-btn-admin')) {
            const stepIdToRemove = e.target.dataset.stepId;
            document.getElementById(stepIdToRemove).remove();
        }
        if (e.target && e.target.classList.contains('add-task-btn-admin')) {
            const currentStepIndex = e.target.dataset.stepIndex;
            const tasksContainer = document.getElementById(`tasks-container-admin-${currentStepIndex}`);
            const taskIndex = tasksContainer.children.length;
            const taskId = `admin-step-${currentStepIndex}-task-${taskIndex}`;

            const taskDiv = document.createElement('div');
            taskDiv.classList.add('task-entry', 'card', 'mb-2', 'p-2', 'bg-light');
            taskDiv.setAttribute('id', taskId);
            taskDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="mb-0 text-secondary">Task ${taskIndex + 1}</small>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-task-btn-admin" data-task-id="${taskId}">Remove Task</button>
                </div>
                <div class="form-group mb-1">
                    <label for="${taskId}-title" class="form-label form-label-sm">Task Title:</label>
                    <input type="text" id="${taskId}-title" name="steps[${currentStepIndex}][tasks][${taskIndex}][title]" class="form-control form-control-sm" required>
                </div>
                <div class="form-group mb-1">
                    <label for="${taskId}-type" class="form-label form-label-sm">Task Type:</label>
                    <select id="${taskId}-type" name="steps[${currentStepIndex}][tasks][${taskIndex}][type]" class="form-select form-select-sm">
                        <option value="assignment">Assignment</option>
                        <option value="quiz">Quiz</option>
                        <option value="reading">Reading</option>
                        <option value="video">Video</option>
                        <option value="discussion">Discussion</option>
                    </select>
                </div>
                <div class="form-group mb-1">
                    <label for="${taskId}-content" class="form-label form-label-sm">Task Content/Instructions (Optional):</label>
                    <textarea id="${taskId}-content" name="steps[${currentStepIndex}][tasks][${taskIndex}][content]" class="form-control form-control-sm" rows="2"></textarea>
                </div>
            `;
            tasksContainer.appendChild(taskDiv);
        }
        if (e.target && e.target.classList.contains('remove-task-btn-admin')) {
            const taskIdToRemove = e.target.dataset.taskId;
            document.getElementById(taskIdToRemove).remove();
        }
    });
});
</script>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

