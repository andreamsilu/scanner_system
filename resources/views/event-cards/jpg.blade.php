<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event Card - {{ $attendee->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: white;
            width: 800px;
            height: 1200px;
            position: relative;
        }
        .card {
            width: 100%;
            height: 100%;
            border: 3px solid #0d6efd;
            border-radius: 12px;
            padding: 40px;
            box-sizing: border-box;
            text-align: center;
            position: relative;
        }
        .header {
            margin-bottom: 40px;
        }
        .header h1 {
            color: #0d6efd;
            margin: 0;
            font-size: 36px;
            font-weight: bold;
        }
        .invitation-content {
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
        .guest-name .name {
            font-size: 24px;
            font-weight: bold;
            color: #0d6efd;
            border-bottom: 2px solid #dee2e6;
            padding: 10px;
            margin: 10px auto;
            max-width: 400px;
        }
        .event-details {
            margin-top: 30px;
        }
        .event-details strong {
            color: #0d6efd;
            font-size: 20px;
        }
        .rsvp-section {
            margin-top: 40px;
            padding: 20px;
            border: 2px solid #0d6efd;
            border-radius: 8px;
            background-color: rgba(13, 110, 253, 0.05);
        }
        .rsvp-section h3 {
            color: #0d6efd;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .rsvp-options {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }
        .rsvp-option {
            text-align: center;
            flex: 1;
            min-width: 200px;
        }
        .rsvp-option .label {
            color: #0d6efd;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .rsvp-option .phone {
            font-family: monospace;
            font-size: 16px;
            color: #495057;
        }
        .footer {
            position: absolute;
            bottom: 40px;
            left: 40px;
            right: 40px;
            text-align: center;
            color: #0d6efd;
            font-size: 14px;
            font-weight: bold;
        }
        .qr-code {
            position: absolute;
            bottom: 40px;
            right: 40px;
            width: 150px;
            height: 150px;
            padding: 10px;
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
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
                    Familia ya MR & MRS PIENZI CHAMBILA wa Kigogo, Dar es Salaam,<br>
                    wanayo furaha kubwa kukualika / kuwaalika
                </p>
                <p>
                    Ndg / Mhe / Dr / Prof / Mch / Bw / Bi / Bw & Bi
                </p>
            </div>

            <!-- Guest Name -->
            <div class="guest-name">
                <p>Jina la Mwalikwa:</p>
                <div class="name">
                    {{ strtoupper($attendee->name) }}
                </div>
            </div>

            <!-- Event Details -->
            <div class="event-details">
                <p>
                    Kwenye sherehe ya SENDOFF ya binti yao mpendwa<br>
                    <strong>ALICE PIENZI CHAMBILA</strong>
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
                    Karibu tusherehekee kwa pamoja tukio hili maalum<br>
                    la furaha na kumbukumbu ya pekee.
                </p>
            </div>

            <!-- RSVP Section -->
            @if($attendee->event->rsvp_enabled && ($attendee->event->rsvp_option_1_phone || $attendee->event->rsvp_option_2_phone))
            <div class="rsvp-section">
                <h3>RSVP - Tafadhali Jibu</h3>
                
                @if($attendee->event->rsvp_message)
                    <p style="font-style: italic; color: #6c757d; margin-bottom: 15px;">{{ $attendee->event->rsvp_message }}</p>
                @endif
                
                <div class="rsvp-options">
                    @if($attendee->event->rsvp_option_1_label && $attendee->event->rsvp_option_1_phone)
                    <div class="rsvp-option">
                        <div class="label">{{ $attendee->event->rsvp_option_1_label }}</div>
                        <div class="phone">{{ $attendee->event->rsvp_option_1_phone }}</div>
                    </div>
                    @endif
                    
                    @if($attendee->event->rsvp_option_2_label && $attendee->event->rsvp_option_2_phone)
                    <div class="rsvp-option">
                        <div class="label">{{ $attendee->event->rsvp_option_2_label }}</div>
                        <div class="phone">{{ $attendee->event->rsvp_option_2_phone }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- QR Code -->
        <div class="qr-code">
            {!! QrCode::size(130)->format('svg')->generate(route('event-cards.verify', $attendee->qr_token)) !!}
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Tafadhali wasilisha tiketi hii mlangoni</p>
        </div>
    </div>
</body>
</html> 