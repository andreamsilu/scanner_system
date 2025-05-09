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

        return view('scanner.dashboard', compact(
            'today_scans',
            'total_scans',
            'active_events',
            'recent_scans'
        ));
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

    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $eventCard = EventCard::where('token', $request->token)
            ->where('is_active', true)
            ->where('is_used', false)
            ->first();

        if (!$eventCard) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or already used QR code.'
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
            'message' => 'Check-in successful!'
        ]);
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