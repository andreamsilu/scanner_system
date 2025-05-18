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

            // Check if attendee has a ticket
            if (!$attendee->ticket) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No ticket found for this attendee.'
                ], 400);
            }

            // Check if already checked in
            if ($attendee->ticket->status === 'used') {
                // Log the duplicate scan
                ScanLog::create([
                    'ticket_id' => $attendee->ticket->id,
                    'scanner_id' => auth()->id(),
                    'status' => 'duplicate',
                    'notes' => 'Duplicate scan attempt'
                ]);

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
                // Log the duplicate scan
                ScanLog::create([
                    'ticket_id' => $attendee->ticket->id,
                    'scanner_id' => null, // Public check-in
                    'status' => 'duplicate',
                    'notes' => 'Public check-in attempt'
                ]);
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
} 