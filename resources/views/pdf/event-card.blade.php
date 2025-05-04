<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Event Pass - {{ $attendee->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .card {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            text-align: center;
        }
        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .info {
            margin-bottom: 20px;
            text-align: left;
        }
        .info p {
            margin: 5px 0;
        }
        .qr-code {
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="title">Event Pass</div>
        
        <div class="info">
            <p><strong>Name:</strong> {{ $attendee->name }}</p>
            <p><strong>Email:</strong> {{ $attendee->email }}</p>
            @if($attendee->phone)
                <p><strong>Phone:</strong> {{ $attendee->phone }}</p>
            @endif
        </div>

        <div class="qr-code">
            {!! $qrCode !!}
        </div>

        <div class="footer">
            <p>ID: {{ $attendee->id }}</p>
            <p>Token: {{ $attendee->qr_token }}</p>
        </div>
    </div>
</body>
</html> 