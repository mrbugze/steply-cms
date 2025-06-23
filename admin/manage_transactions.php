<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "View Transactions";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages

$user_id_filter = null;
$username_filter = "All Users";
$transactions = [];

// Check if a specific user ID is provided to filter transactions
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id_filter = intval($_GET['user_id']);

    // Fetch username for context
    $stmtUser = $conn->prepare("SELECT username FROM users WHERE user_id = :user_id");
    $stmtUser->bindParam(':user_id', $user_id_filter, PDO::PARAM_INT);
    $stmtUser->execute();
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($userData) {
        $username_filter = $userData['username'];
    } else {
        $_SESSION['message'] = "Invalid user ID specified for filtering.";
        $_SESSION['error'] = true;
        header("Location: manage_transactions.php");
        exit;
    }

    // Fetch transactions for the specific user
    $stmt = $conn->prepare("SELECT t.transaction_id, t.user_id, u.username, t.transaction_type, t.amount, t.description, t.related_entity_id, t.related_entity_type, t.transaction_date 
                            FROM transactions t 
                            JOIN users u ON t.user_id = u.user_id 
                            WHERE t.user_id = :user_id 
                            ORDER BY t.transaction_date DESC"); // Use transaction_type
    $stmt->bindParam(':user_id', $user_id_filter, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch all transactions if no specific user is selected
    $stmt = $conn->prepare("SELECT t.transaction_id, t.user_id, u.username, t.transaction_type, t.amount, t.description, t.related_entity_id, t.related_entity_type, t.transaction_date 
                            FROM transactions t 
                            JOIN users u ON t.user_id = u.user_id 
                            ORDER BY t.transaction_date DESC"); // Use transaction_type
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<div class="container mt-4">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>View Transactions <?php echo $user_id_filter ? 'for User: <span class="text-primary">' . htmlspecialchars($username_filter) . '</span> (ID: ' . $user_id_filter . ')' : '(All Users)'; ?></h2>
    <div>
        <?php if ($user_id_filter): ?>
            <a href="manage_wallets.php" class="btn btn-secondary">Back to Wallets</a>
            <a href="manage_transactions.php" class="btn btn-info">View All Transactions</a>
        <?php else: ?>
             <a href="manage_wallets.php" class="btn btn-secondary">Back to Wallets</a>
        <?php endif; ?>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="thead-light">
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Related Item</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted">No transactions found<?php echo $user_id_filter ? ' for this user' : ''; ?>.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['username']); ?> <span class="text-muted">(ID: <?php echo $transaction['user_id']; ?>)</span></td>
                        <td>
                            <span class="badge bg-<?php echo $transaction['transaction_type'] === 'credit' ? 'success' : ($transaction['transaction_type'] === 'debit' ? 'danger' : 'secondary'); ?>">
                                <?php echo htmlspecialchars(ucfirst($transaction['transaction_type'])); ?>
                            </span>
                        </td>
                        <td class="<?php echo $transaction['transaction_type'] === 'credit' ? 'text-success' : ($transaction['transaction_type'] === 'debit' ? 'text-danger' : ''); ?>">
                            <?php echo ($transaction['transaction_type'] === 'credit' ? '+' : ($transaction['transaction_type'] === 'debit' ? '-' : '')); ?>$
                            <?php echo htmlspecialchars(number_format($transaction['amount'], 2)); ?>
                        </td>
                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['related_entity_type'] . ($transaction['related_entity_id'] ? ' (ID: ' . $transaction['related_entity_id'] . ')' : '')); ?></td>
                        <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($transaction['transaction_date']))); ?></td>
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

