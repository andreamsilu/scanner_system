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
use App\Models\Attendee;

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
        
        // Get all assigned events and ensure dates are Carbon instances
        $assigned_events = $user->events()
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(function ($event) {
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

    /**
     * Display the scanning interface for QR code scanning.
     * 
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function scan(): View|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        $eventId = session('selected_event_id');
        
        // If no event is selected, redirect to events list
        if (!$eventId) {
            return redirect()->route('scanner.events.index')
                ->with('error', 'Please select an event first before scanning.');
        }

        // Get the selected event
        $event = Event::find($eventId);
        if (!$event) {
            session()->forget('selected_event_id');
            return redirect()->route('scanner.events.index')
                ->with('error', 'Selected event no longer exists. Please select a new event.');
        }

        // Verify scanner is authorized for this event
        if (!$user->hasRole('admin') && !$user->events()->where('events.id', $eventId)->exists()) {
            session()->forget('selected_event_id');
            return redirect()->route('scanner.events.index')
                ->with('error', 'You are not authorized to scan for this event. Please select an event you are assigned to.');
        }

        // Get recent scans for this event
        $recent_scans = ScanLog::with(['ticket.attendee', 'scanner'])
            ->whereHas('ticket', function($query) use ($eventId) {
                $query->where('event_id', $eventId);
            })
            ->latest()
            ->take(10)
            ->get()
            ->map(function($scan) {
                return [
                    'time' => $scan->created_at->diffForHumans(),
                    'attendee' => $scan->ticket?->attendee?->name ?? 'Invalid Ticket',
                    'scanner' => $scan->scanner?->name ?? 'Unknown',
                    'status' => $scan->status,
                    'status_class' => match($scan->status) {
                        'success' => 'success',
                        'duplicate' => 'warning',
                        default => 'danger'
                    }
                ];
            });

        return view('scanner.scan.index', compact('event', 'recent_scans'));
    }

    /**
     * Display the manual token validation interface.
     * 
     * @return \Illuminate\View\View
     */
    public function validatePage(): View
    {
        $user = auth()->user();
        $eventId = session('selected_event_id');
        
        // Get the selected event if any
        $event = null;
        if ($eventId) {
            $event = Event::find($eventId);
            if (!$event) {
                session()->forget('selected_event_id');
            }
        }
        
        // Get all assigned events with their statistics
        $events = $user->events()
            ->withCount(['attendees' => function($query) {
                $query->whereHas('ticket', function($q) {
                    $q->where('status', 'used');
                });
            }, 'attendees as scanned_count'])
            ->orderBy('start_date', 'desc')
            ->get();

        // Get recent validations for the selected event
        $recent_validations = [];
        if ($eventId && $event) {
            $recent_validations = ScanLog::with(['ticket.attendee', 'scanner'])
                ->whereHas('ticket', function($query) use ($eventId) {
                    $query->where('event_id', $eventId);
                })
                ->where('notes', 'like', '%Manual token validation%')
                ->latest()
                ->take(10)
                ->get()
                ->map(function($scan) {
                    return [
                        'time' => $scan->created_at->diffForHumans(),
                        'attendee' => $scan->ticket?->attendee?->name ?? 'Invalid Ticket',
                        'email' => $scan->ticket?->attendee?->email,
                        'status' => $scan->status,
                        'status_class' => match($scan->status) {
                            'success' => 'success',
                            'duplicate' => 'warning',
                            default => 'danger'
                        },
                        'scanner' => $scan->scanner?->name
                    ];
                });
        }

        return view('scanner.validate.index', compact('events', 'event', 'recent_validations'));
    }

    /**
     * Handle manual token validation.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateManualToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $user = auth()->user();
        $eventId = session('selected_event_id');
        
        // Check if event is selected
        if (!$eventId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an event first before validating tokens.',
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
                'message' => 'You are not authorized to validate tokens for this event.',
                'code' => 'UNAUTHORIZED_EVENT',
                'redirect' => route('scanner.dashboard')
            ], 403);
        }

        // Find the attendee by token
        $attendee = Attendee::where('token', $request->token)
            ->where('event_id', $eventId)
            ->with(['ticket'])
            ->first();

        if (!$attendee) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token or token not for this event.',
                'code' => 'INVALID_TOKEN'
            ], 422);
        }

        // Check if ticket is already used
        if ($attendee->ticket && $attendee->ticket->status === 'used') {
            // Log the duplicate validation attempt
            ScanLog::create([
                'ticket_id' => $attendee->ticket->id,
                'scanner_id' => $user->id,
                'status' => 'duplicate',
                'notes' => 'Manual token validation - Duplicate scan'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'This ticket has already been used.',
                'code' => 'TICKET_USED',
                'attendee' => $attendee->name
            ], 422);
        }

        // Update ticket status if exists
        if ($attendee->ticket) {
            DB::transaction(function() use ($attendee, $user) {
                $attendee->ticket->update([
                    'status' => 'used',
                    'used_at' => now()
                ]);

                // Log the successful validation
                ScanLog::create([
                    'ticket_id' => $attendee->ticket->id,
                    'scanner_id' => $user->id,
                    'status' => 'success',
                    'notes' => 'Manual token validation'
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful!',
                'attendee' => [
                    'name' => $attendee->name,
                    'email' => $attendee->email,
                    'checked_in_at' => now()
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No valid ticket found for this token.',
            'code' => 'NO_TICKET'
        ], 422);
    }

    /**
     * Handle QR code scan validation.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateToken(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'token' => 'required|string'
            ]);

            $user = auth()->user();
            $eventId = session('selected_event_id');
            
            // Check if event is selected
            if (!$eventId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select an event first before scanning.',
                    'code' => 'NO_EVENT_SELECTED'
                ], 422);
            }

            // Get the selected event
            $event = Event::find($eventId);
            if (!$event) {
                session()->forget('selected_event_id');
                return response()->json([
                    'success' => false,
                    'message' => 'Selected event no longer exists. Please select a new event.',
                    'code' => 'EVENT_NOT_FOUND'
                ], 422);
            }

            // Verify scanner is authorized for this event
            if (!$user->hasRole('admin') && !$user->events()->where('events.id', $eventId)->exists()) {
                session()->forget('selected_event_id');
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to scan for this event.',
                    'code' => 'UNAUTHORIZED_EVENT'
                ], 403);
            }

            // Use a database transaction with a lock to prevent race conditions
            return DB::transaction(function() use ($request, $user, $eventId) {
                // Find the attendee by token with a lock to prevent concurrent updates
                $attendee = Attendee::where('token', $request->token)
                ->where('event_id', $eventId)
                    ->with(['ticket' => function($query) {
                        $query->lockForUpdate();
                    }])
                ->first();

                if (!$attendee) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid token or token not for this event.',
                        'code' => 'INVALID_TOKEN'
                    ], 422);
                }

                // Check if ticket exists
                if (!$attendee->ticket) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No valid ticket found for this token.',
                        'code' => 'NO_TICKET'
                    ], 422);
                }

                // Check if ticket is already used
                if ($attendee->ticket->status === 'used') {
                    // Check for recent duplicate scan attempts (within last 5 minutes)
                    $recentDuplicate = ScanLog::where('ticket_id', $attendee->ticket->id)
                        ->where('scanner_id', $user->id)
                        ->where('status', 'duplicate')
                        ->where('created_at', '>=', now()->subMinutes(5))
                        ->exists();

                    // Only log if it's not a recent duplicate attempt
                    if (!$recentDuplicate) {
                        ScanLog::create([
                            'ticket_id' => $attendee->ticket->id,
                            'scanner_id' => $user->id,
                            'status' => 'duplicate',
                            'notes' => 'QR code scan - Duplicate scan attempt'
                        ]);
                    }

                return response()->json([
                    'success' => false,
                    'message' => 'This ticket has already been used.',
                        'code' => 'TICKET_USED',
                        'attendee' => [
                            'name' => $attendee->name,
                            'email' => $attendee->email,
                            'checked_in_at' => $attendee->ticket->used_at
                        ]
                ], 422);
            }

                // Check for any recent successful scans (within last 5 minutes)
                $recentScan = ScanLog::where('ticket_id', $attendee->ticket->id)
                    ->where('status', 'success')
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->exists();

                if ($recentScan) {
            return response()->json([
                'success' => false,
                        'message' => 'This ticket was recently checked in.',
                        'code' => 'RECENT_SCAN',
                        'attendee' => [
                            'name' => $attendee->name,
                            'email' => $attendee->email
                        ]
            ], 422);
        }

                // Update ticket status
                $attendee->ticket->update([
                    'status' => 'used',
                    'used_at' => now()
                ]);

                // Create scan log with unique constraint check
                try {
                    ScanLog::create([
                        'ticket_id' => $attendee->ticket->id,
            'scanner_id' => $user->id,
            'status' => 'success',
                        'notes' => 'QR code scan'
                    ]);
                } catch (\Exception $e) {
                    // If we get a duplicate entry error, roll back the transaction
                    if (str_contains($e->getMessage(), 'Duplicate entry')) {
                        throw new \Exception('Duplicate scan detected');
                    }
                    throw $e;
                }

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful!',
                    'attendee' => [
                        'name' => $attendee->name,
                        'email' => $attendee->email,
                        'checked_in_at' => now()
                    ]
                ]);
            }, 5); // Retry transaction up to 5 times if deadlock occurs

        } catch (\Exception $e) {
            \Log::error('QR code scan validation failed', [
                'error' => $e->getMessage(),
                'token' => $request->token ?? null,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the scan. Please try again.',
                'code' => 'SERVER_ERROR'
            ], 500);
        }
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

    /**
     * Display attendees for a specific event.
     */
    public function showEventAttendees(Event $event): View
    {
        $user = auth()->user();
        
        // Check if user is authorized to view this event's attendees
        if (!$user->hasRole('admin') && !$user->events()->where('events.id', $event->id)->exists()) {
            abort(403, 'You are not authorized to view attendees for this event.');
        }

        // Get attendees with their tickets and scan logs
        $attendees = $event->attendees()
            ->with(['ticket.scanLogs' => function($query) {
                $query->with('scanner')->latest();
            }])
            ->when(request('search'), function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . request('search') . '%')
                      ->orWhere('email', 'like', '%' . request('search') . '%');
                });
            })
            ->when(request('status'), function($query) {
                $query->whereHas('ticket', function($q) {
                    $q->where('status', request('status'));
                });
            })
            ->latest()
            ->paginate(15);

        return view('scanner.events.attendees', compact('event', 'attendees'));
    }

    /**
     * Display a list of all events with their attendees for the scanner.
     * 
     * @return \Illuminate\View\View
     */
    public function attendeesIndex(): View
    {
        $user = auth()->user();
        
        // Get all events assigned to the scanner with their attendees
        $events = $user->events()
            ->with(['attendees' => function($query) {
                $query->with(['ticket.scanLogs' => function($q) {
                    $q->with('scanner')->latest();
                }])
                ->when(request('search'), function($query) {
                    $query->where(function($q) {
                        $q->where('name', 'like', '%' . request('search') . '%')
                          ->orWhere('email', 'like', '%' . request('search') . '%');
                    });
                })
                ->when(request('status'), function($query) {
                    $query->whereHas('ticket', function($q) {
                        $q->where('status', request('status'));
                    });
                });
            }])
            ->when(request('event_search'), function($query) {
                $query->where('name', 'like', '%' . request('event_search') . '%');
            })
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        // Transform the data for the view
        $events->getCollection()->transform(function ($event) {
            $event->attendees_count = $event->attendees->count();
            $event->scanned_count = $event->attendees->filter(function ($attendee) {
                return $attendee->ticket && $attendee->ticket->status === 'used';
            })->count();
            $event->pending_count = $event->attendees->filter(function ($attendee) {
                return !$attendee->ticket || $attendee->ticket->status !== 'used';
            })->count();
            return $event;
        });

        return view('scanner.attendees.index', compact('events'));
    }

    /**
     * Start the scanner and initialize the camera.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function startScanner(): JsonResponse
    {
        try {
        $user = auth()->user();
            $eventId = session('selected_event_id');

            // Check if event is selected
            if (!$eventId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select an event first.'
                ], 400);
            }

            // Verify scanner is authorized for this event
            if (!$user->hasRole('admin') && !$user->events()->where('events.id', $eventId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to scan for this event.'
                ], 403);
            }

            // Get the event
            $event = Event::findOrFail($eventId);

            // Check if event is within valid date range
            if ($event->end_date < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This event has ended.'
                ], 400);
            }

            // Store scanner state in session
            session(['scanner_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Scanner started successfully.',
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'start_date' => $event->start_date->format('M d, Y'),
                    'end_date' => $event->end_date->format('M d, Y')
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to start scanner', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start scanner. Please try again.'
            ], 500);
        }
    }

    /**
     * Stop the scanner and cleanup resources.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopScanner(): JsonResponse
    {
        try {
            // Clear scanner state from session
            session()->forget('scanner_active');

            return response()->json([
                'success' => true,
                'message' => 'Scanner stopped successfully.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to stop scanner', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to stop scanner. Please try again.'
            ], 500);
        }
    }

    /**
     * Get the current scanner status.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getScannerStatus(): JsonResponse
    {
        $user = auth()->user();
        $eventId = session('selected_event_id');
        $isActive = session('scanner_active', false);

        if (!$eventId) {
            return response()->json([
                'success' => false,
                'message' => 'No event selected',
                'status' => 'inactive'
            ]);
        }

        $event = Event::find($eventId);
        if (!$event) {
            session()->forget(['selected_event_id', 'scanner_active']);
            return response()->json([
                'success' => false,
                'message' => 'Selected event not found',
                'status' => 'inactive'
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => $isActive ? 'active' : 'inactive',
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'start_date' => $event->start_date->format('M d, Y'),
                'end_date' => $event->end_date->format('M d, Y')
            ]
        ]);
    }
} 