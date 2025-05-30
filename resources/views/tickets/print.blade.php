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
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #f8f5f2;
            margin: 0;
            padding: 0;
        }
        .invitation-border {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 8px;
            padding: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            position: relative;
        }
        .border-pattern {
            border: 3px solid #bfa46d;
            border-radius: 8px;
            padding: 36px 32px 32px 32px;
            margin: 0;
            position: relative;
        }
        .border-pattern:before, .border-pattern:after {
            content: '';
            position: absolute;
            left: 0; right: 0;
            height: 16px;
            background: repeating-linear-gradient(90deg, #bfa46d, #bfa46d 2px, transparent 2px, transparent 16px);
        }
        .border-pattern:before {
            top: 0;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .border-pattern:after {
            bottom: 0;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
        .invitation-content {
            text-align: center;
            color: #7a6a4f;
        }
        .invitation-content h1 {
            font-size: 2.2rem;
            letter-spacing: 0.15em;
            margin: 0.5em 0 0.2em 0;
            font-weight: 700;
        }
        .invitation-content h2 {
            font-size: 1.2rem;
            letter-spacing: 0.1em;
            margin: 0.2em 0 1.2em 0;
            font-weight: 400;
        }
        .invitation-content .names {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            margin: 0.7em 0 0.2em 0;
        }
        .invitation-content .and {
            font-family: 'Georgia', serif;
            font-style: italic;
            font-size: 1.1rem;
            margin: 0.2em 0 0.2em 0;
            color: #bfa46d;
        }
        .invitation-content .details {
            font-size: 1.1rem;
            margin: 1.5em 0 0.5em 0;
            color: #7a6a4f;
        }
        .invitation-content .venue {
            font-size: 1rem;
            margin: 0.5em 0 0.5em 0;
            color: #7a6a4f;
        }
        .invitation-content .rsvp {
            font-size: 1rem;
            margin: 1.5em 0 0.5em 0;
            color: #7a6a4f;
        }
        .invitation-content .footer {
            font-size: 1rem;
            font-style: italic;
            margin: 1.5em 0 0 0;
            color: #bfa46d;
        }
        .invitation-content .ticket-id {
            font-size: 1.1rem;
            letter-spacing: 0.1em;
            margin: 1em 0 0.5em 0;
            color: #bfa46d;
            font-family: monospace;
        }
        .invitation-content .qr {
            margin: 2em 0 0 0;
        }
        @media print {
            body {
                background: #fff;
            }
            .invitation-border {
                box-shadow: none;
                margin: 0 auto;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invitation-border">
        <div class="border-pattern">
            <div class="invitation-content">
                <h2>PLEASE JOIN US<br><span style="font-size:1.1em;font-style:italic;">for the event</span></h2>
                <div class="names">{{ strtoupper($ticket->attendee->name) }}</div>
                <div class="and">for<br>{{ strtoupper($ticket->event->name) }}</div>
                <div class="details">
                    {{ $ticket->event->start_date->format('l, F j, Y') }}<br>
                    at {{ $ticket->event->start_date->format('g:i A') }}
                </div>
                <div class="venue">
                    {{ $ticket->event->venue }}<br>
                    {{ $ticket->event->location }}
                </div>
                <div class="ticket-id">
                    Ticket ID: {{ $ticket->token }}<br>
                    Type: {{ ucfirst($ticket->type) }}
                </div>
                <div class="rsvp">
                    Issued: {{ $ticket->created_at->format('F j, Y') }}<br>
                    Status: {{ ucfirst($ticket->status) }}
                </div>
                <div class="footer">
                    Please present this ticket at the event<br>
                    <span style="font-size:0.95em;">Reception to follow</span>
                </div>
                <div class="qr">
                    @if($ticket->attendee->qr_code)
                        <img src="{{ $ticket->attendee->qr_code_url }}" alt="QR Code" style="max-width: 140px; height: auto; margin-top: 1em;">
                    @else
                        {!! QrCode::size(140)->generate(route('tickets.verify', $ticket->token)) !!}
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
            Print Ticket
        </button>
    </div>
</body>
</html> 