<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "System Settings";
include __DIR__ . 
    '/../templates/partials/header.php';

$message = "";
$error = false;

// Fetch current settings
$settings = [];
$stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Define expected settings and their validation/type
    $expected_settings = [
        'site_name' => 'string',
        'default_enrollment_cost' => 'float',
        // Add more settings here as needed
    ];

    try {
        $conn->beginTransaction();

        foreach ($expected_settings as $key => $type) {
            if (isset($_POST[$key])) {
                $value = trim($_POST[$key]);

                // Basic validation based on type
                if ($type === 'float') {
                    if (!is_numeric($value)) {
                        throw new Exception("Invalid value provided for " . $key . ". Must be a number.");
                    }
                    $value = floatval($value);
                } elseif ($type === 'int') {
                    if (!filter_var($value, FILTER_VALIDATE_INT)) {
                        throw new Exception("Invalid value provided for " . $key . ". Must be an integer.");
                    }
                    $value = intval($value);
                }
                // Add more type checks if needed (e.g., email, url)

                // Update or Insert setting
                $stmtUpdate = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) 
                                           ON DUPLICATE KEY UPDATE setting_value = :value");
                $stmtUpdate->bindParam(':key', $key);
                $stmtUpdate->bindParam(':value', $value);
                $stmtUpdate->execute();

                // Update the local settings array for immediate display
                $settings[$key] = $value;
            }
        }

        $conn->commit();
        $message = "Settings updated successfully.";

    } catch (Exception $e) {
        $conn->rollBack();
        $message = "Error updating settings: " . $e->getMessage();
        $error = true;
    }
}

?>
<div class="container mt-4">

<h2>System Settings</h2>

<?php if (!empty($message)): ?>
    <div class="message <?php echo $error ? 'error' : 'success'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>
<form action="system_settings.php" method="post" class="form-container">
    <div class="form-group">
        <label for="site_name">Site Name:</label>
        <input type="text" id="site_name" name="site_name" style="
    padding: 10px;
" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'My CMS'); ?>" required>
    </div>

    <div class="form-group">
        <label for="default_enrollment_cost">Default Enrollment Cost ($):</label>
        <input type="number" style="
    padding: 10px;
" id="default_enrollment_cost" name="default_enrollment_cost" step="0.01" min="0" value="<?php echo htmlspecialchars($settings['default_enrollment_cost'] ?? '10.00'); ?>" required>
        <small>The default price for enrolling in a course if not specified otherwise.</small>
    </div>

    <!-- Add more setting fields here -->

    <button type="submit" class="button" style="
    padding: 10px;
    background: #4a90e2;
    color: white;
    border: none;
">Save Settings</button>
</form>
</div>
<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

