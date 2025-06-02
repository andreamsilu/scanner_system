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
        $templates = SmsTemplate::where('is_active', true)->get();
        
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

        $smsStats = SmsLog::whereJsonContains('metadata->event_id', $event->id)
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        $recentLogs = SmsLog::whereJsonContains('metadata->event_id', $event->id)
            ->with(['metadata'])
            ->latest()
            ->take(10)
            ->get();

        return view('sms.management.event', compact(
            'event',
            'templates',
            'attendees',
            'smsStats',
            'recentLogs'
        ));
    }

    /**
     * Send SMS to selected attendees
     */
    public function sendBulkSms(Request $request, Event $event)
    {
        $request->validate([
            'attendee_ids' => 'required|array',
            'attendee_ids.*' => 'exists:attendees,id',
            'template_id' => 'required|exists:sms_templates,id',
            'message' => 'required|string|max:160'
        ]);

        $template = SmsTemplate::findOrFail($request->template_id);
        $attendees = $event->attendees()->whereIn('id', $request->attendee_ids)->get();
        
        $successCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($attendees as $attendee) {
            $ticket = $attendee->tickets()->where('event_id', $event->id)->first();
            
            if (!$ticket || !$attendee->phone) {
                $failedCount++;
                $results[] = [
                    'attendee' => $attendee->name,
                    'status' => 'failed',
                    'reason' => !$attendee->phone ? 'No phone number' : 'No ticket found'
                ];
                continue;
            }

            $data = [
                'user_name' => $attendee->name,
                'event_name' => $event->name,
                'date' => $event->date->format('F j, Y g:i A'),
                'location' => $event->location,
                'ticket_number' => $ticket->ticket_number,
                'ticket_type' => $ticket->type,
                'ticket_price' => $ticket->price
            ];

            $success = $this->smsService->send(
                $attendee->phone,
                $request->message,
                $template->type,
                [
                    'event_id' => $event->id,
                    'attendee_id' => $attendee->id,
                    'ticket_id' => $ticket->id
                ]
            );

            if ($success) {
                $successCount++;
                $results[] = [
                    'attendee' => $attendee->name,
                    'status' => 'success'
                ];
            } else {
                $failedCount++;
                $results[] = [
                    'attendee' => $attendee->name,
                    'status' => 'failed',
                    'reason' => 'SMS sending failed'
                ];
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Sent {$successCount} messages successfully, {$failedCount} failed",
                'results' => $results
            ]);
        }

        return redirect()->route('sms.management.event', $event)
            ->with('success', "Sent {$successCount} messages successfully, {$failedCount} failed")
            ->with('results', $results);
    }

    /**
     * Send invitation SMS to an attendee
     */
    public function sendAttendeeInvitation(Request $request, Event $event)
    {
        $request->validate([
            'attendee_id' => 'required|exists:attendees,id',
            'ticket_id' => 'required|exists:tickets,id'
        ]);

        $attendee = $event->attendees()->findOrFail($request->attendee_id);
        $ticket = $attendee->tickets()->findOrFail($request->ticket_id);

        $success = $this->smsService->sendAttendeeInvitation($attendee, $event, $ticket);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $success ? 'Invitation sent successfully' : 'Failed to send invitation'
            ]);
        }

        return redirect()->route('sms.management.event', $event)
            ->with($success ? 'success' : 'error', 
                $success ? 'Invitation sent successfully' : 'Failed to send invitation');
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
                $query->where('to', 'like', "%{$search}%")
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
                    $log->to,
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
                $q->where('to', 'like', "%{$search}%")
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
            $log->attendee_phone = $metadata['attendee_phone'] ?? $log->to;
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