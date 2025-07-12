function updateCustomAmount() {
    const quickSelect = document.getElementById('quick_amount');
    const manualContainer = document.getElementById('manual_amount_container');
    const manualInput = document.getElementById('manual_amount');
    const finalAmountInput = document.getElementById('amount');

    if (quickSelect.value === 'manual') {
        manualContainer.classList.remove('d-none'); // Show manual input
        manualInput.focus();
        finalAmountInput.value = ''; // Clear hidden input
    } else if (quickSelect.value) {
        manualContainer.classList.add('d-none'); // Hide manual input
        finalAmountInput.value = quickSelect.value; // Set hidden input from dropdown
    } else {
        manualContainer.classList.add('d-none'); // Hide if nothing is selected
        finalAmountInput.value = '';
    }
}

// Update hidden "amount" field with manual input value
document.getElementById('manual_amount').addEventListener('input', function () {
    document.getElementById('amount').value = this.value;
});