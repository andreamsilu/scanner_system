<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\Attendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TicketController extends Controller
{
    /**
     * Display a listing of the tickets.
     */
    public function index()
    {
        $tickets = Ticket::with(['event', 'attendee'])
            ->latest()
            ->paginate(20);

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Generate new tickets for an event.
     */
    public function generate(Request $request, Event $event)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
            'ticket_type' => 'required|in:regular,vip,single,double'
        ]);

        $tickets = collect();
        
        for ($i = 0; $i < $request->quantity; $i++) {
            $ticket = $event->tickets()->create([
                'ticket_id' => Str::uuid(),
                'type' => $request->ticket_type,
                'status' => 'active'
            ]);
            
            $tickets->push($ticket);
        }

        return redirect()
            ->route('events.tickets', $event)
            ->with('success', "{$request->quantity} tickets generated successfully.");
    }

    /**
     * Display the specified ticket.
     */
    public function show(Event $event, $token)
    {
        $ticket = Ticket::whereHas('attendee', function($query) use ($token) {
            $query->where('token', $token);
        })
        ->where('event_id', $event->id)
        ->with(['attendee', 'scanLogs' => function($query) {
            $query->with('scanner')->latest();
        }])
        ->firstOrFail();

        return view('tickets.show', compact('event', 'ticket'));
    }

    /**
     * Show the form for printing a ticket.
     */
    public function print(Event $event, $token)
    {
        $ticket = Ticket::whereHas('attendee', function($query) use ($token) {
            $query->where('token', $token);
        })->where('event_id', $event->id)->firstOrFail();

        return view('tickets.print', compact('event', 'ticket'));
    }

    /**
     * Deactivate a specific ticket.
     */
    public function deactivate(Event $event, $token)
    {
        $ticket = Ticket::whereHas('attendee', function($query) use ($token) {
            $query->where('token', $token);
        })->where('event_id', $event->id)->firstOrFail();

        $ticket->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        return redirect()->route('events.tickets.index', $event)
            ->with('success', 'Ticket has been deactivated successfully.');
    }

    /**
     * Deactivate all tickets for an event.
     */
    public function deactivateAll(Event $event)
    {
        $event->tickets()->update(['status' => 'deactivated']);
        
        return redirect()
            ->back()
            ->with('success', 'All tickets deactivated successfully.');
    }

    /**
     * Export tickets for an event to CSV.
     */
    public function export(Event $event)
    {
        $tickets = $event->tickets()
            ->with('attendee')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tickets.csv"',
        ];

        $callback = function() use ($tickets) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['Ticket ID', 'Type', 'Status', 'Attendee', 'Created At']);
            
            // Add data
            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->ticket_id,
                    $ticket->type,
                    $ticket->status,
                    $ticket->attendee ? $ticket->attendee->name : 'Unassigned',
                    $ticket->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show the form for creating a new ticket for an attendee.
     */
    public function create(Event $event, Attendee $attendee): View
    {
        // Check if attendee already has a ticket
        if ($attendee->ticket) {
            return redirect()
                ->route('events.attendees.show', ['event' => $event, 'attendee' => $attendee])
                ->with('error', 'This attendee already has a ticket.');
        }

        return view('tickets.create', compact('event', 'attendee'));
    }

    /**
     * Store a newly created ticket for an attendee.
     */
    public function store(Request $request, Event $event, Attendee $attendee): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Check if attendee already has a ticket
            if ($attendee->ticket) {
                throw new \Exception('This attendee already has a ticket.');
            }

            // Validate request
            $validated = $request->validate([
                'type' => ['required', 'string', Rule::in(['regular', 'vip', 'single', 'double'])],
            ]);

            // Generate ticket number
            $ticketNumber = Ticket::generateTicketNumber();

            // Create ticket
            $ticket = Ticket::create([
                'event_id' => $event->id,
                'attendee_id' => $attendee->id,
                'type' => $validated['type'],
                'status' => 'active',
                'ticket_number' => $ticketNumber,
                'token' => $attendee->token
            ]);

            // Log the ticket creation
            Log::info('Ticket created for attendee', [
                'attendee_id' => $attendee->id,
                'ticket_id' => $ticket->id,
                'event_id' => $event->id
            ]);

            DB::commit();

            return redirect()
                ->route('events.attendees.show', ['event' => $event, 'attendee' => $attendee])
                ->with('success', 'Ticket created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ticket creation failed', [
                'error' => $e->getMessage(),
                'attendee_id' => $attendee->id,
                'event_id' => $event->id
            ]);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Verify a ticket's validity
     *
     * @param string $token
     * @return \Illuminate\View\View
     */
    public function verify(string $token)
    {
        $ticket = Ticket::whereHas('attendee', function ($query) use ($token) {
            $query->where('token', $token)
                  ->whereNull('deleted_at');
        })->first();

        if (!$ticket) {
            abort(404, 'Ticket not found');
        }

        $event = $ticket->event;
        $attendee = $ticket->attendee;

        return view('tickets.verify', compact('ticket', 'event', 'attendee'));
    }

    /**
     * Export a single ticket as PDF
     */
    public function exportTicket(Event $event, $token)
    {
        $ticket = Ticket::whereHas('attendee', function($query) use ($token) {
            $query->where('token', $token);
        })
        ->where('event_id', $event->id)
        ->with(['attendee', 'event'])
        ->firstOrFail();

        $pdf = \PDF::loadView('tickets.show', compact('ticket'));
        
        return $pdf->download('ticket-' . $ticket->token . '.pdf');
    }

    /**
     * Get tickets data for DataTables
     */
    public function data(Request $request)
    {
        try {
            $query = Ticket::with(['event', 'attendee', 'lastScan']);

            // Apply filters
            if ($request->search['value']) {
                $searchValue = $request->search['value'];
                $query->where(function($q) use ($searchValue) {
                    $q->whereHas('attendee', function($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                          ->orWhere('token', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('event', function($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhere('type', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%");
                });
            }

            // Get total count before pagination
            $totalRecords = $query->count();
            $totalFiltered = $totalRecords;

            // Apply ordering
            $orderColumn = $request->order[0]['column'] ?? 5; // Default to created_at
            $orderDir = $request->order[0]['dir'] ?? 'desc';
            $columns = ['attendee', 'event', 'type', 'status', 'last_scan', 'created_at'];
            
            if (isset($columns[$orderColumn])) {
                if ($columns[$orderColumn] === 'attendee') {
                    $query->join('attendees', 'tickets.attendee_id', '=', 'attendees.id')
                          ->orderBy('attendees.name', $orderDir);
                } elseif ($columns[$orderColumn] === 'event') {
                    $query->join('events', 'tickets.event_id', '=', 'events.id')
                          ->orderBy('events.name', $orderDir);
                } elseif ($columns[$orderColumn] === 'last_scan') {
                    $query->leftJoin('scan_logs', function($join) {
                        $join->on('tickets.id', '=', 'scan_logs.ticket_id')
                             ->whereRaw('scan_logs.id = (SELECT id FROM scan_logs WHERE ticket_id = tickets.id ORDER BY created_at DESC LIMIT 1)');
                    })
                    ->orderBy('scan_logs.created_at', $orderDir);
                } else {
                    $query->orderBy($columns[$orderColumn], $orderDir);
                }
            }

            // Apply pagination
            $start = $request->start ?? 0;
            $length = $request->length ?? 20;
            $tickets = $query->skip($start)->take($length)->get();

            // Transform data for DataTables
            $data = $tickets->map(function ($ticket) {
                return [
                    'attendee' => $ticket->attendee ? [
                        'name' => $ticket->attendee->name,
                        'id' => $ticket->attendee->id,
                        'phone' => $ticket->attendee->phone
                    ] : null,
                    'event' => [
                        'id' => $ticket->event->id,
                        'name' => $ticket->event->name,
                        'start_date' => $ticket->event->start_date->format('M d, Y')
                    ],
                    'type' => $ticket->type,
                    'status' => $ticket->status,
                    'last_scan' => $ticket->lastScan ? [
                        'created_at' => $ticket->lastScan->created_at->format('d-m-Y H:i'),
                        'diff_for_humans' => $ticket->lastScan->created_at->diffForHumans()
                    ] : null,
                    'created_at' => $ticket->created_at->format('d-m-Y'),
                    'token' => $ticket->token,
                    'id' => $ticket->id,
                    'is_active' => $ticket->is_active,
                    'verify_url' => route('tickets.verify', $ticket->token)
                ];
            });

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalFiltered,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error in tickets data endpoint: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while fetching tickets data.'
            ], 500);
        }
    }

    /**
     * Get event-specific tickets data for DataTables
     */
    public function eventData(Request $request, Event $event)
    {
        try {
            $query = $event->tickets()
                ->with(['attendee', 'lastScan']);

            // Apply filters
            if ($request->search['value']) {
                $searchValue = $request->search['value'];
                $query->where(function($q) use ($searchValue) {
                    $q->whereHas('attendee', function($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                          ->orWhere('token', 'like', "%{$searchValue}%");
                    })
                    ->orWhere('type', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%");
                });
            }

            // Get total count before pagination
            $totalRecords = $query->count();
            $totalFiltered = $totalRecords;

            // Apply ordering
            $orderColumn = $request->order[0]['column'] ?? 5; // Default to created_at
            $orderDir = $request->order[0]['dir'] ?? 'desc';
            $columns = ['attendee', 'type', 'status', 'last_scan', 'created_at'];
            
            if (isset($columns[$orderColumn])) {
                if ($columns[$orderColumn] === 'attendee') {
                    $query->join('attendees', 'tickets.attendee_id', '=', 'attendees.id')
                          ->orderBy('attendees.name', $orderDir);
                } elseif ($columns[$orderColumn] === 'last_scan') {
                    $query->leftJoin('scan_logs', function($join) {
                        $join->on('tickets.id', '=', 'scan_logs.ticket_id')
                             ->whereRaw('scan_logs.id = (SELECT id FROM scan_logs WHERE ticket_id = tickets.id ORDER BY created_at DESC LIMIT 1)');
                    })
                    ->orderBy('scan_logs.created_at', $orderDir);
                } else {
                    $query->orderBy($columns[$orderColumn], $orderDir);
                }
            }

            // Apply pagination
            $start = $request->start ?? 0;
            $length = $request->length ?? 20;
            $tickets = $query->skip($start)->take($length)->get();

            // Transform data for DataTables
            $data = $tickets->map(function ($ticket) use ($event) {
                return [
                    'attendee' => $ticket->attendee ? [
                        'name' => $ticket->attendee->name,
                        'id' => $ticket->attendee->id,
                        'phone' => $ticket->attendee->phone
                    ] : null,
                    'type' => $ticket->type,
                    'status' => $ticket->status,
                    'last_scan' => $ticket->lastScan ? [
                        'created_at' => $ticket->lastScan->created_at->format('d-m-Y H:i'),
                        'diff_for_humans' => $ticket->lastScan->created_at->diffForHumans()
                    ] : null,
                    'created_at' => $ticket->created_at->format('d-m-Y'),
                    'token' => $ticket->token,
                    'id' => $ticket->id,
                    'is_active' => $ticket->is_active,
                    'verify_url' => route('tickets.verify', $ticket->token),
                    'event' => [
                        'id' => $event->id,
                        'name' => $event->name,
                        'start_date' => $event->start_date->format('M d, Y')
                    ]
                ];
            });

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalFiltered,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Error in event tickets data endpoint: ' . $e->getMessage(), [
                'exception' => $e,
                'event_id' => $event->id,
                'request' => $request->all()
            ]);

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while fetching event tickets data.'
            ], 500);
        }
    }
} 