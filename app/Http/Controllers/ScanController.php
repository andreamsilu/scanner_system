<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\ScanLog;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ScanController extends Controller
{
    /**
     * Handle QR code scan and check-in
     */
    public function scan(Request $request)
    {
        try {
            // Validate the token
            $request->validate([
                'token' => 'required|string|size:7'
            ]);

            $scanner = Auth::user();
            $eventId = session('selected_event_id');

            // Check if event is selected
            if (!$eventId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Please select an event first.'
                ], 400);
            }

            // Verify scanner is assigned to this event
            if (!$scanner->hasRole('admin') && !$scanner->events()->where('events.id', $eventId)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to scan for this event.'
                ], 403);
            }

            // Get the event
            $event = Event::findOrFail($eventId);

            // Remove event status check
            // if ($event->status !== 'active') {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'This event is not active.'
            //     ], 400);
            // }

            // Check if event is within valid date range
            if ($event->end_date < now()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This event has ended.'
                ], 400);
            }

            DB::beginTransaction();

            // Find the attendee by token
            $attendee = Attendee::where('token', $request->token)
                ->with(['ticket', 'event'])
                ->first();

            if (!$attendee) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid QR code. Attendee not found.'
                ], 404);
            }

            // Check if attendee belongs to the selected event
            if ($attendee->event_id != $eventId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This QR code is not valid for the selected event.'
                ], 400);
            }

            // Check if attendee has a ticket
            if (!$attendee->ticket) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No ticket found for this attendee.'
                ], 400);
            }

            // Check if already checked in
            if ($attendee->ticket->status === 'used') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Attendee already checked in.',
                    'attendee' => [
                        'name' => $attendee->name,
                        'email' => $attendee->email,
                        'event' => $attendee->event->name,
                        'checked_in_at' => $attendee->ticket->used_at
                    ]
                ], 400);
            }

            // Update ticket status
            $attendee->ticket->update([
                'status' => 'used',
                'used_at' => now()
            ]);

            // Mark attendee as checked in
            $attendee->markAsCheckedIn();

            // Create scan log
            ScanLog::create([
                'ticket_id' => $attendee->ticket->id,
                'scanner_id' => auth()->id(),
                'status' => 'success',
                'notes' => 'Checked in via QR code scan'
            ]);

            DB::commit();

            // Log the successful check-in
            Log::info('Attendee checked in via QR code', [
                'attendee_id' => $attendee->id,
                'ticket_id' => $attendee->ticket->id,
                'scanner_id' => auth()->id()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Attendee successfully checked in.',
                'attendee' => [
                    'name' => $attendee->name,
                    'email' => $attendee->email,
                    'event' => $attendee->event->name,
                    'checked_in_at' => now()
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('QR code scan failed', [
                'error' => $e->getMessage(),
                'token' => $request->token ?? null
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing the scan.'
            ], 500);
        }
    }

    /**
     * Display the scanner interface
     */
    public function scanner()
    {
        return view('scanner.index');
    }

    public function index(Event $event): View
    {
        $scans = ScanLog::with(['ticket.attendee', 'scanner'])
            ->whereHas('ticket', function($query) use ($event) {
                $query->where('event_id', $event->id);
            })
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

    /**
     * Handle public check-in via QR code
     */
    public function checkIn($token)
    {
        try {
            // Validate token format
            if (strlen($token) !== 7) {
                $data = [
                    'success' => false,
                    'message' => 'Invalid token format',
                ];
                return request()->wantsJson() ? response()->json($data, 400) : view('checkin.result', $data);
            }

            // Find attendee by token
            $attendee = Attendee::where('token', $token)->with(['ticket', 'event'])->first();
            if (!$attendee) {
                $data = [
                    'success' => false,
                    'message' => 'Invalid token',
                ];
                return request()->wantsJson() ? response()->json($data, 404) : view('checkin.result', $data);
            }

            // Check if attendee has a ticket
            if (!$attendee->ticket) {
                $data = [
                    'success' => false,
                    'message' => 'No ticket found for this attendee',
                    'attendee' => $attendee,
                ];
                return request()->wantsJson() ? response()->json($data, 400) : view('checkin.result', $data);
            }

            // Check if ticket is already used
            if ($attendee->ticket->status === 'used') {
                $data = [
                    'success' => false,
                    'message' => 'Ticket already used',
                    'attendee' => $attendee,
                ];
                return request()->wantsJson() ? response()->json($data, 400) : view('checkin.result', $data);
            }

            // Start transaction
            DB::beginTransaction();
            try {
                // Update ticket status
                $attendee->ticket->update([
                    'status' => 'used',
                    'used_at' => now()
                ]);
                // Create scan log
                ScanLog::create([
                    'ticket_id' => $attendee->ticket->id,
                    'scanner_id' => null, // Public check-in
                    'status' => 'success',
                    'notes' => 'Public check-in'
                ]);
                DB::commit();
                $data = [
                    'success' => true,
                    'message' => 'Check-in successful',
                    'attendee' => $attendee,
                ];
                return request()->wantsJson() ? response()->json($data) : view('checkin.result', $data);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Check-in failed: ' . $e->getMessage());
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Check-in error: ' . $e->getMessage());
            $data = [
                'success' => false,
                'message' => 'An error occurred during check-in',
            ];
            return request()->wantsJson() ? response()->json($data, 500) : view('checkin.result', $data);
        }
    }

    /**
     * Validate an attendee token manually
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function validateToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:255'
        ]);

        $token = $request->token;
        $scanner = Auth::user();

        // Find the attendee by token
        $attendee = Attendee::where('token', $token)
            ->with(['ticket.event'])
            ->first();

        if (!$attendee) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid token'
                ], 404);
            }
            return back()->with('error', 'Invalid token');
        }

        // Check if the ticket is already used
        if ($attendee->ticket && $attendee->ticket->status === 'used') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'warning',
                    'message' => 'Ticket already used',
                    'attendee' => $attendee->name,
                    'event' => $attendee->ticket->event->name
                ]);
            }
            return back()->with('warning', 'Ticket already used for ' . $attendee->name);
        }

        // Update ticket status
        if ($attendee->ticket) {
            $now = now();
            $attendee->ticket->update([
                'status' => 'used',
                'checked_in_at' => $now,
                'checked_in_by' => $scanner->id
            ]);

            // Update attendee registration status
            $attendee->update([
                'registration_status' => 'checked_in',
                'checked_in_at' => $now
            ]);

            // Log the successful scan
            ScanLog::create([
                'scanner_id' => $scanner->id,
                'ticket_id' => $attendee->ticket->id,
                'status' => 'success',
                'notes' => 'Manual token validation'
            ]);

            // Log the successful check-in
            Log::info('Attendee checked in via manual validation', [
                'attendee_id' => $attendee->id,
                'ticket_id' => $attendee->ticket->id,
                'event_id' => $attendee->event_id,
                'scanner_id' => $scanner->id
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Check-in successful',
                    'attendee' => $attendee->name,
                    'event' => $attendee->ticket->event->name
                ]);
            }
            return back()->with('success', 'Successfully checked in ' . $attendee->name);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No valid ticket found for this token'
            ], 404);
        }
        return back()->with('error', 'No valid ticket found for this token');
    }
} 