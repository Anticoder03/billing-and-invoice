<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Bills';

// Get all bills with customer and invoice details
try {
    $stmt = $pdo->query("
        SELECT b.*, c.name as customer_name, i.invoice_number
        FROM bills b 
        LEFT JOIN customers c ON b.customer_id = c.id 
        LEFT JOIN invoices i ON b.invoice_id = i.id 
        ORDER BY b.created_at DESC
    ");
    $bills = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching bills: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="page-header">
    <h2>Bills</h2>
    <div>
        <a href="create.php" class="btn btn-primary">+ Generate Bill</a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['success'] == 'created') echo 'Bill generated successfully!';
        elseif ($_GET['success'] == 'deleted') echo 'Bill deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <?php if (count($bills) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Bill #</th>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Bill Date</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bills as $bill): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                        <td><?php echo htmlspecialchars($bill['invoice_number']); ?></td>
                        <td><?php echo htmlspecialchars($bill['customer_name']); ?></td>
                        <td><?php echo date(DISPLAY_DATE_FORMAT, strtotime($bill['bill_date'])); ?></td>
                        <td><?php echo 'Rs.' . number_format($bill['total_amount'], 2); ?></td>
                        <td>
                            <div class="actions">
                                <a href="view.php?id=<?php echo $bill['id']; ?>" class="btn btn-success btn-sm" target="_blank">View/Download Bill</a>
                                <a href="delete.php?id=<?php echo $bill['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this bill?');">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="card">
            <p style="text-align: center; color: var(--text-secondary);">No bills generated yet. <a href="create.php">Generate your first bill</a></p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
