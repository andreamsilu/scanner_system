<?php

namespace App\Jobs;

use App\Models\BulkUpload;
use App\Services\BulkAttendeeImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessBulkUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 30;

    public function __construct(public int $uploadId, public string $path) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping('bulk-upload:'.$this->uploadId))
            ->dontRelease()->expireAfter(330)];
    }

    public function handle(BulkAttendeeImporter $importer): void
    {
        $importer->import(BulkUpload::findOrFail($this->uploadId), $this->path);
    }

    public function failed(?\Throwable $exception): void
    {
        BulkUpload::whereKey($this->uploadId)->update([
            'status' => 'failed', 'error_message' => 'Import could not finish. Contact an administrator to retry.',
            'completed_at' => now(),
        ]);
    }
}
