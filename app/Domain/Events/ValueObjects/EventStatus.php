<?php

namespace App\Domain\Events\ValueObjects;

class EventStatus
{
    private const DRAFT = 'draft';
    private const PUBLISHED = 'published';
    private const CANCELLED = 'cancelled';
    private const COMPLETED = 'completed';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $validStatuses = [
            self::DRAFT,
            self::PUBLISHED,
            self::CANCELLED,
            self::COMPLETED
        ];

        if (!in_array($value, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid event status: {$value}");
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
} 
 