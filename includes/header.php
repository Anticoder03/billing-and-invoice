<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <h1><?php echo APP_NAME; ?></h1>
            </div>
            <ul class="nav-menu">
                <li><a href="<?php echo BASE_URL; ?>index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && !isset($_GET['page']) ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>customers/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'customers') !== false ? 'active' : ''; ?>">Customers</a></li>
                <li><a href="<?php echo BASE_URL; ?>invoices/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'invoices') !== false ? 'active' : ''; ?>">Invoices</a></li>
                <li><a href="<?php echo BASE_URL; ?>payments/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'payments') !== false ? 'active' : ''; ?>">Payments</a></li>
                <li><a href="<?php echo BASE_URL; ?>bills/index.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'bills') !== false ? 'active' : ''; ?>">Bills</a></li>
            </ul>
        </div>
    </nav>
    <main class="main-content">
        <div class="container">
