<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic check if user is logged in and is an instructor
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "instructor") {
    // Redirect to login page if not logged in or not an instructor
    header("Location: ../public/login.php?error=Access denied. Please login as an instructor.");
    exit;
}

$username = $_SESSION["username"] ?? "Instructor";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - <?php echo htmlspecialchars($pageTitle ?? "CMS"); ?></title>
    <link rel="stylesheet" href="../public/css/style.css"> 
</head>
<body>
    <header class="site-header">
                <div class="brand"><a href="/cms/instructor/index.php">Instructor Dashboard - <?php echo $site_name; ?></a></div>
        <nav class="navbar"  class="nav-links">
            <ul>
                <li><a href="index.php">Dashboard Home</a></li>
                <li><a href="manage_courses.php">My Courses</a></li>
                <li><a href="manage_students.php">My Students</a></li> 
                <li><span>Welcome, <?php echo htmlspecialchars($username); ?>!</span></li>
                <li><a href="../public/logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>
    <main class="container">

