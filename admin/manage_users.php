<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Manage Users";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages now

// Fetch all users
$stmt = $conn->prepare("SELECT user_id, username, email, role, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="container mt-4">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Users</h2>
    <a href="add_user.php" class="btn btn-primary">Add New User</a>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="thead-light">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No users found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($user['role'])); // Capitalize role ?></td>
                        <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($user['created_at']))); ?></td>
                        <td class="actions">
                            <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                            <?php if ($user['user_id'] != $_SESSION['user_id']): // Prevent admin from deleting themselves ?>
                                <a href="delete_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                            <?php endif; ?>
                            <?php if ($user['role'] === 'student'): ?>
                                <a href="promote_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-info" onclick="return confirm('Are you sure you want to promote this user to Instructor?');">Promote</a>
                            <?php elseif ($user['role'] === 'instructor'): ?>
                                 <a href="demote_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to demote this user to Student?');">Demote</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

