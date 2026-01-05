<?php
// Prevent direct access
if (!defined('DB_HOST')) {
    // If accessed directly, try to load config
    if (file_exists('../config/config.php')) {
        require_once '../config/config.php';
        require_once '../config/database.php';
    }
}

function generateBillPDF($pdo, $bill_id, $filepath) {
    // Fetch bill and related details
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
        $stmt->execute([$bill_id]);
        $bill = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bill) {
            throw new Exception("Bill not found");
        }

        // Fetch invoice items
        $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$bill['invoice_id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }

    // Manual include if autoloader fails
    if (!class_exists('TCPDF')) {
        require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
    }

    // Create new PDF structure
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(APP_NAME);
    $pdf->SetAuthor(BUSINESS_NAME);
    $pdf->SetTitle('Bill ' . $bill['bill_number']);
    $pdf->SetSubject('Bill Payment');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set formatting
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->SetFont('helvetica', '', 10);

    // Add a page
    $pdf->AddPage();

    // Define professional colors (based on tech/blue theme)
    $primaryColor = '#1e40af'; // Deep blue
    $accentColor = '#0891b2'; // Teal/Cyan
    $darkGray = '#1f2937';
    $lightGray = '#f3f4f6';
    
    // --- Header Section with Logo ---
    // Add logo if it exists and extensions are available
    $logoPath = LOGO_PATH;
    $logoAdded = false;
    if (file_exists($logoPath)) {
        try {
            // Try to add the logo, but catch any errors if GD/Imagick is not available
            @$pdf->Image($logoPath, 15, 15, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $logoAdded = true;
        } catch (Exception $e) {
            // Logo couldn't be added, continue without it
            $logoAdded = false;
        }
    }
    
    // Company info and bill details - adjust padding based on whether logo was added
    $leftPadding = $logoAdded ? '45mm' : '0';
    $html = '
    <table cellpadding="0" cellspacing="0" style="margin-top: 5px;">
        <tr>
            <td width="55%" style="padding-left: ' . $leftPadding . ';">
                <h2 style="color: ' . $primaryColor . '; margin: 0; font-size: 18pt; font-weight: bold;">' . BUSINESS_NAME . '</h2>
                <p style="font-size: 9pt; color: ' . $darkGray . '; line-height: 1.4; margin-top: 5px;">
                    ' . nl2br(BUSINESS_ADDRESS) . '<br>
                    <strong>Phone:</strong> ' . BUSINESS_PHONE . '<br>
                    <strong>Email:</strong> ' . BUSINESS_EMAIL . '<br>
                    <strong>Email:</strong> ' . BUSINESS_EMAIL_ALT . '
                </p>
            </td>
            <td width="45%" align="right" style="vertical-align: top;">
                <h1 style="color: ' . $accentColor . '; margin: 0; font-size: 24pt; font-weight: bold;">BILL</h1>
                <table cellspacing="0" cellpadding="3" style="margin-top: 10px; border: 1px solid ' . $primaryColor . ';">
                    <tr style="background-color: ' . $primaryColor . '; color: white;">
                        <td style="padding: 5px;"><strong>Bill No:</strong></td>
                        <td style="padding: 5px;">' . $bill['bill_number'] . '</td>
                    </tr>
                    <tr style="background-color: ' . $lightGray . ';">
                        <td style="padding: 5px;"><strong>Date:</strong></td>
                        <td style="padding: 5px;">' . date(DISPLAY_DATE_FORMAT) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px;"><strong>Invoice Ref:</strong></td>
                        <td style="padding: 5px;">' . $bill['invoice_number'] . '</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    ';
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Decorative line
    $pdf->Ln(5);
    $pdf->SetDrawColor(30, 64, 175); // Primary blue color
    $pdf->SetLineWidth(0.8);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(8);

    // --- Bill To Section ---
    $html = '
    <table cellpadding="8" style="background-color: ' . $lightGray . '; border-left: 4px solid ' . $accentColor . ';">
        <tr>
            <td>
                <h3 style="color: ' . $primaryColor . '; margin: 0 0 8px 0;">BILL TO:</h3>
                <p style="font-size: 10pt; line-height: 1.5; margin: 0;">
                    <strong style="font-size: 11pt; color: ' . $darkGray . ';">' . htmlspecialchars($bill['customer_name']) . '</strong><br>
                    ' . nl2br(htmlspecialchars($bill['customer_address'])) . '<br>
                    <strong>Phone:</strong> ' . htmlspecialchars($bill['customer_phone']) . '<br>
                    <strong>Email:</strong> ' . htmlspecialchars($bill['customer_email']) . '
                </p>
            </td>
        </tr>
    </table>
    ';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(8);

    // --- Items Table ---
    $html = '
    <table border="0" cellpadding="8" cellspacing="0" style="border-collapse: collapse;">
        <tr style="background-color: ' . $primaryColor . '; color: white; font-weight: bold; font-size: 10pt;">
            <td width="50%" style="border: 1px solid ' . $primaryColor . '; padding: 8px;">DESCRIPTION</td>
            <td width="12%" align="center" style="border: 1px solid ' . $primaryColor . '; padding: 8px;">QTY</td>
            <td width="18%" align="right" style="border: 1px solid ' . $primaryColor . '; padding: 8px;">UNIT PRICE</td>
            <td width="20%" align="right" style="border: 1px solid ' . $primaryColor . '; padding: 8px;">TOTAL</td>
        </tr>';

    $rowColor = true;
    foreach ($items as $item) {
        $bgColor = $rowColor ? '#ffffff' : $lightGray;
        $html .= '
        <tr style="background-color: ' . $bgColor . ';">
            <td style="border: 1px solid #e5e7eb; padding: 8px;">' . htmlspecialchars($item['description']) . '</td>
            <td align="center" style="border: 1px solid #e5e7eb; padding: 8px;">' . number_format($item['quantity'], 0) . '</td>
            <td align="right" style="border: 1px solid #e5e7eb; padding: 8px;">Rs. ' . number_format($item['unit_price'], 2) . '</td>
            <td align="right" style="border: 1px solid #e5e7eb; padding: 8px; font-weight: bold;">Rs. ' . number_format($item['total'], 2) . '</td>
        </tr>';
        $rowColor = !$rowColor;
    }

    // Totals
    $html .= '
        <tr style="background-color: ' . $accentColor . '; color: white; font-weight: bold; font-size: 11pt;">
            <td colspan="3" align="right" style="border: 1px solid ' . $accentColor . '; padding: 10px;">TOTAL AMOUNT:</td>
            <td align="right" style="border: 1px solid ' . $accentColor . '; padding: 10px; font-size: 12pt;">Rs. ' . number_format($bill['total_amount'], 2) . '</td>
        </tr>
    </table>
    ';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(10);

    // --- Payment Status ---
    $html = '
    <table cellpadding="8" style="background-color: #dcfce7; border-left: 4px solid #16a34a;">
        <tr>
            <td>
                <p style="margin: 0; font-size: 11pt;"><strong style="color: #15803d;">Payment Status:</strong> <span style="color: #16a34a; font-weight: bold;">✓ PAID</span></p>
            </td>
        </tr>
    </table>
    ';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Ln(15);

    // --- Footer / Signatures ---
    $html = '
    <table cellpadding="5">
        <tr>
            <td width="50%">
                <br><br><br>
                <div style="border-top: 2px solid ' . $darkGray . '; width: 60%; padding-top: 5px;">
                    <strong>Authorized Signature</strong><br>
                    <span style="font-size: 8pt; color: #6b7280;">' . BUSINESS_NAME . '</span>
                </div>
            </td>
            <td width="50%" align="right">
                <br><br><br>
                <div style="border-top: 2px solid ' . $darkGray . '; width: 60%; padding-top: 5px; float: right;">
                    <strong>Customer Signature</strong><br>
                    <span style="font-size: 8pt; color: #6b7280;">Date: ___________</span>
                </div>
            </td>
        </tr>
    </table>
    ';
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Footer message
    $pdf->Ln(10);
    $html = '
    <hr style="border: 0; border-top: 1px solid #e5e7eb;">
    <p align="center" style="font-size: 9pt; color: #6b7280; margin-top: 8px;">
        <strong style="color: ' . $primaryColor . ';">Thank you for your business!</strong><br>
        <span style="font-size: 8pt;">This is a computer-generated bill and does not require a physical signature.</span>
    </p>
    ';
    $pdf->writeHTML($html, true, false, true, false, '');

    // Save file
    $pdf->Output($filepath, 'F');
}
?>
