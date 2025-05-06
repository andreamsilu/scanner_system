import './bootstrap';
import 'bootstrap';
import '../css/app.css';

// Initialize all tooltips
const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

// Initialize all popovers
const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

// Global AJAX setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Global error handling
$(document).ajaxError(function(event, jqXHR, settings, error) {
    if (jqXHR.status === 422) {
        // Validation errors
        const errors = jqXHR.responseJSON.errors;
        Object.keys(errors).forEach(key => {
            const input = $(`[name="${key}"]`);
            input.addClass('is-invalid');
            input.after(`<div class="invalid-feedback">${errors[key][0]}</div>`);
        });
    } else if (jqXHR.status === 401) {
        // Unauthorized
        window.location.href = '/login';
    } else {
        // Other errors
        alert('An error occurred. Please try again.');
    }
});
