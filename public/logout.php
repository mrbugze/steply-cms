<?php
require_once __DIR__ . 
    '/../config/db.php'; // Session started in db.php
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->logout();

// Redirect to login page after logout
header("Location: login.php?message=You+have+been+logged+out.");
exit;
?>
