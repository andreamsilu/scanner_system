@props(['type' => 'success'])

@php
    $modalClass = match($type) {
        'success' => 'text-success',
        'error' => 'text-danger',
        'warning' => 'text-warning',
        'info' => 'text-info',
        default => 'text-success'
    };

    $icon = match($type) {
        'success' => 'fas fa-check-circle',
        'error' => 'fas fa-times-circle',
        'warning' => 'fas fa-exclamation-circle',
        'info' => 'fas fa-info-circle',
        default => 'fas fa-check-circle'
    };
@endphp

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">
                    <i class="bi me-2"></i>
                    <span id="feedbackTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="feedbackMessage" class="mb-0"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Wait for both DOM and Bootstrap to be ready
function initializeFeedbackModal() {
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded. Feedback modal will not work.');
        return;
    }

    const modalElement = document.getElementById('feedbackModal');
    if (!modalElement) {
        console.error('Feedback modal element not found.');
        return;
    }

    const feedbackModal = new bootstrap.Modal(modalElement);
    const feedbackTitle = document.getElementById('feedbackTitle');
    const feedbackMessage = document.getElementById('feedbackMessage');
    const modalIcon = document.querySelector('#feedbackModalLabel .bi');
    
    function showFeedback(title, message, type = 'info') {
        if (!feedbackModal || !feedbackTitle || !feedbackMessage || !modalIcon) {
            console.error('Feedback modal elements not found.');
            return;
        }

        // Set modal styling based on type
        const modalContent = document.querySelector('#feedbackModal .modal-content');
        if (!modalContent) {
            console.error('Modal content element not found.');
            return;
        }

        modalContent.className = 'modal-content';
        
        // Set icon and colors based on type
        let iconClass, bgClass;
        switch(type) {
            case 'success':
                iconClass = 'bi-check-circle-fill text-success';
                bgClass = 'bg-success bg-opacity-10';
                break;
            case 'error':
                iconClass = 'bi-x-circle-fill text-danger';
                bgClass = 'bg-danger bg-opacity-10';
                break;
            case 'warning':
                iconClass = 'bi-exclamation-triangle-fill text-warning';
                bgClass = 'bg-warning bg-opacity-10';
                break;
            default:
                iconClass = 'bi-info-circle-fill text-info';
                bgClass = 'bg-info bg-opacity-10';
        }
        
        modalContent.classList.add(bgClass);
        modalIcon.className = `bi ${iconClass} me-2`;
        feedbackTitle.textContent = title;
        feedbackMessage.textContent = message;
        
        try {
            feedbackModal.show();
            
            // Auto-hide after 5 seconds for success/error messages
            if (type === 'success' || type === 'error') {
                setTimeout(() => {
                    feedbackModal.hide();
                }, 5000);
            }
        } catch (error) {
            console.error('Error showing feedback modal:', error);
        }
    }
    
    // Check for session messages
    @if(session('success'))
        showFeedback('Success', "{{ session('success') }}", 'success');
    @endif

    @if(session('error'))
        showFeedback('Error', "{{ session('error') }}", 'error');
    @endif

    @if(session('warning'))
        showFeedback('Warning', "{{ session('warning') }}", 'warning');
    @endif

    @if(session('info'))
        showFeedback('Information', "{{ session('info') }}", 'info');
    @endif

    @if(session('page_expired'))
        showFeedback('Session Expired', 'Your session has expired. Please log in again.', 'warning');
        setTimeout(() => {
            window.location.href = "{{ route('login') }}";
        }, 2000);
    @endif

    // Make showFeedback available globally
    window.showFeedback = showFeedback;

    // Handle AJAX errors
    document.addEventListener('ajaxError', function(event) {
        if (event.detail.status === 419) {
            showFeedback('Session Expired', 'Your session has expired. Please log in again.', 'warning');
            setTimeout(() => {
                window.location.href = "{{ route('login') }}";
            }, 2000);
        }
    });
}

// Try to initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // If Bootstrap is already loaded, initialize immediately
    if (typeof bootstrap !== 'undefined') {
        initializeFeedbackModal();
    } else {
        // Otherwise, wait for Bootstrap to load
        const checkBootstrap = setInterval(function() {
            if (typeof bootstrap !== 'undefined') {
                clearInterval(checkBootstrap);
                initializeFeedbackModal();
            }
        }, 100);

        // Stop checking after 5 seconds
        setTimeout(function() {
            clearInterval(checkBootstrap);
            if (typeof bootstrap === 'undefined') {
                console.error('Bootstrap failed to load within 5 seconds.');
            }
        }, 5000);
    }
});
</script>
 