<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - {{ $ticket->attendee->name }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background: #fff;
        }
        .ticket {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            border-radius: 10px;
            padding: 20px;
            position: relative;
            background: #fff;
        }
        .ticket-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #000;
        }
        .event-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #000;
        }
        .event-details {
            font-size: 16px;
            color: #666;
            margin: 10px 0;
        }
        .ticket-content {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }
        .ticket-info {
            flex: 1;
            padding-right: 20px;
        }
        .qr-code {
            flex: 1;
            text-align: center;
        }
        .qr-code img {
            max-width: 200px;
            height: auto;
        }
        .ticket-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #000;
            font-size: 12px;
            color: #666;
        }
        .ticket-id {
            font-family: monospace;
            font-size: 18px;
            background: #f5f5f5;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        .attendee-info {
            margin: 20px 0;
        }
        .attendee-info p {
            margin: 5px 0;
        }
        .ticket-type {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 0;
        }
        .ticket-type.vip { background: #ffd700; }
        .ticket-type.regular { background: #e0e0e0; }
        .ticket-type.single { background: #90caf9; }
        .ticket-type.double { background: #a5d6a7; }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            color: rgba(0, 0, 0, 0.1);
            white-space: nowrap;
            pointer-events: none;
        }
        @media print {
            body {
                padding: 0;
            }
            .ticket {
                border: none;
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="watermark">EVENT TICKET</div>
        
        <div class="ticket-header">
            <h1 class="event-name">{{ $ticket->event->name }}</h1>
            <div class="event-details">
                <p>{{ $ticket->event->venue }}</p>
                <p>{{ $ticket->event->start_date->format('F j, Y g:i A') }}</p>
            </div>
        </div>

        <div class="ticket-content">
            <div class="ticket-info">
                <div class="attendee-info">
                    <h3>Attendee Information</h3>
                    <p><strong>Name:</strong> {{ $ticket->attendee->name }}</p>
                    <p><strong>Email:</strong> {{ $ticket->attendee->email }}</p>
                    @if($ticket->attendee->phone)
                        <p><strong>Phone:</strong> {{ $ticket->attendee->phone }}</p>
                    @endif
                </div>

                <div class="ticket-details">
                    <h3>Ticket Details</h3>
                    <p><strong>Ticket ID:</strong> <span class="ticket-id">{{ $ticket->attendee->token }}</span></p>
                    <p><strong>Type:</strong> <span class="ticket-type {{ $ticket->type }}">{{ ucfirst($ticket->type) }}</span></p>
                    <p><strong>Status:</strong> {{ ucfirst($ticket->status) }}</p>
                    <p><strong>Issued:</strong> {{ $ticket->created_at->format('F j, Y g:i A') }}</p>
                </div>
            </div>

            <div class="qr-code">
                @if($ticket->attendee->qr_code)
                    <img src="{{ $ticket->attendee->qr_code_url }}" 
                         alt="QR Code" 
                         style="max-width: 200px; height: auto;">
                    <p>Scan this QR code for check-in</p>
                @else
                    {!! QrCode::size(200)->generate(route('tickets.verify', $ticket->token)) !!}
                    <p>Scan this QR code for check-in</p>
                @endif
            </div>
        </div>

        <div class="ticket-footer">
            <p>This ticket is non-transferable and must be presented at the event.</p>
            <p>For any questions, please contact the event organizer.</p>
            <p>Printed on {{ now()->format('F j, Y g:i A') }}</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
            Print Ticket
        </button>
    </div>
</body>
</html> 