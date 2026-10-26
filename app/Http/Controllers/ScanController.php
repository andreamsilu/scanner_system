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

    public function scan(Request $request)
    {
        return app(AdmissionController::class)->store($request, app(\App\Services\CheckInService::class));
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

    public function checkIn($token)
    {
        return app(TicketController::class)->verify($token);
    }

    public function validateToken(Request $request)
    {
        return $this->scan($request);
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
