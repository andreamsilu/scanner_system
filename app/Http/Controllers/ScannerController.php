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
        
        // Get assigned events directly from the user's events relationship
        $assigned_events = $user->events()
            ->where('status', 'active')
            ->where(function($query) {
                $query->where('start_date', '>=', now())
                    ->orWhere('end_date', '>=', now());
            })
            ->with(['attendees' => function($query) {
                $query->with(['ticket' => function($q) {
                    $q->select('id', 'attendee_id', 'status');
                }])
                ->select('id', 'name', 'email', 'event_id', 'token');
            }])
            ->get();
        
        // Get scanner's statistics
        $stats = [
            'today_scans' => ScanLog::where('scanner_id', $user->id)
                ->whereDate('created_at', today())
                ->count(),
            'valid_scans' => ScanLog::where('scanner_id', $user->id)
                ->whereDate('created_at', today())
                ->where('status', 'success')
                ->count(),
            'invalid_scans' => ScanLog::where('scanner_id', $user->id)
                ->whereDate('created_at', today())
                ->whereIn('status', ['invalid', 'duplicate'])
                ->count(),
        ];

        // Get recent activity
        $recent_activity = ScanLog::with(['ticket.attendee', 'ticket.event'])
            ->where('scanner_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        return view('scanner.dashboard', compact('stats', 'assigned_events', 'recent_activity'));
    }

    /**
     * Select an event for scanning.
     *
     * @param Event $event
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function selectEvent(Event $event): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        
        // Check if user is authorized to scan this event
        if (!$user->hasRole('admin') && !$user->events()->where('events.id', $event->id)->exists()) {
            return redirect()->route('scanner.events.index')
                ->with('error', 'You are not authorized to scan this event.');
        }

        // Additional check to ensure event is active
        if ($event->status !== 'active') {
            return redirect()->route('scanner.events.index')
                ->with('error', 'This event is not active.');
        }

        // Check if event is within valid date range
        if ($event->end_date < now()) {
            return redirect()->route('scanner.events.index')
                ->with('error', 'This event has ended.');
        }

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
            ->where('scanner_id', $user->id)
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

    public function events(): View
    {
        $user = auth()->user();
        
        if ($user->hasRole('admin')) {
            // Admins can see all active events
            $events = Event::where('status', 'active')
                ->where(function($query) {
                    $query->where('start_date', '>=', now())
                        ->orWhere('end_date', '>=', now());
                })
                ->orderBy('start_date')
                ->paginate(10);
        } else {
            // Scanners only see their assigned events
            $events = $user->events()
                ->where('status', 'active')
                ->where(function($query) {
                    $query->where('start_date', '>=', now())
                        ->orWhere('end_date', '>=', now());
                })
                ->orderBy('start_date')
                ->paginate(10);
        }

        return view('scanner.events.index', compact('events'));
    }

    public function index(): View
    {
        $scanners = User::where('role', 'scanner')->paginate(10);
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
            'role' => 'scanner'
        ]);

        return redirect()->route('scanners.index')
            ->with('success', 'Scanner created successfully.');
    }

    public function show(User $scanner): View
    {
        if (!$scanner->isScanner()) {
            abort(404);
        }
        return view('scanners.show', compact('scanner'));
    }

    public function edit(User $scanner): View
    {
        if (!$scanner->isScanner()) {
            abort(404);
        }
        return view('scanners.edit', compact('scanner'));
    }

    public function update(Request $request, User $scanner)
    {
        if (!$scanner->isScanner()) {
            abort(404);
        }

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
        if (!$scanner->isScanner()) {
            abort(404);
        }

        $scanner->delete();

        return redirect()->route('scanners.index')
            ->with('success', 'Scanner deleted successfully.');
    }
} 