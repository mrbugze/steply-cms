<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);

require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Manage Wallets";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages

// Fetch all wallets with user info
$stmt = $conn->prepare("SELECT w.wallet_id, w.user_id, u.username, u.email, w.balance, w.updated_at 
                           FROM wallets w 
                           JOIN users u ON w.user_id = u.user_id 
                           ORDER BY w.updated_at DESC");
$stmt->execute();
$wallets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="container mt-4">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage User Wallets</h2>
    <!-- Add button for creating wallets manually if needed, though they are auto-created -->
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="thead-light">
            <tr>
                <th>Wallet ID</th>
                <th>User ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Balance</th>
                <th>Last Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($wallets)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">No wallets found. Wallets are created automatically upon user registration.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($wallets as $wallet): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($wallet['wallet_id']); ?></td>
                        <td><?php echo htmlspecialchars($wallet['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($wallet['username']); ?></td>
                        <td><?php echo htmlspecialchars($wallet['email']); ?></td>
                        <td>$<?php echo htmlspecialchars(number_format($wallet['balance'], 2)); ?></td>
                        <td><?php echo htmlspecialchars(date("Y-m-d H:i", strtotime($wallet["updated_at"]))); ?></td>
                        <td class="actions">
                            <a href="edit_wallet.php?id=<?php echo $wallet['wallet_id']; ?>" class="btn btn-sm btn-secondary">Adjust</a> 
                            <a href="manage_transactions.php?user_id=<?php echo $wallet['user_id']; ?>" class="btn btn-sm btn-info">History</a>
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

