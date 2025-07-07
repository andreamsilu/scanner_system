<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Ticket - {{ $ticket->attendee->name }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }
        .invitation-border {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
        }
        .border-pattern {
            border: 3px solid #D2691E;
            border-radius: 8px;
            padding: 36px 32px 32px 32px;
            margin: 0;
            position: relative;
        }
        .border-pattern:before, .border-pattern:after {
            content: '';
            position: absolute;
            left: 0; right: 0;
            height: 12px;
            background: repeating-linear-gradient(90deg, #D2691E, #D2691E 2px, transparent 2px, transparent 16px);
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
            color: #5A6B47;
        }
        .invitation-content .invitation-message {
            font-size: 1.1rem;
            font-style: italic;
            color: #5A6B47;
            margin-bottom: 2em;
        }
        .invitation-content .names {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            margin: 0.7em 0 0.2em 0;
        }
        .invitation-content .is-invited {
            font-size: 1.1rem;
            font-style: italic;
            color: #5A6B47;
            margin: 0.2em 0 0.2em 0;
        }
        .invitation-content .event-name {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            margin: 0.5em 0 1.2em 0;
        }
        .invitation-content .event-details {
            font-size: 1.1rem;
            margin: 1.5em 0 0.5em 0;
            color: #5A6B47;
        }
        .invitation-content .ticket-id {
            font-size: 1.1rem;
            letter-spacing: 0.1em;
            margin: 2em 0 0.5em 0;
            color: #D2691E;
            font-family: monospace;
        }
        .invitation-content .qr {
            margin: 2em 0 0 0;
        }
        .invitation-content .footer {
            font-size: 1rem;
            font-style: italic;
            margin: 1.5em 0 0 0;
            color: #5A6B47;
        }
        @media print {
            body {
                background: #ffffff;
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
                <div class="invitation-message">
                    <h2 style="color: #9CAF88; margin-bottom: 1.5em;">MWALIKO WA SENDOFF</h2>
                    <p style="margin-bottom: 1em; color: #5A6B47;">
                        Familia ya MR & MRS PIENZI CHAMBILA<br>
                        wanayo furaha kukualika
                    </p>
                    <p style="margin-bottom: 1em; color: #5A6B47; font-weight: bold;">
                        Kwa: {{ strtoupper($ticket->attendee->name) }}
                    </p>
                    <hr style="border: none; border-top: 1.5px dotted #9CAF88; margin: 1.5em 0;">
                    <p style="margin-bottom: 1em; color: #5A6B47;">
                        Kwenye sherehe ya SENDOFF ya<br>
                        <strong style="color: #D2691E; font-size: 1.5em; letter-spacing: 0.04em;">ALICE PIENZI CHAMBILA</strong>
                    </p>
                    <p style="margin-bottom: 1em; color: #5A6B47;">
                        <strong>Tarehe:</strong> Alhamisi, 05-06-2025<br>
                        <strong>Saa:</strong> 12:00 jioni<br>
                        <strong>Mahali:</strong> GBF Garden Hall, Dar es Salaam
                    </p>
                </div>
                <div class="ticket-id" style="color: #D2691E; font-weight: bold;">
                    Tiketi: {{ $ticket->token }}
                </div>
                <div style="text-align: center; margin: 1em 0; color: #D2691E; font-weight: bold;">
                    Aina: {{ strtoupper($ticket->type) }}
                </div>
                <div class="qr">
                    @if($ticket->attendee->qr_code)
                        <img src="{{ $ticket->attendee->qr_code_url }}" alt="QR Code" style="max-width: 140px; height: auto; margin-top: 1em;">
                    @else
                        {!! QrCode::size(140)->generate(route('tickets.verify', $ticket->token)) !!}
                    @endif
                </div>
                <!-- RSVP Section -->
                @if($ticket->event->rsvp_enabled && ($ticket->event->rsvp_option_1_phone || $ticket->event->rsvp_option_2_phone))
                <div style="margin: 2em 0; padding: 0.7em; border: 2px solid #D2691E; border-radius: 8px; background: rgba(156, 175, 136, 0.1);">
                    <h4 style="color: #D2691E; margin-bottom: 1em; font-size: 1.2em;">RSVP</h4>
                    
                    @if($ticket->event->rsvp_message)
                        <p style="margin-bottom: 1em; color: #5A6B47; font-style: italic;">{{ $ticket->event->rsvp_message }}</p>
                    @endif
                    
                    <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 1em;">
                        @if($ticket->event->rsvp_option_1_label && $ticket->event->rsvp_option_1_phone)
                        <div style="text-align: center;">
                            <div style="color: #D2691E; font-weight: bold; margin-bottom: 0.5em;">{{ $ticket->event->rsvp_option_1_label }}</div>
                            <div style="color: #5A6B47; font-family: monospace; font-size: 1.1em; display: flex; align-items: center; justify-content: center;">
                                <span style='font-size:1.1em; margin-right: 0.3em;'>&#128222;</span>
                                <a href="tel:{{ $ticket->event->rsvp_option_1_phone }}" style="color: #5A6B47; text-decoration: none;">{{ $ticket->event->rsvp_option_1_phone }}</a>
                            </div>
                        </div>
                        @endif
                        
                        @if($ticket->event->rsvp_option_2_label && $ticket->event->rsvp_option_2_phone)
                        <div style="text-align: center;">
                            <div style="color: #D2691E; font-weight: bold; margin-bottom: 0.5em;">{{ $ticket->event->rsvp_option_2_label }}</div>
                            <div style="color: #5A6B47; font-family: monospace; font-size: 1.1em; display: flex; align-items: center; justify-content: center;">
                                <span style='font-size:1.1em; margin-right: 0.3em;'>&#128222;</span>
                                <a href="tel:{{ $ticket->event->rsvp_option_2_phone }}" style="color: #5A6B47; text-decoration: none;">{{ $ticket->event->rsvp_option_2_phone }}</a>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <div class="footer" style="color: #5A6B47;">
                    <p style="color: #D2691E; font-weight: bold;">Wasilisha tiketi hii mlangoni</p>
                    <p>Maelezo: 0769289824</p>
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