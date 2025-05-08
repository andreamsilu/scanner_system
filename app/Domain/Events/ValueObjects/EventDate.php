<?php

namespace App\Domain\Events\ValueObjects;

use DateTimeImmutable;

class EventDate
{
    private DateTimeImmutable $value;

    private function __construct(DateTimeImmutable $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $date): self
    {
        try {
            $dateTime = new DateTimeImmutable($date);
            return new self($dateTime);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid date format: {$date}");
        }
    }

    public function toDateTime(): DateTimeImmutable
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value->format('Y-m-d H:i:s');
    }

    public function equals(self $other): bool
    {
        return $this->value->getTimestamp() === $other->value->getTimestamp();
    }
} 