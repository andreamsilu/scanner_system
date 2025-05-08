<?php

namespace App\Domain\Events\Services;

use App\Domain\Events\DTOs\EventData;
use App\Domain\Events\Models\Event;
use Illuminate\Pagination\LengthAwarePaginator;

interface EventServiceInterface
{
    public function getAllPaginated(int $perPage = 10): LengthAwarePaginator;
    public function create(EventData $data): Event;
    public function update(Event $event, EventData $data): Event;
    public function delete(Event $event): bool;
} 