<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Attendee;
use Illuminate\Support\Facades\Log;

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
        try {
            // Generate a unique filename
            $filename = Str::slug($name) . '-' . $token . '.png';
            $path = 'qr-codes/' . $filename;

            // Generate QR code with check-in URL using absolute URL
            $checkInUrl = config('app.url') . '/check-in/' . $token;
            
            // Use QR Server API to generate QR code
            $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query([
                'size' => '300x300',
                'data' => $checkInUrl,
                'format' => 'png',
                'margin' => 1,
                'ecc' => 'H' // Error correction level H (high)
            ]);

            // Download the QR code
            $response = Http::timeout(30)->get($qrCodeUrl);
            
            if ($response->successful()) {
                // Store the QR code
                Storage::put('public/' . $path, $response->body());
                return $path;
            }

            Log::error('QR code generation failed', [
                'token' => $token,
                'name' => $name,
                'event' => $eventName,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
                'qr_code_url' => $qrCodeUrl
            ]);

            throw new \RuntimeException('Failed to generate QR code: ' . $response->status());
        } catch (\Exception $e) {
            Log::error('QR code generation error', [
                'error' => $e->getMessage(),
                'token' => $token,
                'name' => $name,
                'event' => $eventName
            ]);
            throw $e;
        }
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

    /**
     * Generate a QR code for an attendee
     *
     * @param \App\Models\Attendee $attendee The attendee model
     * @return string The path to the generated QR code
     */
    public function generateForAttendee(Attendee $attendee): string
    {
        return $this->generateQRCode(
            $attendee->token,
            $attendee->name,
            $attendee->event->name
        );
    }
} 