<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Get customer ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    // Check if customer has invoices
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE customer_id = ?");
    $stmt->execute([$id]);
    $invoice_count = $stmt->fetch()['count'];
    
    if ($invoice_count > 0) {
        header('Location: index.php?error=has_invoices');
        exit;
    }
    
    // Delete customer
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    
    header('Location: index.php?success=deleted');
    exit;
    
} catch (PDOException $e) {
    die("Error deleting customer: " . $e->getMessage());
}
?>
