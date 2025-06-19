<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\ScanLog;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\Scan;
use App\Services\SmsService;
use App\Jobs\SendSingleSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ScanController extends Controller
{
    /**
     * The SMS service instance
     *
     * @var SmsService
     */
    protected SmsService $smsService;

    /**
     * Create a new controller instance
     */
    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

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

            // Send SMS notification if attendee has a phone number - outside transaction
            if ($attendee->phone) {
                // Log SMS attempt initiation
                Log::info("SMS notification initiated for QR scan check-in", [
                    'attendee_id' => $attendee->id,
                    'phone' => $attendee->phone,
                    'event' => $attendee->event->name,
                    'ticket_id' => $attendee->ticket->id,
                    'ticket_type' => $attendee->ticket->type,
                    'entry_point' => 'QR Scan',
                    'scanner_id' => auth()->id(),
                    'timestamp' => now()->toIso8601String()
                ]);

                try {
                    // Log message formatting
                    $message = $this->formatTicketUsageMessage($attendee->event, $attendee->ticket, 'QR Scan');
                    Log::info("SMS message formatted for QR scan check-in", [
                        'attendee_id' => $attendee->id,
                        'phone' => $attendee->phone,
                        'message_length' => strlen($message),
                        'message_preview' => substr($message, 0, 100) . '...',
                        'timestamp' => now()->toIso8601String()
                    ]);

                    // Prepare metadata
                    $metadata = [
                        'event_id' => $attendee->event_id,
                        'attendee_id' => $attendee->id,
                        'ticket_id' => $attendee->ticket->id,
                        'attendee_name' => $attendee->name,
                        'attendee_phone' => $attendee->phone,
                        'ticket_number' => $attendee->ticket->ticket_number,
                        'ticket_type' => $attendee->ticket->type,
                        'event_name' => $attendee->event->name,
                        'entry_point' => 'QR Scan',
                        'scanned_at' => now()->toDateTimeString(),
                        'scanner_id' => auth()->id(),
                        'validation_type' => 'qr_scan'
                    ];

                    // Log job dispatch attempt
                    Log::info("Attempting to dispatch SMS job for QR scan check-in", [
                        'attendee_id' => $attendee->id,
                        'phone' => $attendee->phone,
                        'event' => $attendee->event->name,
                        'ticket_id' => $attendee->ticket->id,
                        'job_class' => 'SendSingleSms',
                        'message_type' => 'ticket_used',
                        'metadata_keys' => array_keys($metadata),
                        'timestamp' => now()->toIso8601String()
                    ]);

                    // Dispatch SMS job to avoid blocking the response
                    SendSingleSms::dispatch(
                        $attendee->phone,
                        $message,
                        'ticket_used',
                        $metadata
                    );

                    // Log successful job dispatch
                    Log::info("SMS job successfully dispatched for QR scan check-in", [
                        'attendee_id' => $attendee->id,
                        'phone' => $attendee->phone,
                        'event' => $attendee->event->name,
                        'ticket_id' => $attendee->ticket->id,
                        'job_status' => 'queued',
                        'queue_connection' => config('queue.default'),
                        'timestamp' => now()->toIso8601String()
                    ]);
                
                } catch (\Exception $e) {
                    // Log SMS dispatch error
                    Log::error("Failed to dispatch SMS job for QR scan check-in", [
                        'attendee_id' => $attendee->id,
                        'phone' => $attendee->phone,
                        'event' => $attendee->event->name,
                        'ticket_id' => $attendee->ticket->id,
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'timestamp' => now()->toIso8601String()
                    ]);
                }
            } else {
                // Log when SMS is skipped due to missing phone
                Log::info("SMS notification skipped for QR scan check-in - no phone number", [
                    'attendee_id' => $attendee->id,
                    'attendee_name' => $attendee->name,
                    'event' => $attendee->event->name,
                    'ticket_id' => $attendee->ticket->id,
                    'phone_available' => false,
                    'timestamp' => now()->toIso8601String()
                ]);
            }

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
     * @return \Illuminate\Http\JsonResponse
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
        $attendee->ticket->update([
            'status' => 'used',
            'used_at' => now()
        ]);

        // Create scan log
        ScanLog::create([
            'ticket_id' => $attendee->ticket->id,
            'scanner_id' => $scanner->id,
            'status' => 'success',
            'notes' => 'Manual token validation'
        ]);

        // Send SMS notification if attendee has a phone number
        if ($attendee->phone) {
            // Log SMS attempt initiation
            Log::info("SMS notification initiated for manual token validation", [
                'attendee_id' => $attendee->id,
                'phone' => $attendee->phone,
                'event' => $attendee->event->name,
                'ticket_id' => $attendee->ticket->id,
                'ticket_type' => $attendee->ticket->type,
                'entry_point' => 'Manual Entry',
                'scanner_id' => $scanner->id,
                'timestamp' => now()->toIso8601String()
            ]);

            try {
                // Log message formatting
                $message = $this->formatTicketUsageMessage($attendee->event, $attendee->ticket, 'Manual Entry');
                Log::info("SMS message formatted for manual token validation", [
                    'attendee_id' => $attendee->id,
                    'phone' => $attendee->phone,
                    'message_length' => strlen($message),
                    'message_preview' => substr($message, 0, 100) . '...',
                    'timestamp' => now()->toIso8601String()
                ]);

                // Prepare metadata
                $metadata = [
                    'event_id' => $attendee->event_id,
                    'attendee_id' => $attendee->id,
                    'ticket_id' => $attendee->ticket->id,
                    'attendee_name' => $attendee->name,
                    'attendee_phone' => $attendee->phone,
                    'ticket_number' => $attendee->ticket->ticket_number,
                    'ticket_type' => $attendee->ticket->type,
                    'event_name' => $attendee->event->name,
                    'entry_point' => 'Manual Entry',
                    'scanned_at' => now()->toDateTimeString(),
                    'scanner_id' => $scanner->id,
                    'validation_type' => 'manual'
                ];

                // Log job dispatch attempt
                Log::info("Attempting to dispatch SMS job for manual token validation", [
                    'attendee_id' => $attendee->id,
                    'phone' => $attendee->phone,
                    'event' => $attendee->event->name,
                    'ticket_id' => $attendee->ticket->id,
                    'job_class' => 'SendSingleSms',
                    'message_type' => 'ticket_used',
                    'metadata_keys' => array_keys($metadata),
                    'timestamp' => now()->toIso8601String()
                ]);

                // Dispatch SMS job to avoid blocking the response
                SendSingleSms::dispatch(
                    $attendee->phone,
                    $message,
                    'ticket_used',
                    $metadata
                );

                // Log successful job dispatch
                Log::info("SMS job successfully dispatched for manual token validation", [
                    'attendee_id' => $attendee->id,
                    'phone' => $attendee->phone,
                    'event' => $attendee->event->name,
                    'ticket_id' => $attendee->ticket->id,
                    'job_status' => 'queued',
                    'queue_connection' => config('queue.default'),
                    'timestamp' => now()->toIso8601String()
                ]);
            } catch (\Exception $e) {
                // Log SMS dispatch error
                Log::error("Failed to dispatch SMS job for manual token validation", [
                    'attendee_id' => $attendee->id,
                    'phone' => $attendee->phone,
                    'event' => $attendee->event->name,
                    'ticket_id' => $attendee->ticket->id,
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'timestamp' => now()->toIso8601String()
                ]);
            }
        } else {
            // Log when SMS is skipped due to missing phone
            Log::info("SMS notification skipped for manual token validation - no phone number", [
                'attendee_id' => $attendee->id,
                'attendee_name' => $attendee->name,
                'event' => $attendee->event->name,
                'ticket_id' => $attendee->ticket->id,
                'phone_available' => false,
                'timestamp' => now()->toIso8601String()
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Check-in successful',
                'attendee' => [
                    'name' => $attendee->name,
                    'email' => $attendee->email,
                    'event' => $attendee->ticket->event->name,
                    'checked_in_at' => now()
                ]
            ]);
        }

        return back()->with('success', 'Check-in successful for ' . $attendee->name);
    }

    /**
     * Format a ticket usage notification message
     *
     * @param Event $event The event
     * @param Ticket $ticket The ticket
     * @param string $entryPoint The entry point
     * @return string Formatted message
     */
    protected function formatTicketUsageMessage(Event $event, Ticket $ticket, string $entryPoint): string
    {
        $attendee = $ticket->attendee;
        $scannedTime = now()->format('g:i A');

        return sprintf(
            "Ndugu %s, tiketi yako ya %s imethibitishwa na kuingia kupitia %s saa %s. Asante kwa kuhudhuria %s. Karibu tena!",
            $attendee->name,
            $event->name,
            $entryPoint,
            $scannedTime,
            $event->name
        );
    }
} 