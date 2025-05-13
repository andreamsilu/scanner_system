<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCard;
use App\Models\ScanLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ScannerController extends Controller
{
    public function dashboard(): View
    {
        $today_scans = ScanLog::whereDate('created_at', today())->count();
        $total_scans = ScanLog::count();
        $active_events = Event::where('status', 'active')->get();
        $recent_scans = ScanLog::with(['attendee', 'event'])
            ->latest()
            ->take(5)
            ->get();
        $checked_in_today = ScanLog::whereDate('created_at', today())->count();

        return view('scanner.dashboard', compact(
            'today_scans',
            'total_scans',
            'active_events',
            'recent_scans',
            'checked_in_today'
        ));
    }

    public function selectEvent(Event $event): View
    {
        // Store selected event in session
        session(['selected_event_id' => $event->id]);
        
        return view('scanner.scan', compact('event'));
    }

    public function getSelectedEvent(): JsonResponse
    {
        $eventId = session('selected_event_id');
        if (!$eventId) {
            return response()->json([
                'success' => false,
                'message' => 'No event selected'
            ]);
        }

        $event = Event::find($eventId);
        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Selected event not found'
            ]);
        }

        return response()->json([
            'success' => true,
            'event' => $event
        ]);
    }

    public function validateToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        // Check if event is selected
        $eventId = session('selected_event_id');
        if (!$eventId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an event first'
            ]);
        }

        $eventCard = EventCard::where('token', $request->token)
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->where('is_used', false)
            ->first();

        if (!$eventCard) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code or QR code not for this event.'
            ]);
        }

        $scanLog = ScanLog::create([
            'event_id' => $eventCard->event_id,
            'attendee_id' => $eventCard->attendee_id,
            'event_card_id' => $eventCard->id,
            'status' => 'success',
            'scanned_by' => auth()->user()->email
        ]);

        $eventCard->markAsUsed();

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful!',
            'attendee' => $eventCard->attendee
        ]);
    }

    public function history(Request $request): View
    {
        $scans = ScanLog::with(['attendee', 'event'])
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15);

        $events = Event::where('status', 'active')->get();

        return view('scanner.history', compact('scans', 'events'));
    }

    public function exportHistory(Request $request)
    {
        $scans = ScanLog::with(['attendee', 'event'])
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->get();

        $headers = [
            'Date & Time',
            'Attendee',
            'Event',
            'Status',
            'Scanned By'
        ];

        $rows = $scans->map(fn($scan) => [
            $scan->created_at->format('M d, Y h:i A'),
            $scan->attendee->name,
            $scan->event->name,
            ucfirst($scan->status),
            $scan->scanned_by
        ]);

        $filename = 'scan-history-' . now()->format('Y-m-d') . '.csv';

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
} 