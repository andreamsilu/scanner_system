<?php

namespace App\Services;

use Illuminate\Support\Str;

final class TicketToken
{
    public static function generate(): string
    {
        return Str::random(48);
    }

    public static function extract(string $value): string
    {
        $value = trim($value);
        if (str_starts_with($value, '{')) {
            $data = json_decode($value, true);

            return is_array($data) && is_string($data['token'] ?? null) ? $data['token'] : '';
        }
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $parts = explode('/', trim(parse_url($value, PHP_URL_PATH) ?? '', '/'));
            if (end($parts) === 'verify') {
                array_pop($parts);
            }

            return (string) end($parts);
        }

        return $value;
    }
}
