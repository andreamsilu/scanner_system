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
    public function index(Request $request)
    {
        $query = Ticket::with(['event', 'attendee']);

        // Apply filters
        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Handle search
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('attendee', function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('email', 'like', "%{$searchTerm}%")
                      ->orWhere('phone', 'like', "%{$searchTerm}%");
                })
                ->orWhere('token', 'like', "%{$searchTerm}%")
                ->orWhere('type', 'like', "%{$searchTerm}%")
                ->orWhereHas('event', function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                });
            });
        }

        // Handle ordering
        if ($request->filled('order')) {
            $order = $request->order;
            $column = $order['column'];
            $direction = $order['dir'];

            $columns = [
                0 => 'attendee.name',
                1 => 'event.name',
                2 => 'type',
                3 => 'is_active',
                4 => 'last_scan_at',
                5 => 'created_at'
            ];

            if (isset($columns[$column])) {
                if ($column == 0) {
                    $query->join('attendees', 'tickets.attendee_id', '=', 'attendees.id')
                          ->orderBy('attendees.name', $direction);
                } elseif ($column == 1) {
                    $query->join('events', 'tickets.event_id', '=', 'events.id')
                          ->orderBy('events.name', $direction);
                } else {
                    $query->orderBy($columns[$column], $direction);
                }
            }
        } else {
            $query->latest();
        }

        // Get total count before pagination
        $totalRecords = $query->count();

        // Apply pagination
        $tickets = $query->paginate(15);

        // For AJAX requests, return JSON
        if ($request->ajax()) {
            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $tickets->items(),
                'pagination' => [
                    'first_item' => $tickets->firstItem(),
                    'last_item' => $tickets->lastItem(),
                    'total' => $tickets->total(),
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                ]
            ]);
        }

        // Get events for filter dropdown
        $events = Event::orderBy('name')->get();

        return view('tickets.index', compact('tickets', 'events'));
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