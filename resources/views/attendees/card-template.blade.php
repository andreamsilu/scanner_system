<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendee Card - {{ $attendee->name }}</title>
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
    {!! $event->cardTemplate->render($templateData) !!}

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px;">
            Print Card
        </button>
    </div>
</body>
</html> 