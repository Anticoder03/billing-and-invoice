<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Get invoice ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Delete invoice items first (cascade)
    $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$id]);
    
    // Delete invoice
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
    $stmt->execute([$id]);
    
    $pdo->commit();
    header('Location: index.php?success=deleted');
    exit;
    
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Error deleting invoice: " . $e->getMessage());
}
?>
