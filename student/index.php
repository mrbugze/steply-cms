<?php
require_once __DIR__ . 
    '/../config/db.php'; // Corrected
require_once __DIR__ . 
    '/../src/Auth/Auth.php'; // Corrected

$auth = new Auth($conn);
$auth->checkRole('student'); // Corrected: Use single quotes, no newlines

$pageTitle = "Student Dashboard";
include __DIR__ . 
    '/../templates/partials/header.php'; // Corrected
?>

<h2>Student Dashboard</h2>
<p>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); // Corrected ?>!</p>

<p>Here you can view your enrolled courses and progress.</p>

<ul>
    <li><a href="my_courses.php">My Courses</a></li>
    <li><a href="wallet.php">My Wallet</a></li>
    <!-- Add more student-specific links here -->
</ul>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php'; // Corrected
?>

