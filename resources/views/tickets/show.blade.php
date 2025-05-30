<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket - {{ $ticket->attendee->name }}</title>
    <meta property="og:title" content="Ticket for {{ $ticket->event->name }}">
    <meta property="og:description" content="Event ticket for {{ $ticket->attendee->name }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background: #fff;
        }
        .ticket {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #000;
            border-radius: 10px;
            padding: 20px;
            position: relative;
            background: #fff;
        }
        .ticket-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px dashed #000;
        }
        .event-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #000;
        }
        .event-details {
            font-size: 16px;
            color: #666;
            margin: 10px 0;
        }
        .ticket-content {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }
        .ticket-info {
            flex: 1;
            padding-right: 20px;
        }
        .qr-code {
            flex: 1;
            text-align: center;
        }
        .qr-code img {
            max-width: 200px;
            height: auto;
        }
        .ticket-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #000;
            font-size: 12px;
            color: #666;
        }
        .ticket-id {
            font-family: monospace;
            font-size: 18px;
            background: #f5f5f5;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
        }
        .attendee-info {
            margin: 20px 0;
        }
        .attendee-info p {
            margin: 5px 0;
        }
        .ticket-type {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 0;
        }
        .ticket-type.vip { background: #ffd700; }
        .ticket-type.regular { background: #e0e0e0; }
        .ticket-type.single { background: #90caf9; }
        .ticket-type.double { background: #a5d6a7; }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            color: rgba(0, 0, 0, 0.1);
            white-space: nowrap;
            pointer-events: none;
        }
        @media print {
            body {
                padding: 0;
            }
            .ticket {
                border: none;
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .action-button {
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            color: white;
        }
        .print-btn { background-color: #0d6efd; }
        .download-btn { background-color: #198754; }
        .share-btn { background-color: #6c757d; }
        .action-button:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .share-buttons {
            display: none;
            gap: 10px;
            margin-top: 15px;
            justify-content: center;
        }
        .share-buttons.active {
            display: flex;
        }
        .social-share-btn {
            padding: 8px 15px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .facebook { background-color: #1877f2; }
        .twitter { background-color: #1da1f2; }
        .whatsapp { background-color: #25d366; }
        .telegram { background-color: #0088cc; }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="ticket">
        <div class="watermark">EVENT TICKET</div>
        
        <div class="ticket-header">
            <h1 class="event-name">{{ $ticket->event->name }}</h1>
            <div class="event-details">
                <p>{{ $ticket->event->venue }}</p>
                <p>{{ $ticket->event->start_date->format('F j, Y g:i A') }}</p>
            </div>
        </div>

        <div class="welcome-message" style="text-align: center; margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 10px; border: 1px dashed #000;">
            <h3 style="color: #0d6efd; margin-bottom: 15px;">Karibu! (Welcome!)</h3>
            <p style="margin-bottom: 10px;">Mpendwa {{ $ticket->attendee->name }},</p>
            <p style="margin-bottom: 10px;">Tunafurahi kukualika kwenye {{ $ticket->event->name }}. Uwepo wako utafanya tukio hili liwe la kipekee zaidi.</p>
            <p style="margin-bottom: 15px;">Tafadhali wasilisha tiketi hii kwenye ukumbi kwa ajili ya kuingia.</p>
            <hr style="margin: 15px 0; border-top: 1px dashed #000;">
            <p style="font-style: italic; color: #666; margin: 0;">
                (We are delighted to invite you to {{ $ticket->event->name }}. Your presence will make this event even more special. Please present this ticket at the venue for entry.)
            </p>
        </div>

        <div class="ticket-content">
            <div class="ticket-info">
                <div class="attendee-info">
                    <h3>Attendee Information</h3>
                    <p><strong>Name:</strong> {{ $ticket->attendee->name }}</p>
                    <p><strong>Email:</strong> {{ $ticket->attendee->email }}</p>
                    @if($ticket->attendee->phone)
                        <p><strong>Phone:</strong> {{ $ticket->attendee->phone }}</p>
                    @endif
                </div>

                <div class="ticket-details">
                    <h3>Ticket Details</h3>
                    <p><strong>Ticket ID:</strong> <span class="ticket-id">{{ $ticket->attendee->token }}</span></p>
                    <p><strong>Type:</strong> <span class="ticket-type {{ $ticket->type }}">{{ ucfirst($ticket->type) }}</span></p>
                    <p><strong>Status:</strong> {{ ucfirst($ticket->status) }}</p>
                    <p><strong>Issued:</strong> {{ $ticket->created_at->format('F j, Y g:i A') }}</p>
                </div>
            </div>

            <div class="qr-code">
                @if($ticket->attendee->qr_code)
                    <img src="{{ $ticket->attendee->qr_code_url }}" 
                         alt="QR Code" 
                         style="max-width: 200px; height: auto;">
                    <p>Scan this QR code for check-in</p>
                @else
                    {!! QrCode::size(200)->generate(route('tickets.verify', $ticket->token)) !!}
                    <p>Scan this QR code for check-in</p>
                @endif
            </div>
        </div>

        <div class="ticket-footer">
            <p>This ticket is non-transferable and must be presented at the event.</p>
            <p>For any questions, please contact the event organizer.</p>
            <p>Printed on {{ now()->format('F j, Y g:i A') }}</p>
        </div>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <div class="action-buttons">
            <button onclick="window.print()" class="action-button print-btn">
                <i class="fas fa-print"></i> Print Ticket
            </button>
            <button onclick="downloadTicket()" class="action-button download-btn">
                <i class="fas fa-download"></i> Download PDF
            </button>
            <button onclick="toggleShareButtons()" class="action-button share-btn">
                <i class="fas fa-share-alt"></i> Share
            </button>
        </div>
        
        <div id="shareButtons" class="share-buttons">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('tickets.show', $ticket->token)) }}" 
               target="_blank" class="social-share-btn facebook">
                <i class="fab fa-facebook-f"></i> Facebook
            </a>
            <a href="https://twitter.com/intent/tweet?text={{ urlencode('Check out my ticket for ' . $ticket->event->name) }}&url={{ urlencode(route('tickets.show', $ticket->token)) }}" 
               target="_blank" class="social-share-btn twitter">
                <i class="fab fa-twitter"></i> Twitter
            </a>
            <a href="https://wa.me/?text={{ urlencode('Check out my ticket for ' . $ticket->event->name . ': ' . route('tickets.show', $ticket->token)) }}" 
               target="_blank" class="social-share-btn whatsapp">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
            <a href="https://t.me/share/url?url={{ urlencode(route('tickets.show', $ticket->token)) }}&text={{ urlencode('Check out my ticket for ' . $ticket->event->name) }}" 
               target="_blank" class="social-share-btn telegram">
                <i class="fab fa-telegram"></i> Telegram
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function toggleShareButtons() {
            const shareButtons = document.getElementById('shareButtons');
            shareButtons.classList.toggle('active');
        }

        function downloadTicket() {
            const element = document.querySelector('.ticket');
            const opt = {
                margin: 1,
                filename: 'ticket-{{ $ticket->token }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };

            // Remove watermark temporarily for PDF
            const watermark = document.querySelector('.watermark');
            if (watermark) watermark.style.display = 'none';

            html2pdf().set(opt).from(element).save().then(() => {
                // Restore watermark after PDF generation
                if (watermark) watermark.style.display = 'block';
            });
        }
    </script>
</body>
</html> 