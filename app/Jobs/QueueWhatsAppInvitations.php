<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QueueWhatsAppInvitations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(public int $eventId, public int $userId) {}

    public function handle(WhatsAppService $service): void
    {
        $actor = User::find($this->userId);
        $event = Event::find($this->eventId);
        if (! $actor?->is_active || ! $actor->isAdmin() || ! $event || $service->setupIssues()) {
            return;
        }
        Ticket::where('event_id', $event->id)->where('status', 'active')
            ->whereHas('attendee', fn ($query) => $query->whereNotNull('whatsapp_opted_in_at')->where('registration_status', '!=', 'cancelled'))
            ->whereDoesntHave('whatsappInvitation')->with(['attendee', 'event'])->chunkById(100, function ($tickets) use ($service, $actor) {
                foreach ($tickets as $ticket) {
                    $service->queue($ticket, $actor);
                }
            });
    }
}
