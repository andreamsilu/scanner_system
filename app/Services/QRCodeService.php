<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QRCodeService
{
    /**
     * Generate a QR code for an attendee using Google Charts API
     *
     * @param string $token The attendee's token
     * @param string $name The attendee's name
     * @param string $eventName The event name
     * @return string The path to the generated QR code
     */
    public function generateQRCode(string $token, string $name, string $eventName): string
    {
        // Generate a unique filename
        $filename = Str::slug($name) . '-' . $token . '.png';
        $path = 'qr-codes/' . $filename;

        // Generate QR code with check-in URL
        $checkInUrl = route('scanner.check-in', ['token' => $token]);
        
        // Use Google Charts API to generate QR code
        $qrCodeUrl = 'https://chart.googleapis.com/chart?' . http_build_query([
            'cht' => 'qr',
            'chs' => '300x300',
            'chl' => $checkInUrl,
            'choe' => 'UTF-8',
            'chld' => 'H|1', // Error correction level H (high) and margin 1
        ]);

        // Download the QR code
        $response = Http::get($qrCodeUrl);
        
        if ($response->successful()) {
            // Store the QR code
            Storage::put('public/' . $path, $response->body());
            return $path;
        }

        throw new \RuntimeException('Failed to generate QR code');
    }

    /**
     * Delete a QR code
     *
     * @param string $path The path to the QR code
     * @return bool Whether the deletion was successful
     */
    public function deleteQRCode(string $path): bool
    {
        return Storage::delete('public/' . $path);
    }
} 