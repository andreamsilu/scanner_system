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
     * Get tickets data for DataTable with server-side processing
     */
    public function getTicketsData(Request $request, Event $event)
    {
        $query = $event->tickets()
            ->with(['attendee', 'scanLogs' => function($query) {
                $query->latest();
            }]);

        // Handle search
        if ($request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                // Search in tickets table
                $q->where('tickets.ticket_number', 'like', '%' . $searchValue . '%')
                  ->orWhere('tickets.type', 'like', '%' . $searchValue . '%')
                  ->orWhere('tickets.status', 'like', '%' . $searchValue . '%')
                  ->orWhere('tickets.created_at', 'like', '%' . $searchValue . '%')
                  
                  // Search in attendees table
                  ->orWhereHas('attendee', function($q) use ($searchValue) {
                      $q->where('attendees.token', 'like', '%' . $searchValue . '%')
                        ->orWhere('attendees.name', 'like', '%' . $searchValue . '%')
                        ->orWhere('attendees.email', 'like', '%' . $searchValue . '%')
                        ->orWhere('attendees.phone', 'like', '%' . $searchValue . '%');
                  })
                  
                  // Search in scan logs
                  ->orWhereHas('scanLogs', function($q) use ($searchValue) {
                      $q->where('scan_logs.scanner_id', 'like', '%' . $searchValue . '%')
                        ->orWhere('scan_logs.status', 'like', '%' . $searchValue . '%')
                        ->orWhere('scan_logs.notes', 'like', '%' . $searchValue . '%')
                        ->orWhere('scan_logs.created_at', 'like', '%' . $searchValue . '%');
                  })
                  
                  // Search in scanner information
                  ->orWhereHas('scanLogs.scanner', function($q) use ($searchValue) {
                      $q->where('users.name', 'like', '%' . $searchValue . '%')
                        ->orWhere('users.email', 'like', '%' . $searchValue . '%');
                  });
            });
        }

        // Handle filters
        if ($request->ticket_type) {
            $query->where('type', $request->ticket_type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_range) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $startDate = \Carbon\Carbon::parse($dates[0])->startOfDay();
                $endDate = \Carbon\Carbon::parse($dates[1])->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        // Get total count before pagination
        $totalRecords = $query->count();
        $totalFiltered = $totalRecords;

        // Define sortable columns and their relationships
        $sortableColumns = [
            'ticket_number' => 'tickets.ticket_number',
            'attendee' => 'attendees.token',
            'type' => 'tickets.type',
            'status' => 'tickets.status',
            'last_scan' => 'scan_logs.created_at',
            'created_at' => 'tickets.created_at'
        ];

        // Handle ordering
        $orderColumn = $request->order[0]['column'] ?? 6; // Default to created_at
        $orderDir = $request->order[0]['dir'] ?? 'desc';
        $columns = array_keys($sortableColumns);
        
        if (isset($columns[$orderColumn])) {
            $column = $columns[$orderColumn];
            $sortColumn = $sortableColumns[$column];

            // Handle special cases for relationships
            if ($column === 'attendee') {
                $query->leftJoin('attendees', 'tickets.attendee_id', '=', 'attendees.id')
                      ->orderBy($sortColumn, $orderDir)
                      ->select('tickets.*'); // Ensure we select only ticket columns
            } elseif ($column === 'last_scan') {
                $query->leftJoin('scan_logs', function($join) {
                    $join->on('tickets.id', '=', 'scan_logs.ticket_id')
                         ->whereRaw('scan_logs.id IN (SELECT MAX(id) FROM scan_logs GROUP BY ticket_id)');
                })
                ->orderBy($sortColumn, $orderDir)
                ->select('tickets.*'); // Ensure we select only ticket columns
            } else {
                $query->orderBy($sortColumn, $orderDir);
            }
        } else {
            // Default sorting by created_at desc if no valid column is specified
            $query->orderBy('tickets.created_at', 'desc');
        }

        // Apply pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 15;
        $tickets = $query->skip($start)->take($length)->get();

        // Transform data for DataTables
        $data = $tickets->map(function ($ticket) use ($event) {
            return [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'attendee' => $ticket->attendee ? [
                    'token' => $ticket->attendee->token,
                    'name' => $ticket->attendee->name
                ] : null,
                'type' => $ticket->type,
                'status' => [
                    'value' => $ticket->status,
                    'badge' => $ticket->status === 'active' ? 'success' : 
                             ($ticket->status === 'used' ? 'info' : 'danger')
                ],
                'last_scan' => $ticket->scanLogs->first() ? [
                    'time' => $ticket->scanLogs->first()->created_at->format('Y-m-d H:i:s'),
                    'diff' => $ticket->scanLogs->first()->created_at->diffForHumans()
                ] : null,
                'created_at' => $ticket->created_at->format('Y-m-d H:i:s'),
                'actions' => [
                    'view_url' => route('events.tickets.show', ['event' => $event->id, 'ticket' => $ticket->attendee?->token]),
                    'print_url' => route('events.tickets.print', ['event' => $event->id, 'ticket' => $ticket->attendee?->token]),
                    'deactivate_url' => $ticket->status === 'active' ? 
                        route('events.tickets.deactivate', ['event' => $event->id, 'ticket' => $ticket->attendee?->token]) : null
                ]
            ];
        });

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalFiltered,
            'data' => $data
        ]);
    }
} 