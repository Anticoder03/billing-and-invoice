<?php
require_once '../config/config.php';
require_once '../config/database.php';

$page_title = 'Customers';

// Get all customers
try {
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC");
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching customers: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="page-header">
    <h2>Customers</h2>
    <div>
        <a href="add.php" class="btn btn-primary">+ Add Customer</a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['success'] == 'added') echo 'Customer added successfully!';
        elseif ($_GET['success'] == 'updated') echo 'Customer updated successfully!';
        elseif ($_GET['success'] == 'deleted') echo 'Customer deleted successfully!';
        ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <?php if (count($customers) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo $customer['id']; ?></td>
                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                        <td><?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars(substr($customer['address'] ?? '', 0, 50)); ?><?php echo strlen($customer['address'] ?? '') > 50 ? '...' : ''; ?></td>
                        <td><?php echo date(DISPLAY_DATE_FORMAT, strtotime($customer['created_at'])); ?></td>
                        <td>
                            <div class="actions">
                                <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete.php?id=<?php echo $customer['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Are you sure you want to delete this customer?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="card">
            <p style="text-align: center; color: var(--text-secondary);">No customers found. <a href="add.php">Add your first customer</a></p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
