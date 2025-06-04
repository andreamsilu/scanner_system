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

            return view('sms.management.index', compact('balance', 'provider', 'recentLogs'));
        } catch (\Exception $e) {
            Log::error('SMS Management Dashboard Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return view('sms.management.index', [
                'error' => 'Unable to load SMS management dashboard. Please try again later.',
                'recentLogs' => collect() // Empty collection as fallback
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
        try {
            // Rate limiting: max 100 SMS per minute per user
            $executed = RateLimiter::attempt(
                'bulk-sms:' . auth()->id(),
                $perMinute = 100,
                function() {
                    return true;
                }
            );

            if (!$executed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many SMS requests. Please try again in a minute.'
                ], 429);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'attendee_ids' => 'required|string', // We receive it as JSON string
                'message_type' => ['required', Rule::in(['invitation', 'reminder', 'notification'])],
                'custom_message' => 'nullable|string|max:160'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Parse the JSON string to array
            $attendeeIds = json_decode($request->attendee_ids, true);
            
            if (!is_array($attendeeIds)) {
                throw new \Exception('Invalid attendee_ids format');
            }

            $results = [];
            $successCount = 0;
            $failureCount = 0;

            // Get all attendees in one query
            $attendees = $event->attendees()
                ->whereIn('id', $attendeeIds)
                ->with(['tickets' => function($query) use ($event) {
                    $query->where('event_id', $event->id);
                }])
                ->get();

            foreach ($attendees as $attendee) {
                try {
                    if (!$this->isValidPhoneNumber($attendee->phone)) {
                        $results[] = [
                            'success' => false,
                            'recipient' => $attendee->name,
                            'message' => 'Invalid phone number format'
                        ];
                        $failureCount++;
                        continue;
                    }

                    $ticket = $attendee->tickets->first();
                    if (!$ticket) {
                        $results[] = [
                            'success' => false,
                            'recipient' => $attendee->name,
                            'message' => 'No valid ticket found for this event'
                        ];
                        $failureCount++;
                        continue;
                    }

                    // Send SMS based on message type
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
                    Log::error('Failed to send SMS to attendee', [
                        'attendee_id' => $attendee->id,
                        'event_id' => $event->id,
                        'error' => $e->getMessage()
                    ]);

                    $results[] = [
                        'success' => false,
                        'recipient' => $attendee->name,
                        'message' => 'Error: ' . $e->getMessage()
                    ];
                    $failureCount++;
                }
            }

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
            Log::error('Bulk SMS send failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'event_id' => $event->id
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
        try {
            // Rate limiting: max 10 SMS per minute per user
            $executed = RateLimiter::attempt(
                'single-sms:' . auth()->id(),
                $perMinute = 10,
                function() {
                    return true;
                }
            );

            if (!$executed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many SMS requests. Please try again in a minute.'
                ], 429);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'attendee_id' => 'required|exists:attendees,id',
                'ticket_id' => 'required|exists:tickets,id',
                'custom_message' => 'nullable|string|max:160'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get attendee and ticket
            $attendee = $event->attendees()->findOrFail($request->attendee_id);
            $ticket = $attendee->tickets()->findOrFail($request->ticket_id);

            // Validate phone number
            if (!$this->isValidPhoneNumber($attendee->phone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number format for ' . $attendee->name
                ], 422);
            }

            // Prepare SMS payload
            $message = $request->custom_message ?? $this->getDefaultInvitationMessage($ticket);
            $reference = 'EVT-' . $event->id . '-' . $ticket->id . '-' . time();
            
            $payload = [
                'from' => 'SENDOFF',
                'to' => $attendee->phone,
                'text' => $message,
                'reference' => $reference
            ];

            // Send SMS using the service
            $result = $this->smsService->send(
                $payload['to'],
                $payload['text'],
                'invitation',
                [
                    'event_id' => $event->id,
                    'ticket_id' => $ticket->id,
                    'attendee_id' => $attendee->id,
                    'reference' => $reference
                ]
            );

            // Check if we got a valid response with messages array
            if (!isset($result['messages']) || !is_array($result['messages']) || empty($result['messages'])) {
                throw new \Exception('Invalid response from SMS service');
            }

            $smsResult = $result['messages'][0]; // Get first message result

            // Log the SMS attempt
            Log::info('SMS invitation sent', [
                'attendee_id' => $attendee->id,
                'ticket_id' => $ticket->id,
                'event_id' => $event->id,
                'message_id' => $smsResult['messageId'] ?? null,
                'status' => $smsResult['status']['name'] ?? null,
                'reference' => $reference
            ]);

            // Return response matching API format
            return response()->json([
                'success' => true,
                'message' => 'SMS sent successfully to ' . $attendee->name,
                'recipient' => $attendee->name,
                'phone' => $attendee->phone,
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
            Log::error('Failed to send invitation SMS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attendee_id' => $request->attendee_id,
                'ticket_id' => $request->ticket_id,
                'event_id' => $event->id,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage(),
                'recipient' => $attendee->name ?? null,
                'phone' => $attendee->phone ?? null
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
     * Validate phone number format
     *
     * @param string|null $phone
     * @return bool
     */
    private function isValidPhoneNumber(?string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }

        // Remove any spaces, dashes, or parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Must start with + and country code
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $phone)) {
            return false;
        }

        // Additional country-specific validation can be added here
        // For Tanzania numbers (starting with +255)
        if (strpos($phone, '+255') === 0) {
            // Must be 12 digits after +255
            return strlen($phone) === 13;
        }

        return true;
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
            $query->where('type', $type);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($provider = request('provider')) {
            $query->where('provider', $provider);
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
} 