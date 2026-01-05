<?php
require_once '../config/config.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM bills WHERE id = ?");
        $stmt->execute([$id]);
        $bill = $stmt->fetch();

        if ($bill) {
            $filepath = '../' . $bill['pdf_path'];
            
            if (file_exists($filepath)) {
                // Set headers
                header('Content-Description: File Transfer');
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                
                // Clear output buffer
                flush();
                
                // Read file
                readfile($filepath);
                exit;
            } else {
                echo "Error: PDF file not found on server.";
            }
        } else {
            echo "Error: Bill record not found.";
        }
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    echo "Invalid request.";
}
?>
