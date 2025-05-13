<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="page-expired" content="{{ session('page_expired') ? 'true' : 'false' }}">

    <title>{{ config('app.name', 'Event MS') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        /* Base Styles */
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: transparent;
            border-bottom: none;
            padding: 1.5rem 1.5rem 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form Styles */
        .form-control {
            padding: 0.6rem 1rem;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.2s;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
            border-color: #86b7fe;
        }

        .input-group-text {
            border: 1px solid #e0e0e0;
            border-radius: 8px 0 0 8px;
        }

        .input-group .form-control {
            border-radius: 0 8px 8px 0;
        }

        /* Button Styles */
        .btn-primary {
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Alert Styles */
        .alert {
            border-radius: 8px;
            border: none;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
        }

        /* Checkbox Styles */
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
        <main>
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Flash Message Handler -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessage = document.querySelector('meta[name="flash-message"]');
            const flashType = document.querySelector('meta[name="flash-type"]');
            
            if (flashMessage && flashType) {
                const message = flashMessage.getAttribute('content');
                const type = flashType.getAttribute('content');
                
                // Create and show toast notification
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed top-0 end-0 m-3`;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');
                
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;
                
                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
            }
        });
    </script>

    @stack('scripts')
</body>
</html> 