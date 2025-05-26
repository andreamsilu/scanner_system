export class FeedbackModal {
    constructor() {
        this.modal = null;
        this.titleElement = null;
        this.messageElement = null;
        this.initialize();
    }

    initialize() {
        document.addEventListener('DOMContentLoaded', () => {
            this.modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
            this.titleElement = document.getElementById('feedbackModalLabel');
            this.messageElement = document.getElementById('feedbackModalMessage');

            // Listen for session flash messages
            const flashMessage = document.querySelector('meta[name="flash-message"]');
            const flashType = document.querySelector('meta[name="flash-type"]');
            
            if (flashMessage && flashType) {
                const message = flashMessage.getAttribute('content');
                const type = flashType.getAttribute('content');
                if (message) {
                    this.show(this.formatTitle(type), message);
                }
            }

            // Check for page expired error
            const pageExpired = document.querySelector('meta[name="page-expired"]');
            if (pageExpired) {
                this.show('Session Expired', 'Your session has expired. Please log in again.');
                setTimeout(() => {
                    window.location.href = '/login';
                }, 2000);
            }
        });
    }

    show(title, message) {
        if (this.titleElement && this.messageElement) {
            this.titleElement.textContent = title;
            this.messageElement.textContent = message;
            this.modal.show();
        }
    }

    hide() {
        if (this.modal) {
            this.modal.hide();
        }
    }

    formatTitle(type) {
        switch (type) {
            case 'success':
                return 'Success!';
            case 'error':
                return 'Error!';
            case 'warning':
                return 'Warning!';
            case 'info':
                return 'Information';
            default:
                return 'Notice';
        }
    }
} 