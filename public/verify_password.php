<?php
require_once __DIR__ . 
'/../config/db.php';

$username = 
    "admin";
$password_to_verify = 
    "password123";

// Fetch the user
$stmt = $conn->prepare(
"SELECT password_hash FROM users WHERE username = ?"
);
if (!$stmt) {
    die(
"Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt->bind_param(
"s"
, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    $stored_hash = $user["password_hash"];

    echo "Username: " . $username . "\n";
echo "Stored Hash: " . $stored_hash . "\n";
echo "Password to Verify: " . $password_to_verify . "\n";

    // Verify the password
    if (password_verify($password_to_verify, $stored_hash)) {
        echo "Password verification SUCCESSFUL!\n";
    } else {
        echo "Password verification FAILED!\n";
        // Optionally, generate hash for the test password to compare
        $generated_hash = password_hash($password_to_verify, PASSWORD_BCRYPT);
        echo "Generated hash for '" . $password_to_verify . "': " . $generated_hash . "\n";
    }
} else {
    echo "User '" . $username . "' not found.\n";
}

$stmt->close();
$conn->close();

?>

