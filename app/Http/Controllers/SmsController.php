<?php

namespace App\Http\Controllers;

use App\Services\SmsService;
use App\Models\SmsTemplate;
use App\Models\SmsLog;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SmsController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
        // Apply rate limiting middleware
        $this->middleware('throttle:60,1')->only(['sendAttendeeInvitation', 'sendTicketUsageNotification']);
    }

    /**
     * Send an attendee invitation SMS
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendAttendeeInvitation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:events,id',
            'attendee_id' => 'required|exists:attendees,id',
            'phone' => 'required|string|regex:/^\+[1-9]\d{1,14}$/',
            'user_name' => 'required|string|max:255',
            'ticket_type' => 'required|string|max:50',
            'ticket_price' => 'required|string|max:50',
            'ticket_id' => 'nullable|exists:tickets,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event = Event::findOrFail($request->event_id);
        
        $success = $this->smsService->sendAttendeeInvitation(
            $event,
            $request->only([
                'phone',
                'user_name',
                'ticket_type',
                'ticket_price',
                'attendee_id',
                'ticket_id'
            ])
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Attendee invitation sent successfully' : 'Failed to send attendee invitation'
        ], $success ? 200 : 500);
    }

    /**
     * Send a ticket usage notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTicketUsageNotification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|exists:tickets,id',
            'entry_point' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket = Ticket::with(['attendee', 'event'])->findOrFail($request->ticket_id);
        
        $success = $this->smsService->sendTicketUsageNotification(
            $ticket,
            $request->entry_point
        );

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Ticket usage notification sent successfully' : 'Failed to send ticket usage notification'
        ], $success ? 200 : 500);
    }

    /**
     * Send an event reminder
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendEventReminder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|exists:tickets,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ticket = Ticket::with(['attendee', 'event'])->findOrFail($request->ticket_id);
        
        $success = $this->smsService->sendEventReminder($ticket);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Event reminder sent successfully' : 'Failed to send event reminder'
        ], $success ? 200 : 500);
    }

    /**
     * Get SMS templates
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTemplates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['nullable', 'string', Rule::in(['attendee_invitation', 'ticket_used', 'ticket_reminder'])]
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = SmsTemplate::query();
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $templates = $query->get();

        return response()->json(['templates' => $templates]);
    }

    /**
     * Get SMS logs
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLogs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['nullable', 'string', Rule::in(['attendee_invitation', 'ticket_used', 'ticket_reminder'])],
            'status' => ['nullable', 'string'],
            'event_id' => ['nullable', 'exists:events,id'],
            'ticket_id' => ['nullable', 'exists:tickets,id'],
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = SmsLog::query();
        
        if ($request->has('type')) {
            $query->ofType($request->type);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('event_id')) {
            $query->whereJsonContains('metadata->event_id', $request->event_id);
        }

        if ($request->has('ticket_id')) {
            $query->whereJsonContains('metadata->ticket_id', $request->ticket_id);
        }

        $logs = $query->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json(['logs' => $logs]);
    }
} 