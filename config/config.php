<?php
// Application configuration
define('APP_NAME', 'Billing System');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/billing-web/second/');

// Composer Autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Business information
define('BUSINESS_NAME', 'Tech Fellows');
define('BUSINESS_ADDRESS', 'Shop No.3 Rahul Apartment Ram Nagar Chhiri Vapi Gujarat 396191');
define('BUSINESS_PHONE', '+91 7383745943');
define('BUSINESS_EMAIL', 'official@techfellows.tech');
define('BUSINESS_EMAIL_ALT', 'officialtechfellows@gmail.com');
define('USERNAME', 'Tech Fellows'); // Default username for bills
define('LOGO_PATH', __DIR__ . '/../logo.png');

// Date and currency settings
define('DATE_FORMAT', 'Y-m-d');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');
define('CURRENCY_SYMBOL', 'Rs.');
define('CURRENCY_CODE', 'INR');

// PDF settings
define('PDF_DIR', __DIR__ . '/../pdfs/');
define('PDF_URL', BASE_URL . 'pdfs/');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Create PDF directory if it doesn't exist
if (!file_exists(PDF_DIR)) {
    mkdir(PDF_DIR, 0777, true);
}
?>
