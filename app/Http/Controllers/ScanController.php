<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Models\Ticket;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScanController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,ticket_id'
        ]);

        $ticket = Ticket::where('ticket_id', $request->ticket_id)->first();
        
        if (!$ticket->isActive()) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Ticket is not active.'
            ]);
        }

        $scan = DB::transaction(function () use ($ticket) {
            // Check for duplicate scans within the last 5 minutes
            $recentScan = Scan::where('ticket_id', $ticket->id)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->first();

            if ($recentScan) {
                $scan = Scan::create([
                    'ticket_id' => $ticket->id,
                    'event_id' => $ticket->event_id,
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
                'event_id' => $ticket->event_id,
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
            'status' => $scan->status,
            'message' => $this->getScanMessage($scan->status)
        ]);
    }

    public function index(Event $event)
    {
        $scans = $event->scans()
            ->with(['ticket.attendee', 'scanner'])
            ->latest()
            ->paginate(20);

        return view('scans.index', compact('event', 'scans'));
    }

    public function show(Event $event, Scan $scan)
    {
        $scan->load(['ticket.attendee', 'scanner']);
        return view('scans.show', compact('event', 'scan'));
    }

    protected function getScanMessage($status)
    {
        return match($status) {
            'success' => 'Ticket scanned successfully.',
            'invalid' => 'Invalid ticket.',
            'duplicate' => 'Duplicate scan detected.',
            default => 'Unknown scan status.'
        };
    }
} 