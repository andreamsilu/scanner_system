<?php

namespace App\Domain\Events\DTOs;

use App\Domain\Events\ValueObjects\EventDate;
use App\Domain\Events\ValueObjects\EventStatus;
use Illuminate\Http\UploadedFile;

class EventData
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly EventDate $startDate,
        public readonly EventDate $endDate,
        public readonly string $venue,
        public readonly int $capacity,
        public readonly EventStatus $status,
        public readonly ?UploadedFile $banner = null
    ) {}

    public static function fromRequest(array $data, ?UploadedFile $banner = null): self
    {
        return new self(
            name: $data['name'],
            description: $data['description'],
            startDate: EventDate::fromString($data['start_date']),
            endDate: EventDate::fromString($data['end_date']),
            venue: $data['venue'],
            capacity: (int) $data['capacity'],
            status: EventStatus::fromString($data['status']),
            banner: $banner
        );
    }
} 