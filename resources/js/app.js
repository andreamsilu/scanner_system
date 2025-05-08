import './bootstrap';
import 'bootstrap';
import '../css/app.css';
import { FeedbackModal } from './feedback-modal';

// Initialize feedback modal
const feedbackModal = new FeedbackModal();

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
    } else if (jqXHR.status === 419) {
        // Page Expired (CSRF token mismatch)
        feedbackModal.show('Session Expired', 'Your session has expired. Please log in again.');
        setTimeout(() => {
            window.location.href = '/login';
        }, 2000);
    } else if (jqXHR.status === 401) {
        // Unauthorized
        feedbackModal.show('Unauthorized', 'Please log in to continue.');
        setTimeout(() => {
            window.location.href = '/login';
        }, 2000);
    } else {
        // Other errors
        feedbackModal.show('Error', 'An error occurred. Please try again.');
    }
});
