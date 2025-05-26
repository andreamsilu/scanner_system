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
use Illuminate\Support\Facades\DB;

class ScannerController extends Controller
{
    /**
     * Display the scanner dashboard with assigned events and statistics
     * 
     * @return \Illuminate\View\View
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        
        // Get scanner's statistics using a dedicated method
        $stats = $this->getScannerStats($user);
        
        // Get all assigned events without any checks
        $assigned_events = $user->events()
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(function ($event) {
                // Calculate scan statistics for each event
                $event->scan_stats = [
                    'total_attendees' => $event->attendees->count(),
                    'scanned_count' => $event->attendees->filter(function ($attendee) {
                        return $attendee->ticket && $attendee->ticket->status === 'used';
                    })->count(),
                    'pending_count' => $event->attendees->filter(function ($attendee) {
                        return !$attendee->ticket || $attendee->ticket->status !== 'used';
                    })->count()
                ];
                return $event;
            });
        
        // Get recent activity with optimized query
        $recent_activity = ScanLog::with(['ticket.attendee', 'ticket.event'])
            ->where('scanner_id', $user->id)
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($scan) {
                return [
                    'time' => $scan->created_at->diffForHumans(),
                    'event' => $scan->ticket?->event?->name ?? 'N/A',
                    'attendee' => $scan->ticket?->attendee?->name ?? 'Invalid Ticket',
                    'status' => $scan->status,
                    'status_class' => $this->getStatusClass($scan->status)
                ];
            });

        return view('dashboard.scanner', compact('stats', 'assigned_events', 'recent_activity'));
    }

    /**
     * Get scanner statistics
     * 
     * @param User $scanner
     * @return array
     */
    private function getScannerStats(User $scanner): array
    {
        $today = now()->startOfDay();
        
        // Get total assigned events count
        $totalAssignedEvents = $scanner->events()->count();
        
        return [
            'today_scans' => ScanLog::where('scanner_id', $scanner->id)
                ->whereDate('created_at', $today)
                ->count(),
            'valid_scans' => ScanLog::where('scanner_id', $scanner->id)
                ->whereDate('created_at', $today)
                ->where('status', 'success')
                ->count(),
            'invalid_scans' => ScanLog::where('scanner_id', $scanner->id)
                ->whereDate('created_at', $today)
                ->whereIn('status', ['invalid', 'duplicate'])
                ->count(),
            'total_events' => $totalAssignedEvents
        ];
    }

    /**
     * Get status class for scan status
     * 
     * @param string $status
     * @return string
     */
    private function getStatusClass(string $status): string
    {
        return match($status) {
            'success' => 'success',
            'duplicate' => 'warning',
            default => 'danger'
        };
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

    /**
     * Deselect the currently selected event.
     *
     * @param Event $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deselectEvent(Event $event): \Illuminate\Http\RedirectResponse
    {
        // Verify this is the currently selected event
        if (session('selected_event_id') != $event->id) {
            return redirect()->route('scanner.dashboard')
                ->with('error', 'Invalid event selection.');
        }

        // Clear the selected event from session
        session()->forget('selected_event_id');
        
        return redirect()->route('scanner.dashboard')
            ->with('success', 'Event deselected. Please select a new event to start scanning.');
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
        
        // Check if event is selected
        $eventId = session('selected_event_id');
        if (!$eventId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an event first before scanning.',
                'code' => 'NO_EVENT_SELECTED',
                'redirect' => route('scanner.dashboard')
            ], 422);
        }

        // Get the selected event
        $event = Event::find($eventId);
        if (!$event) {
            session()->forget('selected_event_id');
            return response()->json([
                'success' => false,
                'message' => 'Selected event no longer exists. Please select a new event.',
                'code' => 'EVENT_NOT_FOUND',
                'redirect' => route('scanner.dashboard')
            ], 422);
        }

        // Verify scanner is authorized for this event
        if (!$user->hasRole('admin') && !$user->events()->where('events.id', $eventId)->exists()) {
            session()->forget('selected_event_id');
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to scan for this event. Please select an event you are assigned to.',
                'code' => 'UNAUTHORIZED_EVENT',
                'redirect' => route('scanner.dashboard')
            ], 403);
        }

        // Verify event is active
        if ($event->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This event is not active. Please select an active event.',
                'code' => 'EVENT_NOT_ACTIVE',
                'redirect' => route('scanner.dashboard')
            ], 422);
        }

        // Verify event is within valid date range
        if ($event->end_date < now()) {
            return response()->json([
                'success' => false,
                'message' => 'This event has ended. Please select an active event.',
                'code' => 'EVENT_ENDED',
                'redirect' => route('scanner.dashboard')
            ], 422);
        }

        // Validate the token
        $eventCard = EventCard::where('token', $request->token)
            ->where('event_id', $eventId)
            ->where('is_active', true)
            ->where('is_used', false)
            ->first();

        if (!$eventCard) {
            // Check if token exists but is already used
            $usedCard = EventCard::where('token', $request->token)
                ->where('event_id', $eventId)
                ->where('is_used', true)
                ->first();

            if ($usedCard) {
                return response()->json([
                    'success' => false,
                    'message' => 'This ticket has already been used.',
                    'code' => 'TICKET_USED'
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code or QR code not for this event.',
                'code' => 'INVALID_TICKET'
            ], 422);
        }

        // Create scan log
        $scanLog = ScanLog::create([
            'event_id' => $eventCard->event_id,
            'attendee_id' => $eventCard->attendee_id,
            'event_card_id' => $eventCard->id,
            'scanner_id' => $user->id,
            'status' => 'success',
            'scanned_by' => $user->email
        ]);

        // Mark ticket as used
        $eventCard->markAsUsed();

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful!',
            'attendee' => $eventCard->attendee,
            'scan' => $scanLog
        ]);
    }

    public function history(Request $request): View
    {
        $user = auth()->user();
        
        $query = ScanLog::with(['ticket.attendee', 'ticket.event'])
            ->where('scanner_id', $user->id)
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        // If scanner is assigned to specific events, only show those events
        if ($user->hasRole('scanner')) {
            $query->whereHas('ticket.event', function($q) use ($user) {
                $q->whereIn('events.id', $user->events()->pluck('events.id'));
            });
        }

        // Filter by event if specified
        if ($request->event_id) {
            $query->whereHas('ticket.event', function($q) use ($request) {
                $q->where('events.id', $request->event_id);
            });
        }

        $scans = $query->latest()->paginate(15);
        $events = $user->events()->where('status', 'active')->get();

        return view('scanner.history', compact('scans', 'events'));
    }

    public function exportHistory(Request $request)
    {
        $scans = ScanLog::with(['ticket.attendee', 'ticket.event'])
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->event_id, function($q) use ($request) {
                $q->whereHas('ticket.event', function($q) use ($request) {
                    $q->where('events.id', $request->event_id);
                });
            })
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
        
        // Get all assigned events with pagination
        $events = $user->events()
            ->orderBy('start_date', 'desc')
            ->paginate(9) // Show 9 events per page (3x3 grid)
            ->through(function ($event) {
                // Ensure dates are Carbon instances
                if (is_string($event->start_date)) {
                    $event->start_date = \Carbon\Carbon::parse($event->start_date);
                }
                if (is_string($event->end_date)) {
                    $event->end_date = \Carbon\Carbon::parse($event->end_date);
                }
                if (is_string($event->pivot->assigned_at)) {
                    $event->pivot->assigned_at = \Carbon\Carbon::parse($event->pivot->assigned_at);
                }

                // Calculate scan statistics for each event
                $event->scan_stats = [
                    'total_attendees' => $event->attendees->count(),
                    'scanned_count' => $event->attendees->filter(function ($attendee) {
                        return $attendee->ticket && $attendee->ticket->status === 'used';
                    })->count(),
                    'pending_count' => $event->attendees->filter(function ($attendee) {
                        return !$attendee->ticket || $attendee->ticket->status !== 'used';
                    })->count()
                ];
                return $event;
            });

        return view('scanner.events.index', compact('events'));
    }

    /**
     * Display a listing of scanners.
     */
    public function index(): View
    {
        $scanners = User::whereHas('roles', function($query) {
                $query->where('name', 'scanner');
            })
            ->with(['events' => function($query) {
                $query->where('status', 'active')
                    ->where(function($q) {
                        $q->where('start_date', '>=', now())
                            ->orWhere('end_date', '>=', now());
                    })
                    ->withPivot(['assigned_at', 'assigned_by'])
                    ->select(['events.id', 'events.name', 'events.start_date', 'events.end_date', 'events.location', 'events.status']);
            }])
            ->withCount(['events' => function($query) {
                $query->where('status', 'active')
                    ->where(function($q) {
                        $q->where('start_date', '>=', now())
                            ->orWhere('end_date', '>=', now());
                    });
            }])
            ->latest()
            ->paginate(10);

        // Load assigned events for each scanner and ensure dates are Carbon instances
        $scanners->getCollection()->transform(function ($scanner) {
            $scanner->assignedEvents = $scanner->events->map(function ($event) {
                // Ensure dates are Carbon instances
                if (is_string($event->start_date)) {
                    $event->start_date = \Carbon\Carbon::parse($event->start_date);
                }
                if (is_string($event->end_date)) {
                    $event->end_date = \Carbon\Carbon::parse($event->end_date);
                }
                if (is_string($event->pivot->assigned_at)) {
                    $event->pivot->assigned_at = \Carbon\Carbon::parse($event->pivot->assigned_at);
                }
                return $event;
            });
            return $scanner;
        });

        return view('scanners.index', compact('scanners'));
    }

    /**
     * Show the form for creating a new scanner.
     */
    public function create(): View
    {
        return view('scanners.create');
    }

    /**
     * Store a newly created scanner.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::transaction(function() use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole('scanner');
        });

        return redirect()->route('scanners.index')
            ->with('success', 'Scanner created successfully.');
    }

    /**
     * Display the specified scanner.
     */
    public function show(User $scanner): View
    {
        if (!$scanner->hasRole('scanner')) {
            abort(404);
        }

        $scanner->load(['events' => function($query) {
            $query->where('status', 'active')
                ->where(function($q) {
                    $q->where('start_date', '>=', now())
                        ->orWhere('end_date', '>=', now());
                });
        }]);

        $stats = [
            'total_scans' => ScanLog::where('scanner_id', $scanner->id)->count(),
            'today_scans' => ScanLog::where('scanner_id', $scanner->id)
                ->whereDate('created_at', today())
                ->count(),
            'valid_scans' => ScanLog::where('scanner_id', $scanner->id)
                ->where('status', 'success')
                ->count(),
            'invalid_scans' => ScanLog::where('scanner_id', $scanner->id)
                ->whereIn('status', ['invalid', 'duplicate'])
                ->count(),
        ];

        $recent_activity = ScanLog::with(['ticket.attendee', 'ticket.event'])
            ->where('scanner_id', $scanner->id)
            ->latest()
            ->take(10)
            ->get();

        return view('scanners.show', compact('scanner', 'stats', 'recent_activity'));
    }

    /**
     * Show the form for editing the specified scanner.
     */
    public function edit(User $scanner): View
    {
        if (!$scanner->hasRole('scanner')) {
            abort(404);
        }

        return view('scanners.edit', compact('scanner'));
    }

    /**
     * Update the specified scanner.
     */
    public function update(Request $request, User $scanner)
    {
        if (!$scanner->hasRole('scanner')) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $scanner->id,
            'is_active' => 'boolean',
        ]);

        $scanner->update($validated);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);

            $scanner->update([
                'password' => Hash::make($request->password),
            ]);
        }

        return redirect()->route('scanners.index')
            ->with('success', 'Scanner updated successfully.');
    }

    /**
     * Remove the specified scanner.
     */
    public function destroy(User $scanner)
    {
        if (!$scanner->hasRole('scanner')) {
            abort(404);
        }

        // Check if scanner has any scan logs
        if (ScanLog::where('scanner_id', $scanner->id)->exists()) {
            return redirect()->route('scanners.index')
                ->with('error', 'Cannot delete scanner with existing scan logs.');
        }

        DB::transaction(function() use ($scanner) {
            // Remove all event assignments
            $scanner->events()->detach();
            
            // Remove scanner role
            $scanner->removeRole('scanner');
            
            // Delete the user
            $scanner->delete();
        });

        return redirect()->route('scanners.index')
            ->with('success', 'Scanner deleted successfully.');
    }
} 