<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
} 