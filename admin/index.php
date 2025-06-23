<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Admin Dashboard";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header already updated
?>
<style>.list-group {
  padding: 10px;
  background-color: #f8f9fa;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
  overflow-x: auto;
  white-space: nowrap;
}

.list-group ul {
  display: inline-flex;
  list-style: none;
  padding: 0;
  margin: 0;
  gap: 10px;
}

.list-group-item {
  padding: 10px 20px;
  border-radius: 8px;
  background-color: #ffffff;
  transition: background-color 0.3s ease, transform 0.2s ease;
  cursor: pointer;
  border: 1px solid black;
}

.list-group-item a {
  text-decoration: none;
  color: #333;
  font-weight: 500;
}

.list-group-item:hover {
  background-color: #007bff;
  transform: translateY(-2px);
}

.list-group-item:hover a {
  color: #fff;
}
</style>
<div class="container mt-4"><h2 class="mb-4">Admin Dashboard</h2>
<p class="lead mb-4">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</p>
<style>.list-group ul li{    background: var(--dark-bg);
    color: #f6f6f6;
    padding: 10px;
    border-right: #ccc 1px solid;}
    .list-group ul li a{
        text-decoration: none;
        color:black;
    }</style>
<div class="list-group">
    <ul style="display:inline-flex;list-style:none;">
        <li  class="list-group-item list-group-item-action"><a href="manage_users.php">Manage Users</a></li>
        <li   class="list-group-item list-group-item-action"><a href="manage_courses.php">Manage Courses</a></li>
        <li   class="list-group-item list-group-item-action"><a href="manage_steps.php">Manage Steps</a></li>
        <li  class="list-group-item list-group-item-action"><a href="manage_tasks.php">Manage Tasks</a></li>
        <li  class="list-group-item list-group-item-action"><a href="manage_wallets.php">Manage Wallets</a></li>
        <li  class="list-group-item list-group-item-action"><a href="manage_transactions.php">View Transactions</a></li>
        <li  class="list-group-item list-group-item-action"><a href="system_settings.php">System Settings</a></li></ul>
</div>
</div>
<?php
include __DIR__ . 
    '/../templates/partials/footer.php'; // Footer needs update later
?>

