<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php';

$auth = new Auth($conn);
$auth->checkRole(['student']);

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$deposit_message = ''; // Use session for messages across redirects if needed
$deposit_error = '';

// Handle deposit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit_amount'])) {
    $amount = filter_input(INPUT_POST, 'deposit_amount', FILTER_VALIDATE_FLOAT);

    if ($amount === false || $amount <= 0) {
        $deposit_error = 'Invalid deposit amount. Please enter a positive number.';
    } else {
        try {
            $conn->beginTransaction();

            // Update wallet balance
            $stmt_update_wallet = $conn->prepare("UPDATE wallets SET balance = balance + :amount WHERE user_id = :user_id");
            $stmt_update_wallet->bindParam(':amount', $amount);
            $stmt_update_wallet->bindParam(':user_id', $user_id);
            $stmt_update_wallet->execute();

            // Insert transaction record
            $stmt_insert_transaction = $conn->prepare("INSERT INTO transactions (user_id, transaction_type, amount, description) VALUES (:user_id, 'credit', :amount, :description)"); // Use 'credit' type
            $description = 'Wallet deposit';
            $stmt_insert_transaction->bindParam(':user_id', $user_id);
            $stmt_insert_transaction->bindParam(':amount', $amount);
            $stmt_insert_transaction->bindParam(':description', $description);
            $stmt_insert_transaction->execute();

            $conn->commit();
            $deposit_message = 'Deposit successful!';

        } catch (PDOException $e) {
            $conn->rollBack();
            error_log('Deposit failed for user ' . $user_id . ': ' . $e->getMessage()); 
            $deposit_error = 'Deposit failed due to a system error. Please try again later.'; 
        }
    }
}

// Fetch wallet balance (re-fetch after potential deposit)
$stmt_wallet = $conn->prepare("SELECT balance FROM wallets WHERE user_id = :user_id");
$stmt_wallet->bindParam(':user_id', $user_id);
$stmt_wallet->execute();
$wallet = $stmt_wallet->fetch(PDO::FETCH_ASSOC);
$balance = $wallet ? $wallet['balance'] : 0.00;

// Fetch transaction history (re-fetch after potential deposit)
$stmt_transactions = $conn->prepare("SELECT transaction_type, amount, description, transaction_date FROM transactions WHERE user_id = :user_id ORDER BY transaction_date DESC");
$stmt_transactions->bindParam(':user_id', $user_id);
$stmt_transactions->execute();
$transactions = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "My Wallet";
// Use the main header for consistency
include __DIR__ . '/../templates/partials/header.php'; 
?>

<div class="container mt-4">
    <h1 class="mb-4 text-center">My Wallet</h1>

    <?php // Display deposit messages here if not using session messages in header ?>
    <?php if ($deposit_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($deposit_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($deposit_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($deposit_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-body text-center">
            <h3 class="card-title text-muted">Current Balance</h3>
            <p class="card-text display-4 text-success">$<?php echo htmlspecialchars(number_format($balance, 2)); ?></p>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <h2 class="h5 mb-0">Deposit Funds</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="wallet.php" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label for="deposit_amount" class="form-label">Amount ($):</label>
                    <input type="number" id="deposit_amount" name="deposit_amount" step="0.01" min="0.01" required class="form-control" placeholder="Enter amount">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Deposit</button>
                </div>
            </form>
            <div class="form-text mt-2">Enter the amount you wish to add to your wallet.</div>
        </div>
    </div>

    <div class="card shadow-sm">
         <div class="card-header bg-light">
             <h2 class="h5 mb-0">Transaction History</h2>
         </div>
         <div class="card-body p-0"> <?php // Remove padding for table to fill ?>
            <?php if (count($transactions) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0"> <?php // mb-0 to remove bottom margin ?>
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date("Y-m-d H:i", strtotime($transaction["transaction_date"]))); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['transaction_type'] === 'credit' ? 'success' : ($transaction['transaction_type'] === 'debit' ? 'danger' : 'secondary'); ?>">
                                            <?php echo htmlspecialchars(ucfirst($transaction['transaction_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td class="text-end <?php echo $transaction['transaction_type'] === 'credit' ? 'text-success' : ($transaction['transaction_type'] === 'debit' ? 'text-danger' : ''); ?>">
                                        <?php echo ($transaction['transaction_type'] === 'credit' ? '+' : '-') . '$' . htmlspecialchars(number_format(abs($transaction['amount']), 2)); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted p-3">You have no transaction history yet.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php 
// Use the main footer for consistency
include __DIR__ . '/../templates/partials/footer.php'; 
?>

