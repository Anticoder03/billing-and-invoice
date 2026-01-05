<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Payments';

// Get all payments with invoice and customer details
try {
    $stmt = $pdo->query("
        SELECT p.*, i.invoice_number, c.name as customer_name, i.total_amount as invoice_total
        FROM payments p 
        LEFT JOIN invoices i ON p.invoice_id = i.id 
        LEFT JOIN customers c ON i.customer_id = c.id 
        ORDER BY p.created_at DESC
    ");
    $payments = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching payments: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="page-header">
    <h2>Payments</h2>
    <div>
        <a href="add.php" class="btn btn-primary">+ Add Payment</a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['success'] == 'added') echo 'Payment added successfully!';
        elseif ($_GET['success'] == 'updated') echo 'Payment updated successfully!';
        elseif ($_GET['success'] == 'deleted') echo 'Payment deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <?php if (count($payments) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Payment Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo $payment['id']; ?></td>
                        <td><?php echo htmlspecialchars($payment['invoice_number']); ?></td>
                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                        <td><?php echo date(DISPLAY_DATE_FORMAT, strtotime($payment['payment_date'])); ?></td>
                        <td><?php echo CURRENCY_SYMBOL . number_format($payment['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></td>
                        <td>
                            <div class="actions">
                                <a href="edit.php?id=<?php echo $payment['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete.php?id=<?php echo $payment['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Are you sure you want to delete this payment?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="card">
            <p style="text-align: center; color: var(--text-secondary);">No payments found. <a href="add.php">Add your first payment</a></p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
