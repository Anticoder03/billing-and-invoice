<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'View Invoice';

// Get invoice ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Get invoice data
try {
    $stmt = $pdo->prepare("
        SELECT i.*, c.name as customer_name, c.email, c.phone, c.address 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id 
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        header('Location: index.php');
        exit;
    }
    
    // Get invoice items
    $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();
    
    // Get payments
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$id]);
    $payments = $stmt->fetchAll();
    
    // Calculate total paid
    $total_paid = 0;
    foreach ($payments as $payment) {
        $total_paid += $payment['amount'];
    }
    $balance = $invoice['total_amount'] - $total_paid;
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

include '../includes/header.php';
?>

<!-- Business Header with Logo -->
<div style="background: linear-gradient(135deg, #1e40af 0%, #0891b2 100%); padding: 2rem; margin: -2rem -2rem 2rem -2rem; color: white; border-radius: 0 0 12px 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <div style="display: flex; align-items: center; gap: 1.5rem; max-width: 1200px; margin: 0 auto;">
        <?php if (file_exists(LOGO_PATH)): ?>
            <img src="<?php echo BASE_URL; ?>logo.png" alt="<?php echo BUSINESS_NAME; ?>" style="height: 80px; width: auto; background: white; padding: 8px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
        <?php endif; ?>
        <div style="flex: 1;">
            <h1 style="margin: 0 0 0.5rem 0; font-size: 2rem; color: white;"><?php echo BUSINESS_NAME; ?></h1>
            <p style="margin: 0; opacity: 0.95; font-size: 0.9rem; line-height: 1.6;">
                <?php echo nl2br(BUSINESS_ADDRESS); ?><br>
                <strong>Phone:</strong> <?php echo BUSINESS_PHONE; ?> | 
                <strong>Email:</strong> <?php echo BUSINESS_EMAIL; ?>
            </p>
        </div>
    </div>
</div>

<div class="page-header">
    <h2>Invoice Details</h2>
    <div>
        <a href="index.php" class="btn btn-primary">Back to Invoices</a>
        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">Edit Invoice</a>
        <a href="../bills/create.php?invoice_id=<?php echo $id; ?>" class="btn btn-success">Generate Bill</a>
    </div>
</div>

<div class="card">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div>
            <h3>Invoice Information</h3>
            <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
            <p><strong>Invoice Date:</strong> <?php echo date(DISPLAY_DATE_FORMAT, strtotime($invoice['invoice_date'])); ?></p>
            <p><strong>Due Date:</strong> <?php echo $invoice['due_date'] ? date(DISPLAY_DATE_FORMAT, strtotime($invoice['due_date'])) : 'N/A'; ?></p>
            <p><strong>Status:</strong> 
                <?php
                $badge_class = $invoice['status'] == 'paid' ? 'badge-success' : ($invoice['status'] == 'partial' ? 'badge-warning' : 'badge-danger');
                ?>
                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($invoice['status']); ?></span>
            </p>
        </div>
        <div>
            <h3>Customer Information</h3>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($invoice['customer_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($invoice['email'] ?? 'N/A'); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($invoice['phone'] ?? 'N/A'); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($invoice['address'] ?? 'N/A'); ?></p>
        </div>
    </div>
    
    <h3>Invoice Items</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['description']); ?></td>
                        <td><?php echo number_format($item['quantity'], 2); ?></td>
                        <td><?php echo 'Rs.' . number_format($item['unit_price'], 2); ?></td>
                        <td><?php echo 'Rs.' . number_format($item['total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="font-weight: bold; background: var(--light-bg);">
                    <td colspan="3" style="text-align: right;">Total Amount:</td>
                    <td><?php echo 'Rs.' . number_format($invoice['total_amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <?php if ($invoice['notes']): ?>
        <div style="margin-top: 1.5rem;">
            <h4>Notes:</h4>
            <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Payment Information</h3>
    <div style="margin-bottom: 1rem;">
        <p><strong>Total Amount:</strong> <?php echo 'Rs.' . number_format($invoice['total_amount'], 2); ?></p>
        <p><strong>Total Paid:</strong> <?php echo 'Rs.' . number_format($total_paid, 2); ?></p>
        <p><strong>Balance:</strong> <?php echo 'Rs.' . number_format($balance, 2); ?></p>
    </div>
    
    <?php if (count($payments) > 0): ?>
        <h4>Payment History</h4>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo date(DISPLAY_DATE_FORMAT, strtotime($payment['payment_date'])); ?></td>
                            <td><?php echo 'Rs.' . number_format($payment['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($payment['notes'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary);">No payments recorded yet.</p>
    <?php endif; ?>
    
    <div style="margin-top: 1rem;">
        <a href="../payments/add.php?invoice_id=<?php echo $id; ?>" class="btn btn-success">Add Payment</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
