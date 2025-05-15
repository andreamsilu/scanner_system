<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCard;
use App\Models\ScanLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ScannerController extends Controller
{
    public function dashboard(): View
    {
        $user = auth()->user();
        $scanner = $user->scanner;
        
        $today_scans = ScanLog::whereDate('created_at', today())
            ->where('scanned_by', $user->email)
            ->count();
        
        $total_scans = ScanLog::where('scanned_by', $user->email)->count();
        
        $assigned_events = $scanner ? $scanner->events()
            ->where('status', 'active')
            ->where(function($query) {
                $query->where('start_date', '>=', now())
                    ->orWhere('end_date', '>=', now());
            })
            ->get() : collect();
        
        $recent_scans = ScanLog::with(['attendee', 'event'])
            ->where('scanned_by', $user->email)
            ->latest()
            ->take(5)
            ->get();
        
        $checked_in_today = ScanLog::whereDate('created_at', today())
            ->where('scanned_by', $user->email)
            ->count();

        return view('scanner.dashboard', compact(
            'today_scans',
            'total_scans',
            'assigned_events',
            'recent_scans',
            'checked_in_today'
        ));
    }

    public function selectEvent(Event $event): View
    {
        // Store selected event in session
        session(['selected_event_id' => $event->id]);
        
        return redirect()->route('scanner.scan.index', ['event' => $event->id]);
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

        $user = auth()->user();
        $scanner = $user->scanner;

        // Check if event is selected
        $eventId = session('selected_event_id');
        if (!$eventId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an event first'
            ]);
        }

        // Verify scanner is assigned to this event
        if (!$scanner || !$scanner->events()->where('events.id', $eventId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to scan for this event.'
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
            'scanned_by' => $user->email
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
        $user = auth()->user();
        $scanner = $user->scanner;
        
        $query = ScanLog::with(['attendee', 'event'])
            ->where('scanned_by', $user->email)
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->when($request->event_id, fn($q) => $q->where('event_id', $request->event_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        // If scanner is assigned to specific events, only show those events
        if ($scanner) {
            $query->whereIn('event_id', $scanner->events()->pluck('events.id'));
        }

        $scans = $query->latest()->paginate(15);
        $events = $scanner ? $scanner->events()->where('status', 'active')->get() : collect();

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

    public function index(): View
    {
        $scanners = User::role('scanner')->paginate(10);
        return view('scanners.index', compact('scanners'));
    }

    public function create(): View
    {
        return view('scanners.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole('scanner');

        return redirect()->route('scanners.index')
            ->with('success', 'Scanner created successfully.');
    }

    public function show(User $scanner): View
    {
        return view('scanners.show', compact('scanner'));
    }

    public function edit(User $scanner): View
    {
        return view('scanners.edit', compact('scanner'));
    }

    public function update(Request $request, User $scanner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $scanner->id,
        ]);

        $scanner->update($validated);

        return redirect()->route('scanners.index')
            ->with('success', 'Scanner updated successfully.');
    }

    public function destroy(User $scanner)
    {
        $scanner->delete();

        return redirect()->route('scanners.index')
            ->with('success', 'Scanner deleted successfully.');
    }

    public function events(): View
    {
        $user = auth()->user();
        $scanner = $user->scanner;
        
        $events = $scanner ? $scanner->events()
            ->where('status', 'active')
            ->where(function($query) {
                $query->where('start_date', '>=', now())
                    ->orWhere('end_date', '>=', now());
            })
            ->orderBy('start_date')
            ->paginate(10) : collect();

        return view('scanner.events.index', compact('events'));
    }
} 