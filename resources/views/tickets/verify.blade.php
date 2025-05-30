<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Verify Ticket - {{ $event->name }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .ticket-card {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .ticket-header {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 1rem 1rem 0 0;
            text-align: center;
        }
        .ticket-body {
            padding: 2rem;
        }
        .ticket-footer {
            padding: 1.5rem 2rem;
            background: #f8f9fa;
            border-radius: 0 0 1rem 1rem;
            text-align: center;
        }
        .qr-code {
            max-width: 200px;
            margin: 0 auto;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
        }
        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        .event-logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="ticket-card">
            <div class="ticket-header">
                @if($event->logo_path)
                    <img src="{{ asset('storage/' . $event->logo_path) }}" alt="{{ $event->name }}" class="event-logo">
                @endif
                <h4 class="mb-2">{{ $event->name }}</h4>
                <p class="text-muted mb-0">
                    {{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}
                </p>
            </div>

            <div class="ticket-body">
                <div class="text-center mb-4">
                    <div class="qr-code">
                        {!! QrCode::size(200)->generate(request()->url()) !!}
                    </div>
                    <p class="text-muted mt-2">Scan this QR code to verify the ticket</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Attendee Information</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Name:</dt>
                            <dd class="col-sm-8">{{ $attendee->name }}</dd>

                            <dt class="col-sm-4">Email:</dt>
                            <dd class="col-sm-8">{{ $attendee->email }}</dd>

                            <dt class="col-sm-4">Phone:</dt>
                            <dd class="col-sm-8">{{ $attendee->phone }}</dd>

                            @if($attendee->position)
                                <dt class="col-sm-4">Position:</dt>
                                <dd class="col-sm-8">{{ $attendee->position }}</dd>
                            @endif
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Ticket Information</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Ticket ID:</dt>
                            <dd class="col-sm-8">{{ $ticket->token }}</dd>

                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $ticket->is_valid ? 'success' : 'danger' }} status-badge">
                                    {{ $ticket->is_valid ? 'Valid' : 'Invalid' }}
                                </span>
                            </dd>

                            <dt class="col-sm-4">Created:</dt>
                            <dd class="col-sm-8">{{ $ticket->created_at->format('M d, Y H:i') }}</dd>

                            @if($ticket->last_scanned_at)
                                <dt class="col-sm-4">Last Scanned:</dt>
                                <dd class="col-sm-8">{{ $ticket->last_scanned_at->format('M d, Y H:i') }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <div class="ticket-footer">
                <p class="text-muted mb-0">
                    This ticket is managed by {{ config('app.name') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 