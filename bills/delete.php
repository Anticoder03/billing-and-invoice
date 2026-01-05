<?php
require_once '../config/config.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        // Get file path before deleting record
        $stmt = $pdo->prepare("SELECT pdf_path FROM bills WHERE id = ?");
        $stmt->execute([$id]);
        $bill = $stmt->fetch();
        
        if ($bill) {
            // Delete record
            $stmt = $pdo->prepare("DELETE FROM bills WHERE id = ?");
            
            if ($stmt->execute([$id])) {
                // Delete file if exists
                $filepath = '../' . $bill['pdf_path'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                
                header('Location: index.php?success=deleted');
                exit;
            } else {
                echo "Error deleting record.";
            }
        } else {
            echo "Bill not found.";
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header('Location: index.php');
}
?>
