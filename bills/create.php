<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Generate Bill';

// Get invoice ID from URL if provided
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

// Get all paid or partial invoices
try {
    $stmt = $pdo->query("
        SELECT i.id, i.invoice_number, c.name as customer_name, i.total_amount
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        ORDER BY i.invoice_date DESC
    ");
    $invoices = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $invoice_id = (int)$_POST['invoice_id'];
    
    if ($invoice_id <= 0) {
        $error = "Please select an invoice.";
    } else {
        try {
            // Get invoice details
            $stmt = $pdo->prepare("
                SELECT i.*, c.name as customer_name, c.email, c.phone, c.address
                FROM invoices i
                LEFT JOIN customers c ON i.customer_id = c.id
                WHERE i.id = ?
            ");
            $stmt->execute([$invoice_id]);
            $invoice = $stmt->fetch();
            
            if (!$invoice) {
                $error = "Invoice not found.";
            } else {
                // Generate bill number
                $stmt = $pdo->query("SELECT MAX(id) as max_id FROM bills");
                $max_id = $stmt->fetch()['max_id'] ?? 0;
                $bill_number = 'BILL-' . str_pad($max_id + 1, 6, '0', STR_PAD_LEFT);
                
                // Insert bill record (no PDF generation on server)
                $stmt = $pdo->prepare("
                    INSERT INTO bills (invoice_id, customer_id, bill_number, bill_date, total_amount, pdf_path) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $invoice_id,
                    $invoice['customer_id'],
                    $bill_number,
                    date('Y-m-d'),
                    $invoice['total_amount'],
                    '' // No PDF path needed - generated client-side
                ]);
                $bill_id = $pdo->lastInsertId();
                
                // Redirect to view page where PDF can be downloaded
                header('Location: view.php?id=' . $bill_id);
                exit;
            }
        } catch (PDOException $e) {
            $error = "Error generating bill: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h2>Generate Bill</h2>
    <div>
        <a href="index.php" class="btn btn-primary">Back to Bills</a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="">
        <div class="form-group">
            <label for="invoice_id">Select Invoice *</label>
            <select id="invoice_id" name="invoice_id" class="form-control" required>
                <option value="">Select Invoice</option>
                <?php foreach ($invoices as $invoice): ?>
                    <option value="<?php echo $invoice['id']; ?>" <?php echo $invoice['id'] == $invoice_id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($invoice['invoice_number']); ?> - 
                        <?php echo htmlspecialchars($invoice['customer_name']); ?> - 
                        <?php echo 'Rs.' . number_format($invoice['total_amount'], 2); ?>
                    </option>
                <?php endforeach; ?>
                </select>
        </div>
        
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
            This will generate a PDF bill document with all invoice details, payment information, and signature fields for both parties.
        </p>
        
        <button type="submit" class="btn btn-success">Generate Bill PDF</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
