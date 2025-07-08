<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Jobs\SendBulkSms;
use App\Jobs\SendSingleSms;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SmsManagementController extends Controller
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
        $this->middleware('auth');
    }

    /**
     * Display the SMS management dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        try {
            $balance = $this->smsService->getBalance();
            $provider = $this->smsService->getProviderName();
            
            // Get recent SMS logs (last 10 entries)
            $recentLogs = SmsLog::latest()
                ->take(10)
                ->get();

            // Get all events for selection
            $events = \App\Models\Event::orderBy('name')->get();

            return view('sms.management.index', compact('balance', 'provider', 'recentLogs', 'events'));
        } catch (\Exception $e) {
            Log::error('SMS Management Dashboard Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('sms.management.index', [
                'error' => 'Unable to load SMS management dashboard. Please try again later.',
                'recentLogs' => collect(), // Empty collection as fallback
                'events' => collect() // Empty collection as fallback
            ]);
        }
    }

    /**
     * Display SMS management for a specific event
     */
    public function showEvent(Event $event, Request $request)
    {
        $attendees = $event->attendees()
            ->with(['tickets' => function($query) use ($event) {
                $query->where('event_id', $event->id);
            }])
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->paginate(15);

        // Load SMS statistics
        $smsStats = SmsLog::whereJsonContains('metadata->event_id', $event->id)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        // Load recent SMS logs with attendee information
        $recentLogs = SmsLog::whereJsonContains('metadata->event_id', $event->id)
            ->select([
                'id',
                'type',
                'recipient',
                'message',
                'status',
                'created_at',
                'metadata'
            ])
            ->with(['metadata'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($log) {
                $metadata = $log->metadata ?? [];
                $log->recipient_name = $metadata['attendee_name'] ?? 'Unknown';
                $log->content = $log->message;
                return $log;
            });

        // Load event statistics
        $event->loadCount(['attendees', 'sms_logs']);
        $event->load(['sms_logs' => function($query) {
            $query->latest()->take(1);
        }]);

        return view('sms.management.event', compact(
            'event',
            'attendees',
            'smsStats',
            'recentLogs'
        ));
    }

    /**
     * Send SMS to multiple attendees
     *
     * @param Request $request
     * @param Event $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkSend(Request $request, Event $event)
    {
        $startTime = microtime(true);
        $userId = auth()->id();
        
        try {
            // Log bulk SMS request initiation
            Log::info('Bulk SMS request initiated', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'event_name' => $event->name,
                'message_type' => $request->message_type,
                'has_custom_message' => !empty($request->custom_message),
                'request_data' => $request->only(['attendee_ids', 'message_type', 'custom_message']),
                'timestamp' => now()->toIso8601String()
            ]);

            // Rate limiting: max 100 SMS per minute per user
            $executed = RateLimiter::attempt(
                'bulk-sms:' . $userId,
                $perMinute = 100,
                function() {
                    return true;
                }
            );

            if (!$executed) {
                // Log rate limit exceeded
                Log::warning('Bulk SMS rate limit exceeded', [
                    'user_id' => $userId,
                    'event_id' => $event->id,
                    'rate_limit_key' => 'bulk-sms:' . $userId,
                    'rate_limit_per_minute' => $perMinute,
                    'timestamp' => now()->toIso8601String()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Too many SMS requests. Please try again in a minute.'
                ], 429);
            }

            // Log rate limit check passed
            Log::info('Bulk SMS rate limit check passed', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'rate_limit_key' => 'bulk-sms:' . $userId,
                'timestamp' => now()->toIso8601String()
            ]);

            // Validate request
            $validator = Validator::make($request->all(), [
                'attendee_ids' => 'required|string', // We receive it as JSON string
                'message_type' => ['required', Rule::in(['invitation', 'reminder', 'notification'])],
                'custom_message' => 'nullable|string|max:160'
            ]);

            if ($validator->fails()) {
                // Log validation failure
                Log::warning('Bulk SMS validation failed', [
                    'user_id' => $userId,
                    'event_id' => $event->id,
                    'validation_errors' => $validator->errors()->toArray(),
                    'timestamp' => now()->toIso8601String()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Log validation success
            Log::info('Bulk SMS validation passed', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'message_type' => $request->message_type,
                'timestamp' => now()->toIso8601String()
            ]);

            // Parse the JSON string to array
            $attendeeIds = json_decode($request->attendee_ids, true);
            
            if (!is_array($attendeeIds)) {
                // Log invalid attendee IDs format
                Log::error('Bulk SMS invalid attendee_ids format', [
                    'user_id' => $userId,
                    'event_id' => $event->id,
                    'attendee_ids_raw' => $request->attendee_ids,
                    'parsed_result' => $attendeeIds,
                    'timestamp' => now()->toIso8601String()
                ]);

                throw new \Exception('Invalid attendee_ids format');
            }

            // Log attendee IDs parsing success
            Log::info('Bulk SMS attendee IDs parsed successfully', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'attendee_count' => count($attendeeIds),
                'attendee_ids' => $attendeeIds,
                'timestamp' => now()->toIso8601String()
            ]);

            $results = [];
            $successCount = 0;
            $failureCount = 0;

            // Get all attendees in one query
            $attendees = $event->attendees()
                ->whereIn('id', $attendeeIds)
                ->with(['tickets' => function($query) {
                    $query->where('status', 'active');
                }])
                ->get();

            // Log attendees retrieval
            Log::info('Bulk SMS attendees retrieved', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'requested_count' => count($attendeeIds),
                'found_count' => $attendees->count(),
                'attendees_with_phones' => $attendees->whereNotNull('phone')->count(),
                'timestamp' => now()->toIso8601String()
            ]);

            foreach ($attendees as $attendee) {
                try {
                    // Log individual attendee processing start
                    Log::info('Bulk SMS processing individual attendee', [
                        'user_id' => $userId,
                        'event_id' => $event->id,
                        'attendee_id' => $attendee->id,
                        'attendee_name' => $attendee->name,
                        'attendee_phone' => $attendee->phone,
                        'has_phone' => !empty($attendee->phone),
                        'ticket_count' => $attendee->tickets->count(),
                        'timestamp' => now()->toIso8601String()
                    ]);

                    if (!$attendee->phone) {
                        // Log skipped attendee (no phone)
                        Log::info('Bulk SMS attendee skipped - no phone number', [
                            'user_id' => $userId,
                            'event_id' => $event->id,
                            'attendee_id' => $attendee->id,
                            'attendee_name' => $attendee->name,
                            'timestamp' => now()->toIso8601String()
                        ]);

                        $results[] = [
                            'success' => false,
                            'recipient' => $attendee->name,
                            'message' => 'No phone number available'
                        ];
                        $failureCount++;
                        continue;
                    }

                    $ticket = $attendee->tickets->first();
                    if (!$ticket) {
                        // Log skipped attendee (no ticket)
                        Log::info('Bulk SMS attendee skipped - no active ticket', [
                            'user_id' => $userId,
                            'event_id' => $event->id,
                            'attendee_id' => $attendee->id,
                            'attendee_name' => $attendee->name,
                            'timestamp' => now()->toIso8601String()
                        ]);

                        $results[] = [
                            'success' => false,
                            'recipient' => $attendee->name,
                            'message' => 'No active ticket found'
                        ];
                        $failureCount++;
                        continue;
                    }

                    // Log SMS sending attempt for individual attendee
                    Log::info('Bulk SMS sending SMS to individual attendee', [
                        'user_id' => $userId,
                        'event_id' => $event->id,
                        'attendee_id' => $attendee->id,
                        'attendee_name' => $attendee->name,
                        'attendee_phone' => $attendee->phone,
                        'ticket_id' => $ticket->id,
                        'ticket_type' => $ticket->type,
                        'message_type' => $request->message_type,
                        'timestamp' => now()->toIso8601String()
                    ]);

                    switch ($request->message_type) {
                        case 'invitation':
                            $result = $this->smsService->sendInvitation($ticket, $request->custom_message);
                            break;
                        case 'reminder':
                            $result = $this->smsService->sendEventReminder($ticket, $request->custom_message);
                            break;
                        case 'notification':
                            $result = $this->smsService->sendTicketUsageNotification($ticket, $request->custom_message);
                            break;
                        default:
                            throw new \Exception('Invalid message type');
                    }

                    // Log individual SMS result
                    Log::info('Bulk SMS individual SMS result', [
                        'user_id' => $userId,
                        'event_id' => $event->id,
                        'attendee_id' => $attendee->id,
                        'attendee_name' => $attendee->name,
                        'success' => $result['success'],
                        'message_id' => $result['message_id'] ?? null,
                        'log_id' => $result['log_id'] ?? null,
                        'error' => $result['error'] ?? null,
                        'timestamp' => now()->toIso8601String()
                    ]);

                    $results[] = [
                        'success' => $result['success'],
                        'recipient' => $attendee->name,
                        'message' => $result['success'] ? 'SMS sent successfully' : ($result['error'] ?? 'Failed to send SMS'),
                        'log_id' => $result['log_id'] ?? null,
                        'reference' => $result['reference'] ?? null
                    ];

                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failureCount++;
                    }

                } catch (\Exception $e) {
                    // Log individual attendee processing error
                    Log::error('Bulk SMS failed to send SMS to individual attendee', [
                        'user_id' => $userId,
                        'event_id' => $event->id,
                        'attendee_id' => $attendee->id,
                        'attendee_name' => $attendee->name,
                        'exception_class' => get_class($e),
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'timestamp' => now()->toIso8601String()
                    ]);

                    $results[] = [
                        'success' => false,
                        'recipient' => $attendee->name,
                        'message' => 'Error: ' . $e->getMessage()
                    ];
                    $failureCount++;
                }
            }

            // Log bulk SMS completion
            Log::info('Bulk SMS completed', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'total_attendees' => $attendees->count(),
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'success' => $successCount > 0,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'results' => $results,
                'message' => $successCount > 0 
                    ? "Successfully sent {$successCount} messages" . ($failureCount > 0 ? ", {$failureCount} failed" : "")
                    : "Failed to send any messages"
            ]);

        } catch (\Exception $e) {
            // Log bulk SMS error
            Log::error('Bulk SMS send failed with exception', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'exception_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk SMS request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send invitation SMS to an attendee
     */
    public function sendAttendeeInvitation(Request $request, Event $event)
    {
        $startTime = microtime(true);
        $userId = auth()->id();
        
        try {
            // Log invitation request initiation
            Log::info('Manual invitation SMS request initiated', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'event_name' => $event->name,
                'attendee_id' => $request->attendee_id,
                'ticket_id' => $request->ticket_id,
                'request_data' => $request->only(['attendee_id', 'ticket_id', 'custom_message']),
                'timestamp' => now()->toIso8601String()
            ]);

            // Rate limiting: max 10 SMS per minute per user
            $executed = RateLimiter::attempt(
                'single-sms:' . $userId,
                $perMinute = 10,
                function() {
                    return true;
                }
            );

            if (!$executed) {
                // Log rate limit exceeded
                Log::warning('Manual invitation SMS rate limit exceeded', [
                    'user_id' => $userId,
                    'event_id' => $event->id,
                    'attendee_id' => $request->attendee_id,
                    'rate_limit_key' => 'single-sms:' . $userId,
                    'rate_limit_per_minute' => $perMinute,
                    'timestamp' => now()->toIso8601String()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Too many SMS requests. Please try again in a minute.'
                ], 429);
            }

            // Log rate limit check passed
            Log::info('Manual invitation SMS rate limit check passed', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'attendee_id' => $request->attendee_id,
                'rate_limit_key' => 'single-sms:' . $userId,
                'timestamp' => now()->toIso8601String()
            ]);

            // Validate request
            $validator = Validator::make($request->all(), [
                'attendee_id' => 'required|exists:attendees,id',
                'ticket_id' => 'required|exists:tickets,id',
                'custom_message' => 'nullable|string|max:160'
            ]);

            if ($validator->fails()) {
                // Log validation failure
                Log::warning('Manual invitation SMS validation failed', [
                    'user_id' => $userId,
                    'event_id' => $event->id,
                    'attendee_id' => $request->attendee_id,
                    'validation_errors' => $validator->errors()->toArray(),
                    'timestamp' => now()->toIso8601String()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Log validation success
            Log::info('Manual invitation SMS validation passed', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'attendee_id' => $request->attendee_id,
                'ticket_id' => $request->ticket_id,
                'timestamp' => now()->toIso8601String()
            ]);

            // Get attendee and ticket
            $attendee = $event->attendees()->findOrFail($request->attendee_id);
            $ticket = $attendee->tickets()->findOrFail($request->ticket_id);

            // Log attendee and ticket retrieval
            Log::info('Manual invitation SMS attendee and ticket retrieved', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'attendee_id' => $attendee->id,
                'attendee_name' => $attendee->name,
                'attendee_phone' => $attendee->phone,
                'ticket_id' => $ticket->id,
                'ticket_type' => $ticket->type,
                'ticket_number' => $ticket->ticket_number,
                'timestamp' => now()->toIso8601String()
            ]);

            // Format and validate phone number
            $formattedPhone = $this->formatPhoneNumber($attendee->phone);
            
            if (!$formattedPhone) {
                // Log invalid phone number
                Log::warning('Manual invitation SMS invalid phone number', [
                    'user_id' => $userId,
                    'event_id' => $event->id,
                    'attendee_id' => $attendee->id,
                    'attendee_name' => $attendee->name,
                    'attendee_phone' => $attendee->phone,
                    'formatted_phone' => $formattedPhone,
                    'phone_validation' => false,
                    'timestamp' => now()->toIso8601String()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number format for ' . $attendee->name . '. Expected format: 0769289824 or +255769289824'
                ], 422);
            }

            // Log phone number validation and formatting success
            Log::info('Manual invitation SMS phone number validation and formatting passed', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'attendee_id' => $attendee->id,
                'original_phone' => $attendee->phone,
                'formatted_phone' => $formattedPhone,
                'phone_validation' => true,
                'timestamp' => now()->toIso8601String()
            ]);

            // Prepare SMS payload - use the message from the modal
            $message = $request->message ?? $request->custom_message ?? $this->getDefaultInvitationMessage($ticket);
            $reference = 'EVT-' . $event->id . '-' . $ticket->id . '-' . time();
            
            // Log message preparation
            Log::info('Manual invitation SMS message prepared', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'attendee_id' => $attendee->id,
                'message_length' => strlen($message),
                'message_preview' => substr($message, 0, 100) . '...',
                'message_source' => $request->message ? 'modal' : ($request->custom_message ? 'custom' : 'default'),
                'uses_custom_message' => !empty($request->custom_message),
                'reference' => $reference,
                'timestamp' => now()->toIso8601String()
            ]);
            
            $payload = [
                'from' => 'SENDOFF',
                'to' => $formattedPhone,
                'text' => $message,
                'reference' => $reference
            ];

            // Log SMS service call attempt
            Log::info('Manual invitation SMS service call initiated', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'attendee_id' => $attendee->id,
                'original_phone' => $attendee->phone,
                'formatted_phone' => $formattedPhone,
                'message_type' => 'invitation',
                'reference' => $reference,
                'payload_keys' => array_keys($payload),
                'timestamp' => now()->toIso8601String()
            ]);

            // Send SMS using the service
            $result = $this->smsService->send(
                $payload['to'],
                $payload['text'],
                'invitation',
                [
                    'event_id' => $event->id,
                    'ticket_id' => $ticket->id,
                    'attendee_id' => $attendee->id,
                    'reference' => $reference,
                    'user_id' => $userId,
                    'invitation_type' => 'manual',
                    'message_source' => $request->message ? 'modal' : ($request->custom_message ? 'custom' : 'default'),
                    'uses_custom_message' => !empty($request->custom_message)
                ]
            );

            // Log SMS service response
            Log::info('Manual invitation SMS service response received', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'attendee_id' => $attendee->id,
                'success' => $result['success'] ?? false,
                'message_id' => $result['message_id'] ?? null,
                'log_id' => $result['log_id'] ?? null,
                'reference' => $reference,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'timestamp' => now()->toIso8601String()
            ]);

            // Check if we got a valid response with messages array
            if (!isset($result['messages']) || !is_array($result['messages']) || empty($result['messages'])) {
                // Log invalid response
                Log::error('Manual invitation SMS invalid service response', [
                    'user_id' => $userId,
                    'event_id' => $event->id,
                    'attendee_id' => $attendee->id,
                    'result' => $result,
                    'reference' => $reference,
                    'timestamp' => now()->toIso8601String()
                ]);

                throw new \Exception('Invalid response from SMS service');
            }

            $smsResult = $result['messages'][0]; // Get first message result

            // Log the successful SMS attempt
            Log::info('Manual invitation SMS sent successfully', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'attendee_id' => $attendee->id,
                'ticket_id' => $ticket->id,
                'message_id' => $smsResult['messageId'] ?? null,
                'status' => $smsResult['status']['name'] ?? null,
                'reference' => $reference,
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'timestamp' => now()->toIso8601String()
            ]);

            // Return response matching API format
            return response()->json([
                'success' => true,
                'message' => 'SMS sent successfully to ' . $attendee->name,
                'recipient' => $attendee->name,
                'original_phone' => $attendee->phone,
                'formatted_phone' => $formattedPhone,
                'sms_result' => [
                    'to' => $smsResult['to'],
                    'status' => $smsResult['status'],
                    'messageId' => $smsResult['messageId'],
                    'smsCount' => $smsResult['smsCount'],
                    'message' => $smsResult['message']
                ],
                'reference' => $reference
            ]);

        } catch (\Exception $e) {
            // Log comprehensive error details
            Log::error('Manual invitation SMS failed with exception', [
                'user_id' => $userId,
                'event_id' => $event->id,
                'attendee_id' => $request->attendee_id ?? null,
                'ticket_id' => $request->ticket_id ?? null,
                'exception_class' => get_class($e),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage(),
                'recipient' => $attendee->name ?? null,
                'original_phone' => $attendee->phone ?? null,
                'formatted_phone' => $formattedPhone ?? null
            ], 500);
        }
    }

    /**
     * Get default invitation message for a ticket
     */
    private function getDefaultInvitationMessage($ticket): string
    {
        return "You have been invited to {$ticket->event->name}. " .
               "Your ticket code is {$ticket->code}. " .
               "Event date: " . $ticket->event->start_date->format('M d, Y');
    }

    /**
     * Format phone number to Tanzanian format (255XXXXXXXXX)
     *
     * @param string|null $phone
     * @return string|null
     */
    private function formatPhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove any spaces, dashes, parentheses, or other non-digit characters
        $phone = preg_replace('/[^\d]/', '', $phone);

        // If it's already in the correct format (255XXXXXXXXX), return as is
        if (preg_match('/^255\d{9}$/', $phone)) {
            return $phone;
        }

        // If it starts with 0, replace with 255 (Tanzania country code)
        if (preg_match('/^0(\d{9})$/', $phone, $matches)) {
            return '255' . $matches[1];
        }

        // If it's 9 digits, assume it's a local number and add 255
        if (preg_match('/^(\d{9})$/', $phone)) {
            return '255' . $phone;
        }

        // If it's already 12 digits but doesn't start with 255, assume it's already formatted
        if (preg_match('/^(\d{12})$/', $phone)) {
            return $phone;
        }

        // If it starts with +255, remove the + and return
        if (preg_match('/^\+255(\d{9})$/', $phone, $matches)) {
            return '255' . $matches[1];
        }

        // If none of the above patterns match, return null
        return null;
    }

    /**
     * Validate phone number format
     *
     * @param string|null $phone
     * @return bool
     */
    private function isValidPhoneNumber(?string $phone): bool
    {
        $formattedPhone = $this->formatPhoneNumber($phone);
        return $formattedPhone !== null && preg_match('/^255\d{9}$/', $formattedPhone);
    }

    /**
     * Send event reminder SMS
     */
    public function sendEventReminder(Request $request, Event $event)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id'
        ]);

        $ticket = Ticket::findOrFail($request->ticket_id);
        $success = $this->smsService->sendEventReminder($ticket);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Reminder sent successfully' : 'Failed to send reminder'
            ]);
        }

        return redirect()->route('sms.management.event', $event)
            ->with($success ? 'success' : 'error', 
                $success ? 'Reminder sent successfully' : 'Failed to send reminder');
    }

    /**
     * Send ticket usage notification
     */
    public function sendTicketUsageNotification(Request $request, Event $event)
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'entry_point' => 'required|string'
        ]);

        $ticket = Ticket::findOrFail($request->ticket_id);
        $success = $this->smsService->sendTicketUsageNotification($ticket, $request->entry_point);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Notification sent successfully' : 'Failed to send notification'
            ]);
        }

        return redirect()->route('sms.management.event', $event)
            ->with($success ? 'success' : 'error', 
                $success ? 'Notification sent successfully' : 'Failed to send notification');
    }

    /**
     * Get SMS templates
     */
    public function getTemplates(Request $request)
    {
        $templates = SmsTemplate::when($request->type, function($query, $type) {
            $query->where('type', $type);
        })->where('is_active', true)->get();

        if ($request->wantsJson()) {
            return response()->json(['templates' => $templates]);
        }

        return view('sms.management.templates', compact('templates'));
    }

    /**
     * Store a new SMS template
     */
    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'content' => 'required|string|max:160',
            'is_active' => 'boolean'
        ]);

        $template = SmsTemplate::create($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'template' => $template
            ]);
        }

        return redirect()->route('sms.management.templates')
            ->with('success', 'Template created successfully');
    }

    /**
     * Update an SMS template
     */
    public function updateTemplate(Request $request, SmsTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'content' => 'required|string|max:160',
            'is_active' => 'boolean'
        ]);

        $template->update($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'template' => $template
            ]);
        }

        return redirect()->route('sms.management.templates')
            ->with('success', 'Template updated successfully');
    }

    /**
     * Delete an SMS template
     */
    public function deleteTemplate(SmsTemplate $template)
    {
        $template->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('sms.management.templates')
            ->with('success', 'Template deleted successfully');
    }

    /**
     * Get SMS logs for an event
     */
    public function getEventLogs(Event $event, Request $request)
    {
        $logs = SmsLog::whereJsonContains('metadata->event_id', $event->id)
            ->when($request->type, function($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->status, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->search, function($query, $search) {
                $query->where('recipient', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            })
            ->with(['metadata'])
            ->latest()
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json(['logs' => $logs]);
        }

        return view('sms.management.logs', compact('event', 'logs'));
    }

    /**
     * Export SMS logs for an event
     */
    public function exportLogs(Event $event, Request $request)
    {
        $logs = SmsLog::whereJsonContains('metadata->event_id', $event->id)
            ->when($request->type, function($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->status, function($query, $status) {
                $query->where('status', $status);
            })
            ->with(['metadata'])
            ->latest()
            ->get();

        $filename = "sms-logs-{$event->id}-" . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['Type', 'To', 'Message', 'Status', 'Created At']);
            
            // Add data
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->type,
                    $log->recipient,
                    $log->message,
                    $log->status,
                    $log->created_at
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Send a test SMS
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTest(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{1,14}$/'],
            'message' => ['required', 'string', 'max:160'],
        ]);

        try {
            $result = $this->smsService->send(
                $request->phone,
                $request->message,
                'test',
                ['test' => true]
            );

            return response()->json([
                'success' => true,
                'message' => 'Test SMS sent successfully',
                'log_id' => $result['log_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send test SMS: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test SMS: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the status of a message
     *
     * @param string $messageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessageStatus(string $messageId)
    {
        try {
            $status = $this->smsService->getMessageStatus($messageId);

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get message status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display all SMS logs with pagination and filtering.
     *
     * @return \Illuminate\View\View
     */
    public function logs(): View
    {
        $query = SmsLog::query();

        // Apply filters
        if ($type = request('type')) {
            $query->whereJsonContains('metadata->type', $type);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($event = request('event')) {
            $query->whereJsonContains('metadata->event_id', (int)$event);
        }

        if ($search = request('search')) {
            $query->where(function($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhereJsonContains('metadata->attendee_name', $search);
            });
        }

        // Get paginated results
        $logs = $query->latest()
            ->paginate(20)
            ->withQueryString();

        // Add attendee information to each log item
        $logs->getCollection()->transform(function ($log) {
            $metadata = $log->metadata ?? [];
            $log->attendee_name = $metadata['attendee_name'] ?? null;
            $log->attendee_phone = $metadata['attendee_phone'] ?? $log->recipient;
            $log->type = $metadata['type'] ?? 'unknown';
            return $log;
        });

        return view('sms.management.logs', compact('logs'));
    }

    /**
     * Refresh the SMS provider balance
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshBalance(): JsonResponse
    {
        try {
            $balance = $this->smsService->getBalance();
            
            return response()->json([
                'success' => true,
                'balance' => $balance
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to refresh SMS provider balance: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh balance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dispatch invitation SMS job for all attendees
     *
     * @param Event $event
     * @return \Illuminate\Http\JsonResponse
     */
    public function dispatchInvitationSmsJob(Event $event): JsonResponse
    {
        try {
            // Check if user has permission to send SMS for this event
            if (!auth()->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to send SMS for this event.'
                ], 403);
            }

            // Get statistics before dispatching
            $totalAttendees = $event->attendees()->count();
            $attendeesWithPhone = $event->attendees()->whereNotNull('phone')->count();
            $alreadySentCount = $event->attendees()
                ->whereHas('smsLogs', function($query) {
                    $query->whereJsonContains('metadata->type', 'invitation');
                })
                ->count();

            // Dispatch the job
            $job = \App\Jobs\SendInvitationSmsJob::dispatch($event->id, auth()->id());
            $jobId = uniqid('invitation_job_', true);

            // Store job information in cache for tracking
            $jobKey = "sms_job_{$event->id}";
            \Cache::put($jobKey, [
                'job_id' => $jobId,
                'event_id' => $event->id,
                'event_name' => $event->name,
                'user_id' => auth()->id(),
                'total_attendees' => $totalAttendees,
                'attendees_with_phone' => $attendeesWithPhone,
                'already_sent_count' => $alreadySentCount,
                'sent_count' => 0,
                'failed_count' => 0,
                'skipped_count' => 0,
                'status' => 'queued',
                'started_at' => now()->toIso8601String(),
                'completed_at' => null,
                'progress' => 0
            ], 3600); // Cache for 1 hour

            Log::info('Invitation SMS job dispatched', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
                'event_name' => $event->name,
                'job_id' => $jobId,
                'total_attendees' => $totalAttendees,
                'attendees_with_phone' => $attendeesWithPhone,
                'already_sent_count' => $alreadySentCount,
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation SMS job has been queued successfully. SMS will be sent to all attendees who haven\'t received invitations yet.',
                'job_id' => $jobId,
                'statistics' => [
                    'total_attendees' => $totalAttendees,
                    'attendees_with_phone' => $attendeesWithPhone,
                    'already_sent_count' => $alreadySentCount,
                    'will_be_sent_to' => $attendeesWithPhone - $alreadySentCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to dispatch invitation SMS job', [
                'user_id' => auth()->id(),
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch invitation SMS job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start the SMS queue worker
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function startQueue(): JsonResponse
    {
        try {
            // Check if queue is already running
            if ($this->isQueueRunning()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue worker is already running.'
                ], 400);
            }

            // Use the custom command to start the queue worker
            $command = 'php ' . base_path('artisan') . ' sms:queue:start --daemon';
            $output = [];
            $returnCode = 0;
            
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                $pid = \Cache::get('sms_queue_pid');
                
                Log::info('SMS queue worker started', [
                    'user_id' => auth()->id(),
                    'pid' => $pid,
                    'timestamp' => now()->toIso8601String()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'SMS queue worker started successfully.',
                    'pid' => $pid
                ]);
            } else {
                throw new \Exception('Command failed with return code: ' . $returnCode);
            }

        } catch (\Exception $e) {
            Log::error('Failed to start SMS queue worker', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start queue worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop the SMS queue worker
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stopQueue(): JsonResponse
    {
        try {
            $pid = \Cache::get('sms_queue_pid');
            
            if (!$pid) {
                return response()->json([
                    'success' => false,
                    'message' => 'No queue worker PID found.'
                ], 400);
            }

            // Use the custom command to stop the queue worker
            $command = 'php ' . base_path('artisan') . ' sms:queue:stop';
            $output = [];
            $returnCode = 0;
            
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                Log::info('SMS queue worker stopped', [
                    'user_id' => auth()->id(),
                    'pid' => $pid,
                    'timestamp' => now()->toIso8601String()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'SMS queue worker stopped successfully.',
                    'pid' => $pid
                ]);
            } else {
                throw new \Exception('Command failed with return code: ' . $returnCode);
            }

        } catch (\Exception $e) {
            Log::error('Failed to stop SMS queue worker', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to stop queue worker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get queue status and job progress
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQueueStatus(): JsonResponse
    {
        try {
            $isRunning = $this->isQueueRunning();
            $pid = \Cache::get('sms_queue_pid');
            
            // Get active jobs
            $activeJobs = \DB::table('jobs')->count();
            $failedJobs = \DB::table('failed_jobs')->count();
            
            // Get job progress for all events
            $jobProgress = [];
            $cacheKeys = \Cache::get('active_sms_jobs', []);
            
            foreach ($cacheKeys as $jobKey) {
                $jobData = \Cache::get($jobKey);
                if ($jobData) {
                    $jobProgress[] = $jobData;
                }
            }

            return response()->json([
                'success' => true,
                'queue_status' => [
                    'is_running' => $isRunning,
                    'pid' => $pid,
                    'active_jobs' => $activeJobs,
                    'failed_jobs' => $failedJobs
                ],
                'job_progress' => $jobProgress
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get queue status', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get queue status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process one job from the queue (for immediate processing)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function processOneJob(): JsonResponse
    {
        try {
            $output = \Artisan::call('queue:work', ['--once' => true]);
            
            Log::info('Processed one job from queue', [
                'user_id' => auth()->id(),
                'output' => $output,
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Processed one job from queue.',
                'output' => $output
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process job from queue', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toIso8601String()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process job: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if queue worker is running
     *
     * @return bool
     */
    private function isQueueRunning(): bool
    {
        $pid = \Cache::get('sms_queue_pid');
        
        if (!$pid) {
            return false;
        }

        // Check if process is still running
        return file_exists("/proc/{$pid}");
    }
} 