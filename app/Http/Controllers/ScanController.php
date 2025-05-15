<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Models\Ticket;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        // Get the event from the session
        $eventId = session('selected_event_id');
        if (!$eventId) {
            return response()->json([
                'success' => false,
                'message' => 'Please select an event first'
            ]);
        }

        $event = Event::findOrFail($eventId);
        
        // Find the ticket by token
        $ticket = Ticket::where('token', $request->token)
            ->where('event_id', $eventId)
            ->first();

        if (!$ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code or QR code not for this event.'
            ]);
        }

        if (!$ticket->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket is not active.'
            ]);
        }

        $scan = DB::transaction(function () use ($ticket, $event) {
            // Check for duplicate scans within the last 5 minutes
            $recentScan = Scan::where('ticket_id', $ticket->id)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->first();

            if ($recentScan) {
                $scan = Scan::create([
                    'ticket_id' => $ticket->id,
                    'event_id' => $event->id,
                    'scanner_id' => auth()->id(),
                    'scanned_at' => now(),
                    'status' => 'duplicate',
                    'notes' => 'Duplicate scan detected within 5 minutes'
                ]);

                return $scan;
            }

            // Create successful scan
            $scan = Scan::create([
                'ticket_id' => $ticket->id,
                'event_id' => $event->id,
                'scanner_id' => auth()->id(),
                'scanned_at' => now(),
                'status' => 'success'
            ]);

            // Update ticket
            $ticket->update([
                'last_scanned_at' => now()
            ]);

            return $scan;
        });

        return response()->json([
            'success' => $scan->status === 'success',
            'message' => $this->getScanMessage($scan->status)
        ]);
    }

    public function index(Event $event): View
    {
        $scans = $event->scans()
            ->with(['ticket.attendee', 'scanner'])
            ->latest()
            ->paginate(20);

        return view('scanner.scan.index', compact('event', 'scans'));
    }

    public function show(Event $event, Scan $scan): View
    {
        $scan->load(['ticket.attendee', 'scanner']);
        return view('scanner.scan.show', compact('event', 'scan'));
    }

    protected function getScanMessage($status)
    {
        return match($status) {
            'success' => 'Check-in successful!',
            'invalid' => 'Invalid QR code.',
            'duplicate' => 'Duplicate scan detected.',
            'expired' => 'QR code has expired.',
            default => 'Unknown error occurred.'
        };
    }
} 