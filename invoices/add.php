<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Create Invoice';

// Get all customers
try {
    $stmt = $pdo->query("SELECT id, name FROM customers ORDER BY name");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = (int)$_POST['customer_id'];
    $invoice_date = $_POST['invoice_date'];
    $due_date = $_POST['due_date'];
    $notes = trim($_POST['notes']);
    $descriptions = $_POST['description'];
    $quantities = $_POST['quantity'];
    $unit_prices = $_POST['unit_price'];
    $totals = $_POST['total'];
    
    // Validation
    if ($customer_id <= 0) {
        $error = "Please select a customer.";
    } elseif (empty($invoice_date)) {
        $error = "Invoice date is required.";
    } elseif (empty($descriptions[0])) {
        $error = "At least one invoice item is required.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Generate invoice number
            $stmt = $pdo->query("SELECT MAX(id) as max_id FROM invoices");
            $max_id = $stmt->fetch()['max_id'] ?? 0;
            $invoice_number = 'INV-' . str_pad($max_id + 1, 6, '0', STR_PAD_LEFT);
            
            // Calculate total
            $total_amount = 0;
            foreach ($totals as $total) {
                $total_amount += floatval($total);
            }
            
            // Insert invoice
            $stmt = $pdo->prepare("
                INSERT INTO invoices (customer_id, invoice_number, invoice_date, due_date, total_amount, notes) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$customer_id, $invoice_number, $invoice_date, $due_date, $total_amount, $notes]);
            $invoice_id = $pdo->lastInsertId();
            
            // Insert invoice items
            $stmt = $pdo->prepare("
                INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            for ($i = 0; $i < count($descriptions); $i++) {
                if (!empty($descriptions[$i])) {
                    $stmt->execute([
                        $invoice_id,
                        $descriptions[$i],
                        $quantities[$i],
                        $unit_prices[$i],
                        $totals[$i]
                    ]);
                }
            }
            
            $pdo->commit();
            header('Location: index.php?success=added');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error creating invoice: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h2>Create Invoice</h2>
    <div>
        <a href="index.php" class="btn btn-primary">Back to Invoices</a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="" id="invoice-form">
        <div class="form-row">
            <div class="form-group">
                <label for="customer_id">Customer *</label>
                <select id="customer_id" name="customer_id" class="form-control" required>
                    <option value="">Select Customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="invoice_date">Invoice Date *</label>
                <input type="date" id="invoice_date" name="invoice_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
            </div>
        </div>
        
        <h3 style="margin: 2rem 0 1rem;">Invoice Items</h3>
        <div id="invoice-items">
            <div class="item-row" id="item-1">
                <div class="form-group">
                    <label>Description *</label>
                    <input type="text" name="description[]" class="form-control" placeholder="Item description" required>
                </div>
                <div class="form-group">
                    <label>Quantity *</label>
                    <input type="number" name="quantity[]" class="form-control item-quantity" placeholder="Quantity" step="0.01" min="0" required onchange="calculateItemTotal(1)">
                </div>
                <div class="form-group">
                    <label>Unit Price *</label>
                    <input type="number" name="unit_price[]" class="form-control item-price" placeholder="Unit Price" step="0.01" min="0" required onchange="calculateItemTotal(1)">
                </div>
                <div class="form-group">
                    <label>Total</label>
                    <input type="number" name="total[]" class="form-control item-total-field" placeholder="Total" step="0.01" readonly>
                </div>
                <div style="align-self: end;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeInvoiceItem(1)" style="visibility: hidden;">Remove</button>
                </div>
            </div>
        </div>
        
        <button type="button" class="btn btn-primary" onclick="addInvoiceItem()">+ Add Item</button>
        
        <div class="item-total">
            <strong>Grand Total: <?php echo CURRENCY_SYMBOL; ?></strong>
            <input type="number" id="grand-total" readonly style="border: none; background: transparent; font-weight: bold; text-align: right; width: 150px;" value="0.00">
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" placeholder="Additional notes or terms"></textarea>
        </div>
        
        <button type="submit" class="btn btn-success">Create Invoice</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
