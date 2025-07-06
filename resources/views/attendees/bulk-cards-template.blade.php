<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendee Cards - {{ $event->name }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
        }
        .card-page {
            page-break-after: always;
            padding: 20px;
        }
        .card-page:last-child {
            page-break-after: avoid;
        }
        @media print {
            body {
                background: #fff;
            }
        }
    </style>
</head>
<body>
    @foreach($attendees as $attendee)
        <div class="card-page">
            @php
                $templateData = [
                    'event_name' => $event->name,
                    'event_date' => $event->start_date->format('F j, Y'),
                    'event_time' => $event->start_date->format('g:i A'),
                    'event_venue' => $event->venue,
                    'attendee_name' => $attendee->name,
                    'attendee_email' => $attendee->email,
                    'attendee_phone' => $attendee->phone,
                    'attendee_company' => $attendee->company,
                    'attendee_position' => $attendee->position,
                    'attendee_status' => $attendee->registration_status,
                    'ticket_id' => $attendee->token
                ];
            @endphp
            
            {!! $event->cardTemplate->render($templateData) !!}
        </div>
    @endforeach
</body>
</html> 