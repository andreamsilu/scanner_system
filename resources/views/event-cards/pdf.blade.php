<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event Card - {{ $card->attendee->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #0d6efd;
            margin-bottom: 10px;
        }
        .attendee-info {
            display: flex;
            margin-bottom: 30px;
        }
        .avatar {
            width: 100px;
            height: 100px;
            background-color: #0d6efd;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-right: 20px;
        }
        .details {
            flex: 1;
        }
        .details h2 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .info-row {
            margin-bottom: 5px;
            color: #666;
        }
        .qr-section {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            border-top: 1px solid #ddd;
        }
        .qr-code {
            margin: 20px auto;
            max-width: 200px;
        }
        .qr-code img {
            width: 100%;
            height: auto;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>Event Invitation</h1>
            <p>You are cordially invited to attend</p>
        </div>

        <div class="attendee-info">
            <div class="avatar">
                {{ strtoupper(substr($card->attendee->name, 0, 1)) }}
            </div>
            <div class="details">
                <h2>{{ $card->attendee->name }}</h2>
                <div class="info-row">
                    <strong>Position:</strong> {{ $card->attendee->position ?? 'Not specified' }}
                </div>
                <div class="info-row">
                    <strong>Company:</strong> {{ $card->attendee->company ?? 'Not specified' }}
                </div>
                <div class="info-row">
                    <strong>Email:</strong> {{ $card->attendee->email }}
                </div>
                <div class="info-row">
                    <strong>Phone:</strong> {{ $card->attendee->phone ?? 'Not provided' }}
                </div>
            </div>
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
            <p><strong>Ticket ID:</strong> {{ $card->attendee->qr_token }}</p>
        </div>

        <div class="footer">
            <p>This is an official event invitation. Please present this QR code at the venue.</p>
            <p>Generated on {{ now()->format('F d, Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html> 