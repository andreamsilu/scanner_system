<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sendoff Invitation - {{ $card->attendee->name }}</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: white;
        }
        .card {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 0 auto;
            position: relative;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .header h1 {
            color: #0d6efd;
            margin: 0;
            font-size: 36px;
            font-weight: bold;
        }
        .invitation-content {
            text-align: center;
            margin-bottom: 40px;
        }
        .invitation-content p {
            margin: 0 0 15px 0;
            font-size: 16px;
            line-height: 1.8;
        }
        .guest-name {
            margin: 40px 0;
        }
        .guest-name p {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .underline-text {
            border-bottom: 2px solid #dee2e6;
            min-height: 30px;
            padding: 5px;
            margin: 10px auto;
            max-width: 400px;
            font-size: 18px;
        }
        .event-details {
            margin-top: 30px;
        }
        .event-details strong {
            color: #0d6efd;
            font-size: 20px;
        }
        .qr-section {
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .qr-code {
            margin: 20px auto;
            width: 150px;
            height: 150px;
            padding: 10px;
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .ticket-info {
            margin-top: 15px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            display: inline-block;
        }
        .ticket-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .ticket-info small {
            color: #6c757d;
            display: block;
            margin-bottom: 2px;
        }
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status.confirmed {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .footer {
            position: absolute;
            bottom: 10mm;
            left: 20mm;
            right: 20mm;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
        }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .card {
                box-shadow: none;
            }
            .qr-section {
                position: fixed;
                bottom: 20mm;
            }
            .footer {
                position: fixed;
                bottom: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <!-- Header -->
        <div class="header">
            <h1>MWALIKO WA SENDOFF</h1>
        </div>

        <!-- Invitation Content -->
        <div class="invitation-content">
            <div>
                <p>
                    Familia ya MZEE PAUL MSILU wa Kigogo, Dar es Salaam,<br>
                    wanayo furaha kubwa kukualika / kuwaalika
                </p>
                <p>
                    Ndg / Mhe / Dr / Prof / Mch / Bw / Bi / Bw & Bi
                </p>
            </div>

            <!-- Guest Name -->
            <div class="guest-name">
                <p>Jina la Mwalikwa:</p>
                <div class="underline-text">
                    {{ $card->attendee->name }}
                </div>
            </div>

            <!-- Event Details -->
            <div class="event-details">
                <p>
                    Kwenye sherehe ya SENDOFF ya binti yao mpendwa<br>
                    <strong>ANITHA PAUL MSILU</strong>
                </p>
                <p>
                    Itakayofanyika siku ya Alhamisi, tarehe 05-06-2025<br>
                    Kuanzia saa 12:00 jioni
                </p>
                <p>
                    Katika ukumbi wa GBF Garden Hall,<br>
                    uliopo Dar es Salaam – Tabata, Barakuda.
                </p>
                <p>
                    Karibu tusherehekee kwa pamoja tukio hili maalum la furaha na kumbukumbu ya pekee.
                </p>
            </div>

            <!-- RSVP Section -->
            @if($card->attendee->event->rsvp_enabled && ($card->attendee->event->rsvp_option_1_phone || $card->attendee->event->rsvp_option_2_phone))
            <div style="margin-top: 40px; padding: 20px; border: 2px solid #0d6efd; border-radius: 8px; background-color: rgba(13, 110, 253, 0.05); text-align: center;">
                <h3 style="color: #0d6efd; margin-bottom: 15px; font-size: 20px;">RSVP - Tafadhali Jibu</h3>
                
                @if($card->attendee->event->rsvp_message)
                    <p style="margin-bottom: 15px; font-style: italic; color: #6c757d; font-size: 16px;">{{ $card->attendee->event->rsvp_message }}</p>
                @endif
                
                <div style="display: flex; justify-content: space-around; flex-wrap: wrap; gap: 20px;">
                    @if($card->attendee->event->rsvp_option_1_label && $card->attendee->event->rsvp_option_1_phone)
                    <div style="text-align: center; flex: 1; min-width: 200px;">
                        <div style="padding: 15px; border: 1px solid #dee2e6; border-radius: 8px; background-color: white;">
                            <div style="color: #0d6efd; font-weight: bold; margin-bottom: 8px; font-size: 16px;">{{ $card->attendee->event->rsvp_option_1_label }}</div>
                            <div style="font-family: monospace; font-size: 16px; color: #495057;">{{ $card->attendee->event->rsvp_option_1_phone }}</div>
                        </div>
                    </div>
                    @endif
                    
                    @if($card->attendee->event->rsvp_option_2_label && $card->attendee->event->rsvp_option_2_phone)
                    <div style="text-align: center; flex: 1; min-width: 200px;">
                        <div style="padding: 15px; border: 1px solid #dee2e6; border-radius: 8px; background-color: white;">
                            <div style="color: #0d6efd; font-weight: bold; margin-bottom: 8px; font-size: 16px;">{{ $card->attendee->event->rsvp_option_2_label }}</div>
                            <div style="font-family: monospace; font-size: 16px; color: #495057;">{{ $card->attendee->event->rsvp_option_2_phone }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- QR Code Section -->
        <div class="qr-section">
            <h3 style="color: #0d6efd; margin-bottom: 20px;">Entry Pass</h3>
            <!-- Ticket Content -->
            <div style="background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: left; font-size: 12px; line-height: 1.6;">
                <h5 style="color: #0d6efd; text-align: center; margin-bottom: 15px; font-weight: bold;">MWALIKO WA SENDOFF</h5>
                <p style="margin-bottom: 8px;">
                    Familia ya MZEE PAUL MSILU wa Kigogo, Dar es Salaam,<br>
                    wanayo furaha kubwa kukualika / kuwaalika
                </p>
                <p style="margin-bottom: 8px;">
                    Ndg / Mhe / Dr / Prof / Mch / Bw / Bi / Bw & Bi
                </p>
                <div style="text-align: center; margin-bottom: 8px;">
                    ..............................................................................................
                </div>
                <p style="margin-bottom: 8px;">
                    Kwenye sherehe ya SENDOFF ya binti yao mpendwa<br>
                    <strong>ANITHA PAUL MSILU</strong>
                </p>
                <p style="margin-bottom: 8px;">
                    Itakayofanyika siku ya Alhamisi, tarehe 05-06-2025<br>
                    Kuanzia saa 12:00 jioni
                </p>
                <p style="margin-bottom: 8px;">
                    Katika ukumbi wa GBF Garden Hall,<br>
                    uliopo Dar es Salaam – Tabata, Barakuda.
                </p>
                <p style="margin-bottom: 0;">
                    Karibu tusherehekee kwa pamoja tukio hili maalum la furaha na kumbukumbu ya pekee.
                </p>
            </div>
            <!-- QR Code -->
            <div class="qr-code">
                @php
                    $qrCode = QrCode::size(200)
                        ->format('png')
                        ->generate(route('event-cards.verify', $card->attendee->qr_token));
                    $base64 = base64_encode($qrCode);
                @endphp
                <img src="data:image/png;base64,{{ $base64 }}" alt="QR Code">
            </div>
            <p style="color: #6c757d; font-size: 14px; margin: 10px 0;">Scan this QR code at the venue</p>
            <div class="ticket-info">
                <p class="mb-0">
                    <small>Ticket ID</small>
                    <span style="font-weight: 500;">{{ $card->attendee->qr_token }}</span>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an official event invitation. Please present this QR code at the venue.</p>
            <p>Generated on {{ now()->format('F d, Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html> 