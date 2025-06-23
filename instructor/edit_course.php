<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('instructor');

$pageTitle = "Edit Course";
include __DIR__ . 
    '/../templates/partials/header.php';

$message = "";
$error = false;
$course = null;
$steps_with_tasks = []; // To hold existing steps and their tasks
$instructor_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid course ID.";
    $_SESSION['error'] = true;
    header("Location: manage_courses.php");
    exit;
}
$course_id = intval($_GET['id']);

// Fetch course data, ensuring it belongs to the logged-in instructor
$stmtCourse = $conn->prepare("SELECT * FROM courses WHERE course_id = :course_id AND instructor_id = :instructor_id");
$stmtCourse->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmtCourse->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
$stmtCourse->execute();
$course = $stmtCourse->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    $_SESSION['message'] = "Course not found or permission denied.";
    $_SESSION['error'] = true;
    header("Location: manage_courses.php");
    exit;
}

// Fetch existing steps for the course
$stmtSteps = $conn->prepare("SELECT * FROM steps WHERE course_id = :course_id ORDER BY step_order ASC");
$stmtSteps->bindParam(':course_id', $course_id, PDO::PARAM_INT);
$stmtSteps->execute();
$existing_steps = $stmtSteps->fetchAll(PDO::FETCH_ASSOC);

foreach ($existing_steps as $step) {
    $stmtTasks = $conn->prepare("SELECT * FROM tasks WHERE step_id = :step_id ORDER BY task_id ASC"); // Assuming task_id implies order or add an order column
    $stmtTasks->bindParam(':step_id', $step['step_id'], PDO::PARAM_INT);
    $stmtTasks->execute();
    $step['tasks'] = $stmtTasks->fetchAll(PDO::FETCH_ASSOC);
    $steps_with_tasks[] = $step;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->beginTransaction();
    try {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = isset($_POST['price']) && is_numeric($_POST['price']) ? floatval($_POST['price']) : 0.00;
        $image_path = trim($_POST["image_path"] ?? $course['image_path']); // Keep old if not provided

        if (empty($title) || empty($description)) {
            throw new Exception("Title and Description are required.");
        }
        if ($price < 0) {
            throw new Exception("Price cannot be negative.");
        }

        // Update course details
        $stmtUpdateCourse = $conn->prepare("UPDATE courses SET title = :title, description = :description, price = :price, image_path = :image_path WHERE course_id = :course_id AND instructor_id = :instructor_id");
        $stmtUpdateCourse->bindParam(':title', $title);
        $stmtUpdateCourse->bindParam(':description', $description);
        $stmtUpdateCourse->bindParam(':price', $price);
        $stmtUpdateCourse->bindParam(':image_path', $image_path);
        $stmtUpdateCourse->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmtUpdateCourse->bindParam(':instructor_id', $instructor_id, PDO::PARAM_INT);
        $stmtUpdateCourse->execute();

        // Manage Steps and Tasks
        // For simplicity in this example: delete existing steps/tasks and re-add. 
        // A more robust solution would handle updates, deletions, and additions individually.
        $stmtDeleteTasks = $conn->prepare("DELETE tasks FROM tasks JOIN steps ON tasks.step_id = steps.step_id WHERE steps.course_id = :course_id");
        $stmtDeleteTasks->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmtDeleteTasks->execute();

        $stmtDeleteSteps = $conn->prepare("DELETE FROM steps WHERE course_id = :course_id");
        $stmtDeleteSteps->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmtDeleteSteps->execute();

        // Re-add steps and tasks from POST data (similar to add_course.php)
        if (isset($_POST['steps']) && is_array($_POST['steps'])) {
            foreach ($_POST['steps'] as $step_data) {
                $step_title = trim($step_data['title'] ?? '');
                $step_content = trim($step_data['content'] ?? '');
                $step_video_path = trim($step_data['video_path'] ?? '');
                $step_order = isset($step_data['order']) && is_numeric($step_data['order']) ? intval($step_data['order']) : 0;

                if (empty($step_title) || empty($step_content)) continue;

                $stmtStep = $conn->prepare("INSERT INTO steps (course_id, title, content, video_path, step_order) VALUES (:course_id, :title, :content, :video_path, :step_order)");
                $stmtStep->bindParam(':course_id', $course_id, PDO::PARAM_INT);
                $stmtStep->bindParam(':title', $step_title);
                $stmtStep->bindParam(':content', $step_content);
                $stmtStep->bindParam(':video_path', $step_video_path);
                $stmtStep->bindParam(':step_order', $step_order, PDO::PARAM_INT);
                $stmtStep->execute();
                $new_step_id = $conn->lastInsertId();

                if (isset($step_data['tasks']) && is_array($step_data['tasks'])) {
                    foreach ($step_data['tasks'] as $task_data) {
                        $task_title = trim($task_data['title'] ?? '');
                        $task_type = trim($task_data['type'] ?? 'assignment');
                        $task_content = trim($task_data['content'] ?? '');

                        if (empty($task_title)) continue;

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
        $_SESSION['message'] = "Course updated successfully.";
        $_SESSION['error'] = false;
        header("Location: manage_courses.php");
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        $message = $e->getMessage();
        $error = true;
        // Repopulate form with submitted data on error
        $course['title'] = $_POST['title'] ?? $course['title'];
        $course['description'] = $_POST['description'] ?? $course['description'];
        $course['price'] = $_POST['price'] ?? $course['price'];
        $course['image_path'] = $_POST['image_path'] ?? $course['image_path'];
        // Re-populating dynamic steps/tasks on error is more complex and omitted for brevity here
        // but in a real app, you'd want to preserve user input.
    }
}

?>

<div class="container mt-4">
    <h2 class="text-center mb-4">Edit Course (ID: <?php echo htmlspecialchars($course['course_id']); ?>)</h2>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $error ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="edit_course.php?id=<?php echo $course['course_id']; ?>" method="post" id="editCourseForm" class="form-container card p-4 shadow-sm">
        <fieldset class="mb-4">
            <legend class="h5 mb-3">Course Details</legend>
            <div class="form-group mb-3">
                <label for="title" class="form-label">Course Title:</label>
                <input type="text" id="title" name="title" class="form-control" required value="<?php echo htmlspecialchars($course['title']); ?>">
            </div>
            <div class="form-group mb-3">
                <label for="description" class="form-label">Description:</label>
                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($course['description']); ?></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 form-group mb-3">
                    <label for="price" class="form-label">Price ($):</label>
                    <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars(number_format((float)($course['price'] ?? 0.00), 2, '.', '')); ?>" required>
                </div>
                <div class="col-md-6 form-group mb-3">
                    <label for="image_path" class="form-label">Image Path (Optional):</label>
                    <input type="text" id="image_path" name="image_path" class="form-control" value="<?php echo htmlspecialchars($course['image_path'] ?? ''); ?>" placeholder="/uploads/course_image.jpg">
                    <div class="form-text">Relative path from /cms. E.g., /uploads/course_image.jpg</div>
                </div>
            </div>
        </fieldset>

        <fieldset class="mb-4">
            <legend class="h5 mb-3 d-flex justify-content-between align-items-center">
                Course Steps
                <button type="button" id="addStepBtn" class="btn btn-sm btn-outline-success">+ Add Step</button>
            </legend>
            <div id="stepsContainer">
                <?php foreach ($steps_with_tasks as $s_idx => $existing_step): ?>
                <div class="step-entry card mb-3 p-3" id="step-<?php echo $s_idx; ?>">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Step <?php echo $s_idx + 1; ?></h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-step-btn" data-step-id="step-<?php echo $s_idx; ?>">Remove Step</button>
                    </div>
                    <div class="form-group mb-2">
                        <label for="step-<?php echo $s_idx; ?>-title" class="form-label">Step Title:</label>
                        <input type="text" id="step-<?php echo $s_idx; ?>-title" name="steps[<?php echo $s_idx; ?>][title]" class="form-control" required value="<?php echo htmlspecialchars($existing_step['title']); ?>">
                    </div>
                    <div class="form-group mb-2">
                        <label for="step-<?php echo $s_idx; ?>-content" class="form-label">Step Content:</label>
                        <textarea id="step-<?php echo $s_idx; ?>-content" name="steps[<?php echo $s_idx; ?>][content]" class="form-control" rows="3" required><?php echo htmlspecialchars($existing_step['content']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group mb-2">
                            <label for="step-<?php echo $s_idx; ?>-video_path" class="form-label">Video Path (Optional):</label>
                            <input type="text" id="step-<?php echo $s_idx; ?>-video_path" name="steps[<?php echo $s_idx; ?>][video_path]" class="form-control" placeholder="/uploads/step_video.mp4" value="<?php echo htmlspecialchars($existing_step['video_path'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 form-group mb-2">
                            <label for="step-<?php echo $s_idx; ?>-order" class="form-label">Step Order:</label>
                            <input type="number" id="step-<?php echo $s_idx; ?>-order" name="steps[<?php echo $s_idx; ?>][order]" class="form-control" value="<?php echo htmlspecialchars($existing_step['step_order']); ?>" required>
                        </div>
                    </div>
                    <div class="tasks-section mt-2 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Tasks for this Step</small>
                            <button type="button" class="btn btn-sm btn-outline-info add-task-btn" data-step-index="<?php echo $s_idx; ?>">+ Add Task to Step</button>
                        </div>
                        <div class="tasks-container" id="tasks-container-<?php echo $s_idx; ?>">
                            <?php foreach ($existing_step['tasks'] as $t_idx => $existing_task): ?>
                            <div class="task-entry card mb-2 p-2 bg-light" id="step-<?php echo $s_idx; ?>-task-<?php echo $t_idx; ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="mb-0 text-secondary">Task <?php echo $t_idx + 1; ?></small>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-task-btn" data-task-id="step-<?php echo $s_idx; ?>-task-<?php echo $t_idx; ?>">Remove Task</button>
                                </div>
                                <div class="form-group mb-1">
                                    <label for="step-<?php echo $s_idx; ?>-task-<?php echo $t_idx; ?>-title" class="form-label form-label-sm">Task Title:</label>
                                    <input type="text" id="step-<?php echo $s_idx; ?>-task-<?php echo $t_idx; ?>-title" name="steps[<?php echo $s_idx; ?>][tasks][<?php echo $t_idx; ?>][title]" class="form-control form-control-sm" required value="<?php echo htmlspecialchars($existing_task['title']); ?>">
                                </div>
                                <div class="form-group mb-1">
                                    <label for="step-<?php echo $s_idx; ?>-task-<?php echo $t_idx; ?>-type" class="form-label form-label-sm">Task Type:</label>
                                    <select id="step-<?php echo $s_idx; ?>-task-<?php echo $t_idx; ?>-type" name="steps[<?php echo $s_idx; ?>][tasks][<?php echo $t_idx; ?>][type]" class="form-select form-select-sm">
                                        <option value="assignment" <?php echo ($existing_task['task_type'] == 'assignment') ? 'selected' : ''; ?>>Assignment</option>
                                        <option value="quiz" <?php echo ($existing_task['task_type'] == 'quiz') ? 'selected' : ''; ?>>Quiz</option>
                                        <option value="reading" <?php echo ($existing_task['task_type'] == 'reading') ? 'selected' : ''; ?>>Reading</option>
                                        <option value="video" <?php echo ($existing_task['task_type'] == 'video') ? 'selected' : ''; ?>>Video</option>
                                        <option value="discussion" <?php echo ($existing_task['task_type'] == 'discussion') ? 'selected' : ''; ?>>Discussion</option>
                                    </select>
                                </div>
                                <div class="form-group mb-1">
                                    <label for="step-<?php echo $s_idx; ?>-task-<?php echo $t_idx; ?>-content" class="form-label form-label-sm">Task Content/Instructions (Optional):</label>
                                    <textarea id="step-<?php echo $s_idx; ?>-task-<?php echo $t_idx; ?>-content" name="steps[<?php echo $s_idx; ?>][tasks][<?php echo $t_idx; ?>][content]" class="form-control form-control-sm" rows="2"><?php echo htmlspecialchars($existing_task['content']); ?></textarea>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="d-flex justify-content-end mt-4">
            <a href="manage_courses.php" class="btn btn-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Course</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let stepIndex = <?php echo count($steps_with_tasks); ?>; // Initialize with count of existing steps

    document.getElementById('addStepBtn').addEventListener('click', function () {
        const stepsContainer = document.getElementById('stepsContainer');
        const stepId = `step-${stepIndex}`;

        const stepDiv = document.createElement('div');
        stepDiv.classList.add('step-entry', 'card', 'mb-3', 'p-3');
        stepDiv.setAttribute('id', stepId);
        stepDiv.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">New Step ${stepIndex + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-step-btn" data-step-id="${stepId}">Remove Step</button>
            </div>
            <div class="form-group mb-2">
                <label for="${stepId}-title" class="form-label">Step Title:</label>
                <input type="text" id="${stepId}-title" name="steps[${stepIndex}][title]" class="form-control" required>
            </div>
            <div class="form-group mb-2">
                <label for="${stepId}-content" class="form-label">Step Content:</label>
                <textarea id="${stepId}-content" name="steps[${stepIndex}][content]" class="form-control" rows="3" required></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 form-group mb-2">
                    <label for="${stepId}-video_path" class="form-label">Video Path (Optional):</label>
                    <input type="text" id="${stepId}-video_path" name="steps[${stepIndex}][video_path]" class="form-control" placeholder="/uploads/step_video.mp4">
                </div>
                <div class="col-md-6 form-group mb-2">
                    <label for="${stepId}-order" class="form-label">Step Order:</label>
                    <input type="number" id="${stepId}-order" name="steps[${stepIndex}][order]" class="form-control" value="${stepIndex}" required>
                </div>
            </div>
            <div class="tasks-section mt-2 pt-2 border-top">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted">Tasks for this Step</small>
                    <button type="button" class="btn btn-sm btn-outline-info add-task-btn" data-step-index="${stepIndex}">+ Add Task to Step</button>
                </div>
                <div class="tasks-container" id="tasks-container-${stepIndex}">
                    <!-- Tasks for this step will be added here -->
                </div>
            </div>
        `;
        stepsContainer.appendChild(stepDiv);
        stepIndex++;
    });

    document.getElementById('stepsContainer').addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('remove-step-btn')) {
            const stepIdToRemove = e.target.dataset.stepId;
            document.getElementById(stepIdToRemove).remove();
        }
        if (e.target && e.target.classList.contains('add-task-btn')) {
            const currentStepIndex = e.target.dataset.stepIndex;
            const tasksContainer = document.getElementById(`tasks-container-${currentStepIndex}`);
            const taskIndex = tasksContainer.children.length;
            const taskId = `step-${currentStepIndex}-task-${taskIndex}`;

            const taskDiv = document.createElement('div');
            taskDiv.classList.add('task-entry', 'card', 'mb-2', 'p-2', 'bg-light');
            taskDiv.setAttribute('id', taskId);
            taskDiv.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="mb-0 text-secondary">New Task ${taskIndex + 1}</small>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-task-btn" data-task-id="${taskId}">Remove Task</button>
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
        if (e.target && e.target.classList.contains('remove-task-btn')) {
            const taskIdToRemove = e.target.dataset.taskId;
            document.getElementById(taskIdToRemove).remove();
        }
    });
});
</script>

<?php
include __DIR__ . 
    '/../templates/partials/instructor_footer.php';
?>

