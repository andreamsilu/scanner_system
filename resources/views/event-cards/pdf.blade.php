<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sendoff Invitation - {{ $card->attendee->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .header h1 {
            color: #0d6efd;
            margin-bottom: 20px;
            font-size: 32px;
            font-weight: bold;
        }
        .invitation-content {
            text-align: center;
            margin-bottom: 40px;
            font-size: 16px;
            line-height: 1.8;
        }
        .invitation-content p {
            margin-bottom: 15px;
        }
        .guest-name {
            margin: 30px 0;
            font-size: 18px;
        }
        .underline-text {
            border-bottom: 1px solid #dee2e6;
            min-height: 30px;
            padding: 5px;
            margin: 10px 0;
        }
        .qr-section {
            text-align: center;
            margin-top: 40px;
            padding: 30px;
            border-top: 1px solid #ddd;
        }
        .qr-code {
            margin: 20px auto;
            max-width: 200px;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .qr-code img {
            width: 100%;
            height: auto;
        }
        .ticket-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #666;
            font-size: 12px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
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
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>MWALIKO WA SENDOFF</h1>
        </div>

        <div class="invitation-content">
            <p>
                Familia ya MR & MRS PIENZI CHAMBILA wa Kigogo, Dar es Salaam,<br>
                wanayo furaha kubwa kukualika / kuwaalika
            </p>
            <p>
                Ndg / Mhe / Dr / Prof / Mch / Bw / Bi / Bw & Bi
            </p>
            <div class="guest-name">
                <p>Jina la Mwalikwa:</p>
                <div class="underline-text">
                    {{ $card->attendee->name }}
                </div>
            </div>
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
                Karibu tusherehekee kwa pamoja tukio hili maalum la furaha na kumbukumbu ya pekee.
            </p>
        </div>

        <div class="qr-section">
            <h3>Entry Pass</h3>
            <div class="qr-code">
                @php
                    $qrCode = QrCode::size(200)
                        ->format('png')
                        ->generate(route('event-cards.verify', $card->attendee->qr_token));
                    $base64 = base64_encode($qrCode);
                @endphp
                <img src="data:image/png;base64,{{ $base64 }}" alt="QR Code">
            </div>
            <p>Scan this QR code at the venue</p>
            <div class="ticket-info">
                <p><strong>Ticket ID:</strong> {{ $card->attendee->qr_token }}</p>
                <p><strong>Status:</strong> 
                    @if($card->attendee->scanned)
                        <span class="status confirmed">Confirmed</span>
                    @else
                        <span class="status pending">Pending</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="footer">
            <p>This is an official event invitation. Please present this QR code at the venue.</p>
            <p>Generated on {{ now()->format('F d, Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html> 