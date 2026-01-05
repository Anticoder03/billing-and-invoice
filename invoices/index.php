<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Invoices';

// Get all invoices with customer names
try {
    $stmt = $pdo->query("
        SELECT i.*, c.name as customer_name 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id 
        ORDER BY i.created_at DESC
    ");
    $invoices = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching invoices: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="page-header">
    <h2>Invoices</h2>
    <div>
        <a href="add.php" class="btn btn-primary">+ Create Invoice</a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['success'] == 'added') echo 'Invoice created successfully!';
        elseif ($_GET['success'] == 'updated') echo 'Invoice updated successfully!';
        elseif ($_GET['success'] == 'deleted') echo 'Invoice deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <?php if (count($invoices) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Invoice Date</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                        <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                        <td><?php echo date(DISPLAY_DATE_FORMAT, strtotime($invoice['invoice_date'])); ?></td>
                        <td><?php echo $invoice['due_date'] ? date(DISPLAY_DATE_FORMAT, strtotime($invoice['due_date'])) : 'N/A'; ?></td>
                        <td><?php echo CURRENCY_SYMBOL . number_format($invoice['total_amount'], 2); ?></td>
                        <td>
                            <?php
                            $badge_class = $invoice['status'] == 'paid' ? 'badge-success' : ($invoice['status'] == 'partial' ? 'badge-warning' : 'badge-danger');
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($invoice['status']); ?></span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="view.php?id=<?php echo $invoice['id']; ?>" class="btn btn-primary btn-sm">View</a>
                                <a href="edit.php?id=<?php echo $invoice['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete.php?id=<?php echo $invoice['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Are you sure you want to delete this invoice?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="card">
            <p style="text-align: center; color: var(--text-secondary);">No invoices found. <a href="add.php">Create your first invoice</a></p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
