<?php
// Database Configuration for XAMPP using PDO
define("DB_HOST", "localhost");
define("DB_USER", "root"); // Default XAMPP username
define("DB_PASS", ""); // Default XAMPP password is empty
define("DB_NAME", "cms_db"); // Choose a database name
define("DB_CHARSET", "utf8mb4");

// Data Source Name (DSN)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Create PDO instance
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Note: Database creation check is less straightforward with PDO and often handled outside the connection script.
    // Assuming the database cms_db already exists based on previous checks.

} catch (\PDOException $e) {
    // Log the error or handle it appropriately for production
    // For development, we can just die and show the error.
    error_log("PDO Connection Error: " . $e->getMessage()); // Log error
    die("Database connection failed. Please check logs or contact support."); // User-friendly message
    // throw new \PDOException($e->getMessage(), (int)$e->getCode()); // Or re-throw exception
}

// Function to close the connection (not strictly necessary with PDO as it closes when script ends)
function close_db(&$connection) {
    $connection = null;
}

// Start session for user management
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
