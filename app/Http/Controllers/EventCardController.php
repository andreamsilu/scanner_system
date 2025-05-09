<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Event;

class EventCardController extends Controller
{
    public function download(Attendee $attendee)
    {
        $qrCode = QrCode::size(200)
            ->format('svg')
            ->generate($attendee->qr_token);

        $pdf = PDF::loadView('pdf.event-card', [
            'attendee' => $attendee,
            'qrCode' => $qrCode,
        ]);

        return $pdf->download("event-card-{$attendee->id}.pdf");
    }

    /**
     * Display the attendees list for an event.
     *
     * @param  \App\Models\Event  $event
     * @return \Illuminate\View\View
     */
    public function attendees(Event $event)
    {
        $this->authorize('view', $event);

        $attendees = $event->attendees()
            ->with(['ticketType', 'status'])
            ->latest()
            ->paginate(10);

        $companies = $event->attendees()
            ->distinct()
            ->pluck('company')
            ->filter()
            ->values();

        return view('event-cards.attendees', compact('event', 'attendees', 'companies'));
    }
} 