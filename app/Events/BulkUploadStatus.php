<?php

namespace App\Events;

use App\Models\BulkUpload;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BulkUploadStatus implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $bulkUpload;
    public $status;
    public $progress;
    public $message;
    public $errors;

    /**
     * Create a new event instance.
     */
    public function __construct(BulkUpload $bulkUpload, string $status, array $progress = [], string $message = '', array $errors = [])
    {
        $this->bulkUpload = $bulkUpload;
        $this->status = $status;
        $this->progress = $progress;
        $this->message = $message;
        $this->errors = $errors;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('bulk-upload.' . $this->bulkUpload->id),
        ];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'bulk-upload.status';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->bulkUpload->id,
            'status' => $this->status,
            'progress' => $this->progress,
            'message' => $this->message,
            'errors' => $this->errors,
            'total_records' => $this->bulkUpload->total_records,
            'processed_records' => $this->bulkUpload->processed_records,
            'failed_records' => $this->bulkUpload->failed_records,
        ];
    }
} 