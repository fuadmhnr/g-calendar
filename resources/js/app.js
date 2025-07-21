import './bootstrap';

// Add event listeners when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Date validation for event creation/editing
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            // Ensure end date is not before start date
            if (endDateInput.value < startDateInput.value) {
                endDateInput.value = startDateInput.value;
            }
            
            // Set min attribute of end date to start date
            endDateInput.min = startDateInput.value;
        });
    }
    
    // Flash message auto-dismiss
    const flashMessages = document.querySelectorAll('.alert-success, .alert-error');
    if (flashMessages.length > 0) {
        flashMessages.forEach(message => {
            setTimeout(() => {
                message.style.opacity = '0';
                setTimeout(() => {
                    message.remove();
                }, 500);
            }, 5000);
        });
    }
});