<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$isLoggedIn = $auth->isLoggedIn();
$userRole = $isLoggedIn ? $auth->getRole() : null;
$userId = $isLoggedIn ? $auth->getUserId() : null;

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Use session messages set in header.php

if ($course_id <= 0) {
    $_SESSION['message'] = "Invalid course ID.";
    $_SESSION['error'] = true;
    header("Location: index.php");
    exit;
}

// Fetch course details
try {
    $stmt = $conn->prepare("SELECT c.course_id, c.title, c.description, c.price, c.image_path, u.username AS instructor_name 
                           FROM courses c 
                           LEFT JOIN users u ON c.instructor_id = u.user_id 
                           WHERE c.course_id = :course_id");
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        $_SESSION['message'] = "Course not found.";
        $_SESSION['error'] = true;
        header("Location: index.php");
        exit;
    }

    // Check if student is already enrolled
    $isEnrolled = false;
    if ($isLoggedIn && $userRole === 'student') {
        $stmt_enroll = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE user_id = :user_id AND course_id = :course_id");
        $stmt_enroll->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt_enroll->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmt_enroll->execute();
        if ($stmt_enroll->fetch()) {
            $isEnrolled = true;
        }
    }

} catch (PDOException $e) {
    error_log("Error fetching course details or enrollment status: " . $e->getMessage());
    $_SESSION['message'] = "Could not load course details due to a database error.";
    $_SESSION['error'] = true;
    header("Location: index.php");
    exit;
}

// Handle Enrollment POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll']) && $isLoggedIn && $userRole === 'student' && !$isEnrolled) {
    $coursePrice = $course['price'];

    try {
        $conn->beginTransaction();

        // 1. Check wallet balance
        $stmt_wallet = $conn->prepare("SELECT wallet_id, balance FROM wallets WHERE user_id = :user_id FOR UPDATE");
        $stmt_wallet->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt_wallet->execute();
        $wallet = $stmt_wallet->fetch(PDO::FETCH_ASSOC);

        if (!$wallet || $wallet['balance'] < $coursePrice) {
            $_SESSION['message'] = "Insufficient funds to enroll.";
            $_SESSION['error'] = true;
            $conn->rollBack();
            header("Location: view_course_details.php?id=" . $course_id);
            exit;
        }

        // 2. Deduct price from wallet
        $newBalance = $wallet['balance'] - $coursePrice;
        $stmt_update_wallet = $conn->prepare("UPDATE wallets SET balance = :new_balance WHERE wallet_id = :wallet_id");
        $stmt_update_wallet->bindParam(':new_balance', $newBalance);
        $stmt_update_wallet->bindParam(':wallet_id', $wallet['wallet_id'], PDO::PARAM_INT);
        $stmt_update_wallet->execute();

        // 3. Record transaction (using 'debit' type)
        $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, transaction_type, amount, description,wallet_id) 
                                     VALUES (:user_id, 'debit', :amount, :description,:wallet_id)");
        $transDesc = "Enrollment fee for course: " . $course['title'];
        $stmt_trans->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt_trans->bindParam(':amount', $coursePrice); // Store as positive value, type indicates debit
        $stmt_trans->bindParam(':wallet_id', $wallet['wallet_id'], PDO::PARAM_INT);
        $stmt_trans->bindParam(':description', $transDesc);
        $stmt_trans->execute();

        // 4. Add enrollment record
        $stmt_add_enroll = $conn->prepare("INSERT INTO enrollments (user_id, course_id, status) VALUES (:user_id, :course_id, 'active')");
        $stmt_add_enroll->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt_add_enroll->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmt_add_enroll->execute();

        // Commit transaction
        $conn->commit();

        $_SESSION['message'] = "Successfully enrolled in the course!";
        $_SESSION['error'] = false;
        // Redirect to the actual course view page after successful enrollment
        header("Location: /cms/student/view_course.php?id=" . $course_id);
        exit;

    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Enrollment Error: " . $e->getMessage());
        $_SESSION['message'] = "An error occurred during enrollment. Please try again.".$e->getMessage();
        $_SESSION['error'] = true;
        header("Location: view_course_details.php?id=" . $course_id);
        exit;
    }
}

$pageTitle = htmlspecialchars($course['title']);
include __DIR__ . '/../templates/partials/header.php';
?>

<!-- Course Hero Section -->
<section class="course-hero">
    <div class="container">
        <div class="course-hero-content">
            <div class="course-hero-text">
                <div class="course-breadcrumb">
                    <a href="/cms/public/index.php">Course Catalog</a>
                    <span>/</span>
                    <span><?php echo htmlspecialchars($course['title']); ?></span>
                </div>
                <h1 class="course-hero-title"><?php echo htmlspecialchars($course['title']); ?></h1>
                <p class="course-hero-subtitle">Master new skills with expert-led instruction</p>
                <div class="course-meta">
                    <div class="course-instructor">
                        <span class="meta-label">Instructor:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($course['instructor_name'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="course-price">
                        <span class="price-label">Price:</span>
                        <span class="price-value">$<?php echo number_format($course['price'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Course Details Section -->
<section class="course-details">
    <div class="container">
        <div class="course-details-grid">
            <div class="course-image-section">
                <div class="course-image-container">
                    <?php 
                    $image_url = 'https://archive.org/download/placeholder-image//placeholder-image.jpg'; // Default placeholder
                    if (!empty($course["image_path"])) {
                        $relative_image_path = $course["image_path"];
                        if (strpos($relative_image_path, '/uploads/') !== 0) {
                            $relative_image_path = '/uploads/' . ltrim($relative_image_path, '/');
                        }
                        if (file_exists(__DIR__ . '/..' . $relative_image_path)) {
                            $image_url = '/cms' . $relative_image_path;
                        }
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="course-image">
                </div>
            </div>
            
            <div class="course-content-section">
                <div class="course-description-card">
                    <h2 class="section-title">Course Description</h2>
                    <div class="course-description">
                        <?php echo nl2br(htmlspecialchars($course['description'])); ?>
                    </div>
                </div>
                
                <div class="course-enrollment-card">
                    <?php if ($isLoggedIn && $userRole === 'student'): ?>
                        <?php if ($isEnrolled): ?>
                            <div class="enrollment-status enrolled">
                                <div class="status-icon">✓</div>
                                <div class="status-text">
                                    <h3>You're Enrolled!</h3>
                                    <p>You have access to this course content</p>
                                </div>
                            </div>
                            <a href="/cms/student/view_course.php?id=<?php echo $course_id; ?>" class="btn btn-success btn-large ripple-effect">
                                <span>Access Course Content</span>
                                <div class="ripple"></div>
                            </a>
                        <?php else: ?>
                            <div class="enrollment-pricing">
                                <div class="price-display">
                                    <span class="currency">$</span>
                                    <span class="amount"><?php echo number_format($course['price'], 2); ?></span>
                                </div>
                                <p class="price-description">One-time payment for lifetime access</p>
                            </div>
                            <form method="POST" action="view_course_details.php?id=<?php echo $course_id; ?>" class="enrollment-form">
                                <button type="submit" name="enroll" class="btn btn-primary btn-large ripple-effect">
                                    <span>Enroll Now</span>
                                    <div class="ripple"></div>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php elseif (!$isLoggedIn): ?>
                        <div class="enrollment-cta">
                            <h3>Ready to Start Learning?</h3>
                            <p>Create an account or sign in to enroll in this course</p>
                            <div class="cta-buttons">
                                <a href="/cms/public/login.php?redirect=<?php echo urlencode(htmlspecialchars($_SERVER['REQUEST_URI'])); ?>" class="btn btn-primary ripple-effect">
                                    <span>Sign In</span>
                                    <div class="ripple"></div>
                                </a>
                                <a href="/cms/public/register.php" class="btn btn-outline ripple-effect">
                                    <span>Create Account</span>
                                    <div class="ripple"></div>
                                </a>
                            </div>
                        </div>
                    <?php else: // Admin or Instructor ?>
                        <div class="enrollment-info">
                            <p class="info-text">Admins and Instructors cannot enroll in courses.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Back to Catalog -->
<section class="course-navigation">
    <div class="container">
        <a href="/cms/public/index.php" class="back-link">
            <span class="back-icon">←</span>
            <span>Back to Course Catalog</span>
        </a>
    </div>
</section>

<!-- Include modern JavaScript -->
<script src="/cms/public/js/modern-interactions.js"></script>
<script src="/cms/public/js/microinteractions.js"></script>

<style>
/* Course Details Page Styles */
.course-hero {
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-800) 100%);
    color: white;
    padding: var(--space-20) 0 var(--space-16);
    position: relative;
    overflow: hidden;
}

.course-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="100" cy="100" r="50" fill="url(%23a)"/><circle cx="900" cy="200" r="80" fill="url(%23a)"/><circle cx="300" cy="800" r="60" fill="url(%23a)"/></svg>');
    animation: float 30s ease-in-out infinite;
}

.course-hero-content {
    position: relative;
    z-index: 2;
}

.course-breadcrumb {
    font-size: 0.875rem;
    margin-bottom: var(--space-4);
    opacity: 0.9;
}

.course-breadcrumb a {
    color: white;
    text-decoration: none;
    transition: opacity var(--transition-fast);
}

.course-breadcrumb a:hover {
    opacity: 0.8;
}

.course-breadcrumb span {
    margin: 0 var(--space-2);
}

.course-hero-title {
    font-family: var(--font-display);
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: var(--space-4);
    line-height: 1.2;
}

.course-hero-subtitle {
    font-size: 1.25rem;
    margin-bottom: var(--space-8);
    opacity: 0.9;
}

.course-meta {
    display: flex;
    gap: var(--space-8);
    flex-wrap: wrap;
}

.course-instructor,
.course-price {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.meta-label,
.price-label {
    font-weight: 500;
    opacity: 0.8;
}

.meta-value,
.price-value {
    font-weight: 600;
    font-size: 1.125rem;
}

.course-details {
    padding: var(--space-20) 0;
    background: var(--bg-secondary);
}

.course-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-12);
    align-items: start;
}

.course-image-container {
    position: relative;
    overflow: hidden;
    border-radius: var(--radius-2xl);
    box-shadow: var(--shadow-xl);
    aspect-ratio: 16/9;
}

.course-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--transition-slow);
}

.course-image-container:hover .course-image {
    transform: scale(1.05);
}

.course-description-card,
.course-enrollment-card {
    background: white;
    border-radius: var(--radius-2xl);
    padding: var(--space-8);
    box-shadow: var(--shadow-lg);
    margin-bottom: var(--space-6);
}

.section-title {
    font-family: var(--font-display);
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: var(--space-4);
    color: var(--text-primary);
}

.course-description {
    font-size: 1rem;
    line-height: 1.7;
    color: var(--text-secondary);
}

.enrollment-status {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    margin-bottom: var(--space-6);
    padding: var(--space-4);
    border-radius: var(--radius-lg);
}

.enrollment-status.enrolled {
    background: var(--success-50);
    border: 1px solid var(--success-200);
}

.status-icon {
    width: 3rem;
    height: 3rem;
    background: var(--success-500);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
}

.status-text h3 {
    margin: 0 0 var(--space-1) 0;
    color: var(--success-700);
    font-weight: 600;
}

.status-text p {
    margin: 0;
    color: var(--success-600);
    font-size: 0.875rem;
}

.enrollment-pricing {
    text-align: center;
    margin-bottom: var(--space-6);
}

.price-display {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: var(--space-1);
    margin-bottom: var(--space-2);
}

.currency {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-secondary);
}

.amount {
    font-size: 3rem;
    font-weight: 700;
    color: var(--primary-600);
}

.price-description {
    color: var(--text-tertiary);
    font-size: 0.875rem;
}

.enrollment-cta {
    text-align: center;
}

.enrollment-cta h3 {
    margin-bottom: var(--space-2);
    color: var(--text-primary);
}

.enrollment-cta p {
    margin-bottom: var(--space-6);
    color: var(--text-secondary);
}

.cta-buttons {
    display: flex;
    gap: var(--space-4);
    justify-content: center;
    flex-wrap: wrap;
}

.enrollment-info {
    text-align: center;
    padding: var(--space-6);
}

.info-text {
    color: var(--text-tertiary);
    font-style: italic;
}

.btn-large {
    padding: var(--space-4) var(--space-8);
    font-size: 1.125rem;
    width: 100%;
}

.btn-success {
    background: linear-gradient(135deg, var(--success-600) 0%, var(--success-700) 100%);
    color: white;
    box-shadow: var(--shadow-md);
}

.btn-success:hover {
    background: linear-gradient(135deg, var(--success-700) 0%, var(--success-800) 100%);
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    color: var(--primary-600);
    border: 2px solid var(--primary-600);
}

.btn-outline:hover {
    background: var(--primary-600);
    color: white;
}

.course-navigation {
    padding: var(--space-8) 0;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--text-secondary);
    text-decoration: none;
    font-weight: 500;
    transition: all var(--transition-fast);
}

.back-link:hover {
    color: var(--primary-600);
    transform: translateX(-4px);
}

.back-icon {
    font-size: 1.25rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .course-hero-title {
        font-size: 2rem;
    }
    
    .course-details-grid {
        grid-template-columns: 1fr;
        gap: var(--space-8);
    }
    
    .course-meta {
        flex-direction: column;
        gap: var(--space-4);
    }
    
    .cta-buttons {
        flex-direction: column;
    }
}
</style>

<?php
include __DIR__ . '/../templates/partials/footer.php';
?>

