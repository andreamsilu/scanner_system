<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sendoff Invitation - {{ $attendee->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .card {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background-color: white;
            border: 2px solid #0d6efd;
            border-radius: 15px;
            position: relative;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 1px dashed #0d6efd;
            border-radius: 10px;
            pointer-events: none;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #0d6efd;
        }
        .title {
            font-size: 28px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 16px;
            color: #6c757d;
        }
        .guest-info {
            margin-bottom: 30px;
            text-align: center;
        }
        .guest-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .guest-position {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .guest-company {
            font-size: 16px;
            color: #6c757d;
            margin-bottom: 20px;
        }
        .details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        .details-column {
            flex: 1;
            padding: 0 15px;
        }
        .details-title {
            font-size: 16px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 15px;
        }
        .details-item {
            margin-bottom: 10px;
        }
        .details-label {
            font-weight: bold;
            color: #495057;
        }
        .qr-section {
            text-align: center;
            margin: 40px 0;
            padding: 30px;
            background: linear-gradient(145deg, #f8f9fa, #ffffff);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .qr-container {
            display: inline-block;
            padding: 25px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(13, 110, 253, 0.1);
            position: relative;
        }
        .qr-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border: 2px solid #0d6efd;
            border-radius: 15px;
            opacity: 0.2;
        }
        .qr-code {
            margin: 0 auto;
            padding: 15px;
            background-color: white;
            border-radius: 10px;
            display: inline-block;
        }
        .qr-text {
            margin-top: 20px;
            font-size: 16px;
            color: #0d6efd;
            font-weight: 500;
        }
        .qr-subtext {
            margin-top: 8px;
            font-size: 14px;
            color: #6c757d;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #0d6efd;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="title">Sendoff Invitation</div>
            <div class="subtitle">You are cordially invited to attend</div>
        </div>
        
        <div class="guest-info">
            <div class="guest-name">{{ $attendee->name }}</div>
            @if($attendee->position)
                <div class="guest-position">{{ $attendee->position }}</div>
            @endif
            @if($attendee->company)
                <div class="guest-company">{{ $attendee->company }}</div>
            @endif
        </div>

        <div class="details">
            <div class="details-column">
                <div class="details-title">Contact Information</div>
                <div class="details-item">
                    <div class="details-label">Email</div>
                    <div>{{ $attendee->email }}</div>
                </div>
                @if($attendee->phone)
                    <div class="details-item">
                        <div class="details-label">Phone</div>
                        <div>{{ $attendee->phone }}</div>
                    </div>
                @endif
            </div>
            <div class="details-column">
                <div class="details-title">Event Details</div>
                <div class="details-item">
                    <div class="details-label">Venue</div>
                    <div>Main Conference Hall</div>
                </div>
                <div class="details-item">
                    <div class="details-label">Date & Time</div>
                    <div>2:00 PM - 5:00 PM</div>
                </div>
            </div>
        </div>

        <div class="qr-section">
            <div class="qr-container">
                <div class="qr-code">
                    @if(isset($qrCode))
                        {!! $qrCode !!}
                    @else
                        <div style="width: 200px; height: 200px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                            QR Code not available
                        </div>
                    @endif
                </div>
            </div>
            <div class="qr-text">Entry Pass QR Code</div>
            <div class="qr-subtext">Please scan this code at the venue entrance for verification</div>
        </div>

        <div class="footer">
            <div>Ticket ID: {{ $attendee->qr_token }}</div>
            <div>Please arrive 15 minutes before the event</div>
        </div>
    </div>
</body>
</html> 