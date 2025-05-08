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

<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="feedbackMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
    const feedbackMessage = document.getElementById('feedbackMessage');
    
    // Check for session messages
    @if(session('success'))
        feedbackMessage.textContent = "{{ session('success') }}";
        feedbackModal.show();
    @endif

    @if(session('error'))
        feedbackMessage.textContent = "{{ session('error') }}";
        feedbackModal.show();
    @endif

    @if(session('page_expired'))
        feedbackMessage.textContent = "Your session has expired. Please log in again.";
        feedbackModal.show();
    @endif

    // Handle AJAX errors
    document.addEventListener('ajaxError', function(event) {
        if (event.detail.status === 419) {
            feedbackMessage.textContent = "Your session has expired. Please log in again.";
            feedbackModal.show();
            setTimeout(() => {
                window.location.href = "{{ route('login') }}";
            }, 2000);
        }
    });
});
</script>
 