<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Edit Payment';

// Get payment ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Get payment data
try {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        header('Location: index.php');
        exit;
    }
    
    // Get all invoices
    $stmt = $pdo->query("
        SELECT i.id, i.invoice_number, c.name as customer_name
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        ORDER BY i.invoice_date DESC
    ");
    $invoices = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Function to update invoice status
function updateInvoiceStatus($pdo, $invoice_id) {
    // Get total invoice amount
    $stmt = $pdo->prepare("SELECT total_amount FROM invoices WHERE id = ?");
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();
    
    // Get total paid
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE invoice_id = ?");
    $stmt->execute([$invoice_id]);
    $total_paid = $stmt->fetch()['total_paid'];
    
    // Determine status
    $status = 'unpaid';
    if ($total_paid >= $invoice['total_amount']) {
        $status = 'paid';
    } elseif ($total_paid > 0) {
        $status = 'partial';
    }
    
    // Update status
    $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
    $stmt->execute([$status, $invoice_id]);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $invoice_id = (int)$_POST['invoice_id'];
    $payment_date = $_POST['payment_date'];
    $amount = floatval($_POST['amount']);
    $payment_method = trim($_POST['payment_method']);
    $notes = trim($_POST['notes']);
    
    // Validation
    if ($invoice_id <= 0) {
        $error = "Please select an invoice.";
    } elseif (empty($payment_date)) {
        $error = "Payment date is required.";
    } elseif ($amount <= 0) {
        $error = "Payment amount must be greater than zero.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $old_invoice_id = $payment['invoice_id'];
            
            // Update payment
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET invoice_id = ?, payment_date = ?, amount = ?, payment_method = ?, notes = ? 
                WHERE id = ?
            ");
            $stmt->execute([$invoice_id, $payment_date, $amount, $payment_method, $notes, $id]);
            
            // Update old invoice status
            updateInvoiceStatus($pdo, $old_invoice_id);
            
            // Update new invoice status if changed
            if ($invoice_id != $old_invoice_id) {
                updateInvoiceStatus($pdo, $invoice_id);
            }
            
            $pdo->commit();
            header('Location: index.php?success=updated');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error updating payment: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h2>Edit Payment</h2>
    <div>
        <a href="index.php" class="btn btn-primary">Back to Payments</a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="invoice_id">Invoice *</label>
                <select id="invoice_id" name="invoice_id" class="form-control" required>
                    <option value="">Select Invoice</option>
                    <?php foreach ($invoices as $invoice): ?>
                        <option value="<?php echo $invoice['id']; ?>" <?php echo $invoice['id'] == $payment['invoice_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($invoice['invoice_number']); ?> - <?php echo htmlspecialchars($invoice['customer_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="payment_date">Payment Date *</label>
                <input type="date" id="payment_date" name="payment_date" class="form-control" required value="<?php echo $payment['payment_date']; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="amount">Amount *</label>
                <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0.01" required value="<?php echo $payment['amount']; ?>">
            </div>
            <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method" class="form-control">
                    <option value="">Select Method</option>
                    <option value="Cash" <?php echo $payment['payment_method'] == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="Bank Transfer" <?php echo $payment['payment_method'] == 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="Check" <?php echo $payment['payment_method'] == 'Check' ? 'selected' : ''; ?>>Check</option>
                    <option value="Credit Card" <?php echo $payment['payment_method'] == 'Credit Card' ? 'selected' : ''; ?>>Credit Card</option>
                    <option value="UPI" <?php echo $payment['payment_method'] == 'UPI' ? 'selected' : ''; ?>>UPI</option>
                    <option value="Other" <?php echo $payment['payment_method'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" placeholder="Payment notes or reference"><?php echo htmlspecialchars($payment['notes'] ?? ''); ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-success">Update Payment</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
