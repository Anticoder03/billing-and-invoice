<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Get payment ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
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

try {
    $pdo->beginTransaction();
    
    // Get invoice_id before deleting
    $stmt = $pdo->prepare("SELECT invoice_id FROM payments WHERE id = ?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        $invoice_id = $payment['invoice_id'];
        
        // Delete payment
        $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
        $stmt->execute([$id]);
        
        // Update invoice status
        updateInvoiceStatus($pdo, $invoice_id);
    }
    
    $pdo->commit();
    header('Location: index.php?success=deleted');
    exit;
    
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error deleting payment: " . $e->getMessage());
}
?>
