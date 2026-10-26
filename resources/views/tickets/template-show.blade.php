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
            margin: 0;
            padding: 20px;
        }
        @media print {
            body {
                background: #fff;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    @if($ticket->event->cardTemplate)
        {!! $ticket->event->cardTemplate->render([
            'event_name' => $ticket->event->name,
            'event_date' => $ticket->event->start_date->format('F j, Y'),
            'event_time' => $ticket->event->start_date->format('g:i A'),
            'event_venue' => $ticket->event->venue,
            'attendee_name' => $ticket->attendee->name,
            'attendee_email' => $ticket->attendee->email,
            'attendee_phone' => $ticket->attendee->phone,
            'attendee_company' => $ticket->attendee->company,
            'attendee_position' => $ticket->attendee->position,
            'attendee_status' => $ticket->attendee->registration_status,
            'ticket_id' => $ticket->ticket_number
        ]) !!}
    @else
        <!-- Fallback to default design if no template is assigned -->
        <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
            <h2>Event Ticket</h2>
            <p><strong>Event:</strong> {{ $ticket->event->name }}</p>
            <p><strong>Attendee:</strong> {{ $ticket->attendee->name }}</p>
            <p><strong>Date:</strong> {{ $ticket->event->start_date->format('F j, Y g:i A') }}</p>
            <p><strong>Venue:</strong> {{ $ticket->event->venue }}</p>
            <p><strong>Ticket ID:</strong> {{ $ticket->ticket_number }}</p>
            @if($ticket->attendee->qr_code)
                <img src="{{ $ticket->attendee->qr_code_url }}" alt="QR Code" style="max-width: 140px; height: auto; margin-top: 1em;">
            @else
                {!! QrCode::size(140)->generate(route('tickets.verify', $ticket->token)) !!}
            @endif
        </div>
    @endif

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px;">
            Print Ticket
        </button>
        <a href="{{ route('tickets.show', $ticket) }}" style="padding: 10px 20px; font-size: 16px; text-decoration: none; background: #6c757d; color: white; border-radius: 4px; margin-left: 10px;">
            View Default Design
        </a>
    </div>
</body>
</html>
