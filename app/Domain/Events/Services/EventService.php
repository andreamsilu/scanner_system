<?php

namespace App\Domain\Events\Services;

use App\Domain\Events\DTOs\EventData;
use App\Domain\Events\Models\Event;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class EventService implements EventServiceInterface
{
    public function getAllPaginated(int $perPage = 10): LengthAwarePaginator
    {
        return Event::latest()->paginate($perPage);
    }

    public function create(EventData $data): Event
    {
        $attributes = $this->prepareAttributes($data);
        
        if ($data->banner) {
            $attributes['banner'] = $this->storeBanner($data->banner);
        }

        return Event::create($attributes);
    }

    public function update(Event $event, EventData $data): Event
    {
        $attributes = $this->prepareAttributes($data);
        
        if ($data->banner) {
            // Delete old banner if exists
            if ($event->banner) {
                Storage::delete($event->banner);
            }
            $attributes['banner'] = $this->storeBanner($data->banner);
        }

        $event->update($attributes);
        return $event;
    }

    public function delete(Event $event): bool
    {
        if ($event->banner) {
            Storage::disk('public')->delete($event->banner);
        }

        return $event->delete();
    }

    private function prepareAttributes(EventData $data): array
    {
        return [
            'name' => $data->name,
            'description' => $data->description,
            'start_date' => $data->startDate->toString(),
            'end_date' => $data->endDate->toString(),
            'venue' => $data->venue,
            'capacity' => $data->capacity,
            'status' => $data->status->toString(),
        ];
    }

    private function storeBanner($file): string
    {
        return $file->store('event-banners', 'public');
    }
} 