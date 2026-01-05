<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'View Bill';

// Get bill ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Get bill data
try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               i.invoice_number, i.invoice_date, i.due_date,
               c.name as customer_name, c.email as customer_email, 
               c.address as customer_address, c.phone as customer_phone
        FROM bills b 
        LEFT JOIN invoices i ON b.invoice_id = i.id 
        LEFT JOIN customers c ON b.customer_id = c.id 
        WHERE b.id = ?
    ");
    $stmt->execute([$id]);
    $bill = $stmt->fetch();
    
    if (!$bill) {
        header('Location: index.php');
        exit;
    }
    
    // Get invoice items
    $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$bill['invoice_id']]);
    $items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Action Buttons -->
    <div class="no-print fixed top-4 right-4 z-50 flex gap-2">
        <a href="index.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow-lg transition">
            ← Back to Bills
        </a>
        <button onclick="downloadPDF()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-lg transition font-semibold">
            📥 Download PDF
        </button>
    </div>

    <!-- Bill Content -->
    <div id="bill-content" class="max-w-4xl mx-auto my-8 bg-white shadow-2xl">
        <!-- Header with Logo and Business Info -->
        <div class="bg-gradient-to-r from-blue-800 to-cyan-600 text-white p-8">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-6">
                    <?php if (file_exists(LOGO_PATH)): ?>
                        <img src="<?php echo BASE_URL; ?>logo.png" alt="<?php echo BUSINESS_NAME; ?>" class="h-20 w-20 bg-white p-2 rounded-lg shadow-lg">
                    <?php endif; ?>
                    <div>
                        <h1 class="text-3xl font-bold mb-2"><?php echo BUSINESS_NAME; ?></h1>
                        <p class="text-sm opacity-90 leading-relaxed">
                            <?php echo nl2br(BUSINESS_ADDRESS); ?><br>
                            <span class="font-semibold">Phone:</span> <?php echo BUSINESS_PHONE; ?><br>
                            <span class="font-semibold">Email:</span> <?php echo BUSINESS_EMAIL; ?><br>
                            <span class="font-semibold">Email:</span> <?php echo BUSINESS_EMAIL_ALT; ?>
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-4xl font-bold mb-4">BILL</h2>
                    <div class="bg-white text-blue-800 rounded-lg p-4 shadow-lg">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <span class="font-semibold">Bill No:</span>
                            <span><?php echo htmlspecialchars($bill['bill_number']); ?></span>
                            <span class="font-semibold">Date:</span>
                            <span><?php echo date(DISPLAY_DATE_FORMAT); ?></span>
                            <span class="font-semibold">Invoice Ref:</span>
                            <span><?php echo htmlspecialchars($bill['invoice_number']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bill To Section -->
        <div class="p-8">
            <div class="bg-gray-50 border-l-4 border-cyan-600 p-6 mb-8">
                <h3 class="text-blue-800 font-bold text-lg mb-3">BILL TO:</h3>
                <div class="text-gray-700">
                    <p class="font-bold text-lg mb-2"><?php echo htmlspecialchars($bill['customer_name']); ?></p>
                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($bill['customer_address'])); ?></p>
                    <p class="mb-1"><span class="font-semibold">Phone:</span> <?php echo htmlspecialchars($bill['customer_phone']); ?></p>
                    <p><span class="font-semibold">Email:</span> <?php echo htmlspecialchars($bill['customer_email']); ?></p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="mb-8 overflow-hidden rounded-lg border border-gray-200">
                <table class="w-full">
                    <thead>
                        <tr class="bg-blue-800 text-white">
                            <th class="text-left p-4 font-semibold">DESCRIPTION</th>
                            <th class="text-center p-4 font-semibold w-24">QTY</th>
                            <th class="text-right p-4 font-semibold w-32">UNIT PRICE</th>
                            <th class="text-right p-4 font-semibold w-36">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item): ?>
                            <tr class="<?php echo $index % 2 == 0 ? 'bg-white' : 'bg-gray-50'; ?> border-b border-gray-200">
                                <td class="p-4"><?php echo htmlspecialchars($item['description']); ?></td>
                                <td class="text-center p-4"><?php echo number_format($item['quantity'], 0); ?></td>
                                <td class="text-right p-4">Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-right p-4 font-semibold">Rs. <?php echo number_format($item['total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="bg-cyan-600 text-white font-bold text-lg">
                            <td colspan="3" class="text-right p-4">TOTAL AMOUNT:</td>
                            <td class="text-right p-4">Rs. <?php echo number_format($bill['total_amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Payment Status -->
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-8">
                <p class="text-green-800 font-semibold">
                    <span class="text-green-600 text-xl">✓</span> Payment Status: <span class="font-bold">PAID</span>
                </p>
            </div>

            <!-- Signatures -->
            <div class="grid grid-cols-2 gap-8 mb-8 mt-16">
                <div>
                    <div class="border-t-2 border-gray-800 pt-2 w-64">
                        <p class="font-bold">Authorized Signature</p>
                        <p class="text-sm text-gray-600"><?php echo BUSINESS_NAME; ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="border-t-2 border-gray-800 pt-2 w-64 ml-auto">
                        <p class="font-bold">Customer Signature</p>
                        <p class="text-sm text-gray-600">Date: ___________</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-300 pt-6 text-center">
                <p class="text-blue-800 font-bold text-lg mb-1">Thank you for your business!</p>
                <p class="text-gray-500 text-sm">This is a computer-generated bill and does not require a physical signature.</p>
            </div>
        </div>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('bill-content');
            const opt = {
                margin: 0,
                filename: 'Bill_<?php echo $bill['bill_number']; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, letterRendering: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Show loading state
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Generating PDF...';
            button.disabled = true;
            
            html2pdf().set(opt).from(element).save().then(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    </script>
</body>
</html>
