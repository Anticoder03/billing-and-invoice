<?php
require_once 'config/config.php';
require_once 'config/database.php';

$page_title = 'Dashboard';

// Get statistics
try {
    $stats = [];
    
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
    $stats['customers'] = $stmt->fetch()['count'];
    
    // Total invoices
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM invoices");
    $stats['invoices'] = $stmt->fetch()['count'];
    
    // Total payments
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments");
    $stats['payments'] = $stmt->fetch()['total'] ?? 0;
    
    // Pending invoices
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM invoices WHERE status != 'paid'");
    $stats['pending'] = $stmt->fetch()['count'];
    
    // Recent invoices
    $stmt = $pdo->query("
        SELECT i.*, c.name as customer_name 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id 
        ORDER BY i.created_at DESC 
        LIMIT 5
    ");
    $recent_invoices = $stmt->fetchAll();
    
    // Recent payments
    $stmt = $pdo->query("
        SELECT p.*, i.invoice_number, c.name as customer_name 
        FROM payments p 
        LEFT JOIN invoices i ON p.invoice_id = i.id 
        LEFT JOIN customers c ON i.customer_id = c.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $recent_payments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Error fetching data: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="page-header">
    <h2>Dashboard</h2>
    <div>
        <a href="invoices/add.php" class="btn btn-primary">+ New Invoice</a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Customers</h3>
        <div class="stat-value"><?php echo number_format($stats['customers']); ?></div>
    </div>
    <div class="stat-card">
        <h3>Total Invoices</h3>
        <div class="stat-value"><?php echo number_format($stats['invoices']); ?></div>
    </div>
    <div class="stat-card">
        <h3>Total Payments</h3>
        <div class="stat-value"><?php echo CURRENCY_SYMBOL . number_format($stats['payments'], 2); ?></div>
    </div>
    <div class="stat-card">
        <h3>Pending Invoices</h3>
        <div class="stat-value"><?php echo number_format($stats['pending']); ?></div>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom: 1rem;">Recent Invoices</h3>
    <?php if (count($recent_invoices) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_invoices as $invoice): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                            <td><?php echo date(DISPLAY_DATE_FORMAT, strtotime($invoice['invoice_date'])); ?></td>
                            <td><?php echo CURRENCY_SYMBOL . number_format($invoice['total_amount'], 2); ?></td>
                            <td>
                                <?php
                                $badge_class = $invoice['status'] == 'paid' ? 'badge-success' : ($invoice['status'] == 'partial' ? 'badge-warning' : 'badge-danger');
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($invoice['status']); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary);">No invoices yet. <a href="invoices/add.php">Create your first invoice</a></p>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-bottom: 1rem;">Recent Payments</h3>
    <?php if (count($recent_payments) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_payments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['invoice_number']); ?></td>
                            <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                            <td><?php echo date(DISPLAY_DATE_FORMAT, strtotime($payment['payment_date'])); ?></td>
                            <td><?php echo CURRENCY_SYMBOL . number_format($payment['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary);">No payments yet.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
