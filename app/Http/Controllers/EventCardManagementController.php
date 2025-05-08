<?php

namespace App\Http\Controllers;

use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\Attendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PDF;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EventCardManagementController extends Controller
{
    public function index(): View
    {
        $attendees = Attendee::with('event')->latest()->paginate(10);
        return view('event-cards.index', compact('attendees'));
    }

    public function create(Request $request): View
    {
        $eventId = $request->query('event');
        $event = Event::findOrFail($eventId);
        return view('event-cards.create', compact('event'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:attendees,email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        $attendee = Attendee::create($validated);

        return redirect()
            ->route('event-cards.show', $attendee)
            ->with('success', 'Event card created successfully.');
    }

    public function show(Attendee $attendee): View
    {
        return view('event-cards.show', compact('attendee'));
    }

    public function edit(Attendee $attendee): View
    {
        return view('event-cards.edit', compact('attendee'));
    }

    public function update(Request $request, Attendee $attendee): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:attendees,email,' . $attendee->id,
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        $attendee->update($validated);

        return redirect()
            ->route('event-cards.show', $attendee)
            ->with('success', 'Event card updated successfully.');
    }

    public function destroy(Attendee $attendee): RedirectResponse
    {
        $attendee->delete();
        return redirect()
            ->route('event-cards.index')
            ->with('success', 'Event card deleted successfully.');
    }

    public function download(Attendee $attendee)
    {
        // Generate QR code with SVG format
        $qrCode = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->generate(route('event-cards.verify', $attendee->qr_token));

        $pdf = PDF::loadView('pdf.event-card', [
            'attendee' => $attendee,
            'qrCode' => $qrCode
        ]);
        
        return $pdf->download("event-card-{$attendee->qr_token}.pdf");
    }

    public function verify(string $token): View
    {
        $attendee = Attendee::where('qr_token', $token)->firstOrFail();
        return view('event-cards.verify', compact('attendee'));
    }
} 