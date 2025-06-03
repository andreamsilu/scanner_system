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
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    /**
     * Display a listing of the tickets.
     */
    public function index(Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = Ticket::with(['event', 'attendee']);

            // Apply search
            if ($request->search) {
                $searchValue = $request->search;
                $query->where(function($q) use ($searchValue) {
                    $q->whereHas('attendee', function($q) use ($searchValue) {
                        $q->where('name', 'like', '%' . $searchValue . '%')
                          ->orWhere('email', 'like', '%' . $searchValue . '%')
                          ->orWhere('phone', 'like', '%' . $searchValue . '%');
                    })
                    ->orWhere('token', 'like', '%' . $searchValue . '%')
                    ->orWhere('type', 'like', '%' . $searchValue . '%');
                });
            }

            // Get total count before pagination
            $totalRecords = $query->count();

            // Apply ordering
            if ($request->order) {
                $orderColumn = $request->order['column'] ?? 5; // Default to created_at
                $orderDir = $request->order['dir'] ?? 'desc';
                $columns = ['attendee', 'event', 'type', 'status', 'last_scan', 'created_at'];
                
                if (isset($columns[$orderColumn])) {
                    if ($columns[$orderColumn] === 'attendee') {
                        $query->join('attendees', 'tickets.attendee_id', '=', 'attendees.id')
                              ->orderBy('attendees.name', $orderDir);
                    } elseif ($columns[$orderColumn] === 'event') {
                        $query->join('events', 'tickets.event_id', '=', 'events.id')
                              ->orderBy('events.name', $orderDir);
                    } else {
                        $query->orderBy($columns[$orderColumn], $orderDir);
                    }
                }
            } else {
                $query->latest();
            }

            // Apply pagination
            $tickets = $query->paginate(15);

            // Transform data for DataTables
            $data = $tickets->map(function ($ticket) {
                return [
                    'attendee' => $ticket->attendee ? [
                        'name' => $ticket->attendee->name,
                        'token' => $ticket->token
                    ] : null,
                    'event' => [
                        'name' => $ticket->event->name,
                        'url' => route('events.show', $ticket->event)
                    ],
                    'type' => [
                        'type' => $ticket->type,
                        'badge_class' => match($ticket->type) {
                            'vip' => 'bg-warning',
                            'regular' => 'bg-secondary',
                            'single' => 'bg-primary',
                            'double' => 'bg-success',
                            default => 'bg-secondary'
                        }
                    ],
                    'status' => [
                        'status' => $ticket->is_active ? 'Active' : 'Inactive',
                        'badge_class' => $ticket->is_active ? 'bg-success' : 'bg-danger'
                    ],
                    'last_scan' => $ticket->last_scan ? [
                        'time' => $ticket->last_scan->created_at->diffForHumans(),
                        'full_time' => $ticket->last_scan->created_at->format('d-m-Y H:i')
                    ] : null,
                    'created_at' => $ticket->created_at->format('d-m-Y'),
                    'actions' => [
                        'view_url' => route('events.events.tickets.show', ['event' => $ticket->event->id, 'ticket' => $ticket->token]),
                        'print_url' => route('events.events.tickets.print', ['event' => $ticket->event->id, 'ticket' => $ticket->token]),
                        'has_phone' => $ticket->attendee && $ticket->attendee->phone,
                        'is_active' => $ticket->is_active,
                        'deactivate_url' => route('tickets.deactivate', $ticket),
                        'event_name' => $ticket->event->name,
                        'attendee_name' => $ticket->attendee ? $ticket->attendee->name : 'Unassigned',
                        'token' => $ticket->token,
                        'event_date' => $ticket->event->start_date->format('M d, Y'),
                        'ticket_url' => route('tickets.verify', $ticket->token),
                        'export_url' => route('events.events.tickets.export', ['event' => $ticket->event->id, 'ticket' => $ticket->token])
                    ]
                ];
            });

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $data,
                'pagination' => [
                    'first_item' => $tickets->firstItem(),
                    'last_item' => $tickets->lastItem(),
                    'total' => $tickets->total(),
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                    'per_page' => $tickets->perPage()
                ]
            ]);
        }

        $tickets = Ticket::with(['event', 'attendee'])->paginate(15);
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
} 