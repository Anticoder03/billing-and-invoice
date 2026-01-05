// Confirmation dialogs
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Invoice item management
let itemCount = 1;

function addInvoiceItem() {
    itemCount++;
    const itemsContainer = document.getElementById('invoice-items');
    const newItem = document.createElement('div');
    newItem.className = 'item-row';
    newItem.id = 'item-' + itemCount;
    newItem.innerHTML = `
        <div class="form-group">
            <input type="text" name="description[]" class="form-control" placeholder="Item description" required>
        </div>
        <div class="form-group">
            <input type="number" name="quantity[]" class="form-control item-quantity" placeholder="Quantity" step="0.01" min="0" required onchange="calculateItemTotal(${itemCount})">
        </div>
        <div class="form-group">
            <input type="number" name="unit_price[]" class="form-control item-price" placeholder="Unit Price" step="0.01" min="0" required onchange="calculateItemTotal(${itemCount})">
        </div>
        <div class="form-group">
            <input type="number" name="total[]" class="form-control item-total-field" placeholder="Total" step="0.01" readonly>
        </div>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeInvoiceItem(${itemCount})">Remove</button>
    `;
    itemsContainer.appendChild(newItem);
}

function removeInvoiceItem(id) {
    const item = document.getElementById('item-' + id);
    if (item) {
        item.remove();
        calculateGrandTotal();
    }
}

function calculateItemTotal(id) {
    const item = document.getElementById('item-' + id);
    if (!item) return;

    const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(item.querySelector('.item-price').value) || 0;
    const total = quantity * price;

    item.querySelector('.item-total-field').value = total.toFixed(2);
    calculateGrandTotal();
}

function calculateGrandTotal() {
    let grandTotal = 0;
    document.querySelectorAll('.item-total-field').forEach(field => {
        grandTotal += parseFloat(field.value) || 0;
    });

    const totalField = document.getElementById('grand-total');
    if (totalField) {
        totalField.value = grandTotal.toFixed(2);
    }
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const inputs = form.querySelectorAll('[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = 'var(--danger-color)';
            isValid = false;
        } else {
            input.style.borderColor = 'var(--border-color)';
        }
    });

    return isValid;
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Number formatting
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// Date formatting
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-IN');
}
