<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole('admin');

$pageTitle = "Adjust Wallet Balance";
include __DIR__ . 
    '/../templates/partials/header.php'; // Header handles messages

$message = ""; // Local message for form errors
$error = false;
$wallet = null;
$user = null;

// Check if wallet ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid wallet ID.";
    $_SESSION['error'] = true;
    header("Location: manage_wallets.php");
    exit;
}

$wallet_id = intval($_GET['id']);

// Fetch wallet and user data
$stmt = $conn->prepare("SELECT w.wallet_id, w.user_id, w.balance, u.username 
                        FROM wallets w 
                        JOIN users u ON w.user_id = u.user_id 
                        WHERE w.wallet_id = :wallet_id");
$stmt->bindParam(':wallet_id', $wallet_id, PDO::PARAM_INT);
$stmt->execute();
$wallet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$wallet) {
    $_SESSION['message'] = "Wallet not found.";
    $_SESSION['error'] = true;
    header("Location: manage_wallets.php");
    exit;
}
$user_id = $wallet['user_id'];
$username = $wallet['username'];
$current_balance = $wallet['balance'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $adjustment_type = $_POST['adjustment_type'] ?? 'add';
    $amount = isset($_POST['amount']) && is_numeric($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $reason = trim($_POST['reason'] ?? 'Manual adjustment by admin');

    if ($amount <= 0) {
        $message = "Adjustment amount must be positive.";
        $error = true;
    } else {
        $new_balance = $current_balance;
        $transaction_type = '';

        if ($adjustment_type === 'add') {
            $new_balance += $amount;
            $transaction_type = 'credit'; // Use consistent transaction type
        } elseif ($adjustment_type === 'subtract') {
            if ($amount > $current_balance) {
                $message = "Cannot subtract more than the current balance.";
                $error = true;
            } else {
                $new_balance -= $amount;
                $transaction_type = 'debit'; // Use consistent transaction type
            }
        } else {
            $message = "Invalid adjustment type.";
            $error = true;
        }

        if (!$error) {
            try {
                $conn->beginTransaction();

                // Update wallet balance
                $stmtUpdate = $conn->prepare("UPDATE wallets SET balance = :new_balance, updated_at = NOW() WHERE wallet_id = :wallet_id");
                $stmtUpdate->bindParam(':new_balance', $new_balance);
                $stmtUpdate->bindParam(':wallet_id', $wallet_id, PDO::PARAM_INT);
                $stmtUpdate->execute();

                // Record the transaction using the correct 'type' column from schema v4
                $stmtTransaction = $conn->prepare("INSERT INTO transactions (user_id, transaction_type, amount, description, related_entity_id, related_entity_type,wallet_id) VALUES (:user_id, :transaction_type, :amount, :description, :related_id, :related_type,:wallet_id)");
                $related_id = $wallet_id; 
                $related_type = 'manual_adjustment';
                $stmtTransaction->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmtTransaction->bindParam(':transaction_type', $transaction_type); // Changed from 'type'
                $stmtTransaction->bindParam(':amount', $amount);
                $stmtTransaction->bindParam(':description', $reason);
                $stmtTransaction->bindParam(':related_id', $related_id, PDO::PARAM_INT);
                $stmtTransaction->bindParam(':related_type', $related_type);
                $stmtTransaction->bindParam(':wallet_id', $wallet_id);
                $stmtTransaction->execute();

                $conn->commit();
                $_SESSION['message'] = "Wallet balance adjusted successfully.";
                $_SESSION['error'] = false;
                header("Location: manage_wallets.php");
                exit;

            } catch (PDOException $e) {
                $conn->rollBack();
                error_log("Wallet adjustment error: " . $e->getMessage());
                $message = "Failed to adjust balance due to a database error.".$e->getMessage();
                $error = true;
            }
        }
    }
}

?>

<div class="form-wrapper">
    <h2 class="text-center mb-3">Adjust Wallet Balance</h2>
    <p class="text-center lead">User: <span class="text-primary"><?php echo htmlspecialchars($username); ?></span> (ID: <?php echo $user_id; ?>)</p>
    <p class="text-center mb-4">Current Balance: <strong class="text-success">$<?php echo htmlspecialchars(number_format($current_balance, 2)); ?></strong></p>

    <?php if (!empty($message)): ?>
        <div class="alert <?php echo $error ? 'alert-danger' : 'alert-info'; ?>" role="alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="edit_wallet.php?id=<?php echo $wallet_id; ?>" method="post">
        <div class="form-group mb-3">
            <label for="adjustment_type" class="form-label">Adjustment Type:</label>
            <select id="adjustment_type" name="adjustment_type" class="form-control">
                <option value="add">Add Funds (Credit)</option>
                <option value="subtract">Subtract Funds (Debit)</option>
            </select>
        </div>
        <div class="form-group mb-3">
            <label for="amount" class="form-label">Amount ($):</label>
            <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0.01" required>
        </div>
        <div class="form-group mb-3">
            <label for="reason" class="form-label">Reason:</label>
            <input type="text" id="reason" name="reason" class="form-control" value="Manual adjustment by admin" required>
        </div>
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="manage_wallets.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Adjust Balance</button>
        </div>
    </form>
</div>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

