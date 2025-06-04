<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tickets List</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            color: #333;
        }
        .header p {
            font-size: 12px;
            color: #666;
            margin: 5px 0 0;
        }
        .filters {
            margin: 10px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filters p {
            margin: 5px 0;
            font-size: 11px;
            color: #666;
        }
        .filters strong {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            color: #666;
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tickets List</h1>
        <p>Generated on: {{ $generatedAt }}</p>
    </div>

    @if(isset($filters) && (isset($filters['event']) || isset($filters['type']) || isset($filters['date_from']) || isset($filters['date_to']) || isset($filters['status'])))
    <div class="filters">
        <p><strong>Applied Filters:</strong></p>
        @if(isset($filters['event']) && $filters['event'])
            <p><strong>Event:</strong> {{ $filters['event'] }}</p>
        @endif
        @if(isset($filters['type']) && $filters['type'])
            <p><strong>Ticket Type:</strong> {{ ucfirst($filters['type']) }}</p>
        @endif
        @if(isset($filters['date_from']) && $filters['date_from'])
            <p><strong>From Date:</strong> {{ date('d/m/Y', strtotime($filters['date_from'])) }}</p>
        @endif
        @if(isset($filters['date_to']) && $filters['date_to'])
            <p><strong>To Date:</strong> {{ date('d/m/Y', strtotime($filters['date_to'])) }}</p>
        @endif
        @if(isset($filters['status']) && $filters['status'])
            <p><strong>Status:</strong> {{ ucfirst($filters['status']) }}</p>
        @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Attendee Name</th>
                <th>Token</th>
                <th>Ticket Type</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tickets as $ticket)
                <tr>
                    <td>{{ $ticket->attendee_name ?? 'Unassigned' }}</td>
                    <td>{{ $ticket->token }}</td>
                    <td>{{ ucfirst($ticket->type) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Page {PAGE_NUM} of {PAGE_COUNT}</p>
        <p>This document was generated from the Scannable Event System</p>
    </div>
</body>
</html> 