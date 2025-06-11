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
        .invitation-content .invitation-message {
            font-size: 1.1rem;
            font-style: italic;
            color: #bfa46d;
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
            color: #bfa46d;
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
            color: #7a6a4f;
        }
        .invitation-content .ticket-id {
            font-size: 1.1rem;
            letter-spacing: 0.1em;
            margin: 2em 0 0.5em 0;
            color: #bfa46d;
            font-family: monospace;
        }
        .invitation-content .qr {
            margin: 2em 0 0 0;
        }
        .invitation-content .footer {
            font-size: 1rem;
            font-style: italic;
            margin: 1.5em 0 0 0;
            color: #bfa46d;
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
                <div class="invitation-message">
                    <h2 style="color: #bfa46d; margin-bottom: 1.5em;">MWALIKO WA SENDOFF</h2>
                    <p style="margin-bottom: 1em; color: #000000;">
                        Familia ya MR & MRS  NDIMBO wa  Dar es Salaam,<br>
                        wanayo furaha kubwa kukualika / kuwaalika
                    </p>
                    <p style="margin-bottom: 1em; color: #000000;">
                        Ndg / Mhe / Dr / Prof / Mch / Bw / Bi / Bw & Bi
                    </p>
                    <div style="text-align: center; margin: 1em 0; min-height: 2.5em; border-bottom: 2px solid #bfa46d; padding: 0.5em; color: #000000;">
                        {{ strtoupper($ticket->attendee->name) }}
                    </div>
                    <div style="text-align: center; margin: 1em 0;">
                    </div>
                    <p style="margin-bottom: 1em; color: #000000;">
                        Kwenye sherehe ya SENDOFF ya binti yao mpendwa<br>
                        <strong style="color: #bfa46d; font-size: 1.2em;">YASINTA MOSES NDIMBO</strong>
                    </p>
                    <p style="margin-bottom: 1em; color: #000000;">
                        Itakayofanyika siku ya Alhamisi, tarehe 12-06-2025<br>
                        Kuanzia saa 12:00 jioni
                    </p>
                    <p style="margin-bottom: 1em; color: #000000;">
                        Katika ukumbi wa  Highland Hall,<br>
                        uliopo Dar es Salaam – Tabata, Barakuda.
                    </p>
                    <p style="margin-bottom: 1em; color: #000000;">
                        Karibu tusherehekee kwa pamoja tukio hili maalum la furaha na kumbukumbu ya pekee.
                    </p>
                </div>
                <div class="ticket-id" style="color: #bfa46d; font-weight: bold;">
                    Kitambulisho cha Tiketi: {{ $ticket->token }}
                </div>
                <div style="text-align: center; margin: 1em 0; color: #bfa46d; font-weight: bold;">
                    Aina ya Tiketi: {{ strtoupper($ticket->type) }}
                </div>
                <div class="qr">
                    @if($ticket->attendee->qr_code)
                        <img src="{{ $ticket->attendee->qr_code_url }}" alt="QR Code" style="max-width: 140px; height: auto; margin-top: 1em;">
                    @else
                        {!! QrCode::size(140)->generate(route('tickets.verify', $ticket->token)) !!}
                    @endif
                </div>
                <div class="footer" style="color: #000000;">
                    <p style="color: #bfa46d; font-weight: bold;">Tafadhali wasilisha tiketi hii mlangoni</p>
                    <p>Kwa maelezo zaidi tafadhali wasiliana na: 0769289824</p>
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