<?php

namespace App\Jobs;

use App\Models\WhatsAppInvitation;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppInvitation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;
    public int $maxExceptions = 1;
    public int $timeout = 45;
    public bool $failOnTimeout = true;

    public function __construct(public int $invitationId) {}

    public function middleware(): array
    {
        return [new RateLimited('whatsapp-invitations')];
    }

    public function handle(WhatsAppService $service): void
    {
        $service->send($this->invitationId);
    }

    public function failed(?\Throwable $exception): void
    {
        WhatsAppInvitation::whereKey($this->invitationId)->where('status', 'queued')->update([
            'status' => 'failed', 'error_message' => 'Queue processing failed before sending.',
        ]);
        WhatsAppInvitation::whereKey($this->invitationId)->where('status', 'sending')->update([
            'status' => 'unknown', 'error_message' => 'Worker stopped during sending. Check Meta before trying again.',
        ]);
    }
}
