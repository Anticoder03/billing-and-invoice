<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Edit Invoice';

// Get invoice ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Get invoice data
try {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ?");
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
    
    // Get all customers
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
            
            // Calculate total
            $total_amount = 0;
            foreach ($totals as $total) {
                $total_amount += floatval($total);
            }
            
            // Update invoice
            $stmt = $pdo->prepare("
                UPDATE invoices 
                SET customer_id = ?, invoice_date = ?, due_date = ?, total_amount = ?, notes = ? 
                WHERE id = ?
            ");
            $stmt->execute([$customer_id, $invoice_date, $due_date, $total_amount, $notes, $id]);
            
            // Delete old items
            $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $stmt->execute([$id]);
            
            // Insert new items
            $stmt = $pdo->prepare("
                INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            for ($i = 0; $i < count($descriptions); $i++) {
                if (!empty($descriptions[$i])) {
                    $stmt->execute([
                        $id,
                        $descriptions[$i],
                        $quantities[$i],
                        $unit_prices[$i],
                        $totals[$i]
                    ]);
                }
            }
            
            $pdo->commit();
            header('Location: index.php?success=updated');
            exit;
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error updating invoice: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="page-header">
    <h2>Edit Invoice</h2>
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
                        <option value="<?php echo $customer['id']; ?>" <?php echo $customer['id'] == $invoice['customer_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($customer['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="invoice_date">Invoice Date *</label>
                <input type="date" id="invoice_date" name="invoice_date" class="form-control" required value="<?php echo $invoice['invoice_date']; ?>">
            </div>
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" class="form-control" value="<?php echo $invoice['due_date']; ?>">
            </div>
        </div>
        
        <h3 style="margin: 2rem 0 1rem;">Invoice Items</h3>
        <div id="invoice-items">
            <?php 
            $item_num = 0;
            foreach ($items as $item): 
                $item_num++;
            ?>
            <div class="item-row" id="item-<?php echo $item_num; ?>">
                <div class="form-group">
                    <label>Description *</label>
                    <input type="text" name="description[]" class="form-control" placeholder="Item description" required value="<?php echo htmlspecialchars($item['description']); ?>">
                </div>
                <div class="form-group">
                    <label>Quantity *</label>
                    <input type="number" name="quantity[]" class="form-control item-quantity" placeholder="Quantity" step="0.01" min="0" required value="<?php echo $item['quantity']; ?>" onchange="calculateItemTotal(<?php echo $item_num; ?>)">
                </div>
                <div class="form-group">
                    <label>Unit Price *</label>
                    <input type="number" name="unit_price[]" class="form-control item-price" placeholder="Unit Price" step="0.01" min="0" required value="<?php echo $item['unit_price']; ?>" onchange="calculateItemTotal(<?php echo $item_num; ?>)">
                </div>
                <div class="form-group">
                    <label>Total</label>
                    <input type="number" name="total[]" class="form-control item-total-field" placeholder="Total" step="0.01" readonly value="<?php echo $item['total']; ?>">
                </div>
                <div style="align-self: end;">
                    <?php if ($item_num > 1): ?>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeInvoiceItem(<?php echo $item_num; ?>)">Remove</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-danger btn-sm" style="visibility: hidden;">Remove</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <button type="button" class="btn btn-primary" onclick="addInvoiceItem()">+ Add Item</button>
        
        <div class="item-total">
            <strong>Grand Total: <?php echo CURRENCY_SYMBOL; ?></strong>
            <input type="number" id="grand-total" readonly style="border: none; background: transparent; font-weight: bold; text-align: right; width: 150px;" value="<?php echo $invoice['total_amount']; ?>">
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" placeholder="Additional notes or terms"><?php echo htmlspecialchars($invoice['notes'] ?? ''); ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-success">Update Invoice</button>
    </form>
</div>

<script>
    // Update itemCount to match existing items
    itemCount = <?php echo $item_num; ?>;
    // Calculate grand total on load
    calculateGrandTotal();
</script>

<?php include '../includes/footer.php'; ?>
