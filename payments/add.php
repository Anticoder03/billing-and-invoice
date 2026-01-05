<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Add Payment';

// Get invoice ID from URL if provided
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

// Get all unpaid/partial invoices
try {
    $stmt = $pdo->query("
        SELECT i.id, i.invoice_number, i.total_amount, c.name as customer_name,
        COALESCE(SUM(p.amount), 0) as paid_amount
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN payments p ON i.id = p.invoice_id
        WHERE i.status != 'paid'
        GROUP BY i.id
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
            
            // Insert payment
            $stmt = $pdo->prepare("
                INSERT INTO payments (invoice_id, payment_date, amount, payment_method, notes) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$invoice_id, $payment_date, $amount, $payment_method, $notes]);
            
            // Update invoice status
            updateInvoiceStatus($pdo, $invoice_id);
            
            $pdo->commit();
            header('Location: index.php?success=added');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error adding payment: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h2>Add Payment</h2>
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
                <select id="invoice_id" name="invoice_id" class="form-control" required onchange="updateBalance()">
                    <option value="">Select Invoice</option>
                    <?php foreach ($invoices as $invoice): ?>
                        <option value="<?php echo $invoice['id']; ?>" 
                                data-total="<?php echo $invoice['total_amount']; ?>"
                                data-paid="<?php echo $invoice['paid_amount']; ?>"
                                <?php echo $invoice['id'] == $invoice_id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($invoice['invoice_number']); ?> - 
                            <?php echo htmlspecialchars($invoice['customer_name']); ?> - 
                            Balance: <?php echo CURRENCY_SYMBOL . number_format($invoice['total_amount'] - $invoice['paid_amount'], 2); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="payment_date">Payment Date *</label>
                <input type="date" id="payment_date" name="payment_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="amount">Amount *</label>
                <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label for="payment_method">Payment Method</label>
                <select id="payment_method" name="payment_method" class="form-control">
                    <option value="">Select Method</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Check">Check</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="UPI">UPI</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" placeholder="Payment notes or reference"></textarea>
        </div>
        
        <div id="balance-info" style="padding: 1rem; background: var(--light-bg); border-radius: 8px; margin-bottom: 1rem; display: none;">
            <p><strong>Invoice Total:</strong> <span id="invoice-total">-</span></p>
            <p><strong>Already Paid:</strong> <span id="already-paid">-</span></p>
            <p><strong>Balance Due:</strong> <span id="balance-due">-</span></p>
        </div>
        
        <button type="submit" class="btn btn-success">Add Payment</button>
    </form>
</div>

<script>
function updateBalance() {
    const select = document.getElementById('invoice_id');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const total = parseFloat(option.dataset.total);
        const paid = parseFloat(option.dataset.paid);
        const balance = total - paid;
        
        document.getElementById('invoice-total').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + total.toFixed(2);
        document.getElementById('already-paid').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + paid.toFixed(2);
        document.getElementById('balance-due').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + balance.toFixed(2);
        document.getElementById('balance-info').style.display = 'block';
        document.getElementById('amount').value = balance.toFixed(2);
    } else {
        document.getElementById('balance-info').style.display = 'none';
    }
}

// Trigger on page load if invoice is pre-selected
if (document.getElementById('invoice_id').value) {
    updateBalance();
}
</script>

<?php include '../includes/footer.php'; ?>
