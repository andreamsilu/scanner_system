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
     * Generate a QR code locally for an attendee.
     *
     * @param string $token The attendee's token
     * @param string $name The attendee's name
     * @param string $eventName The event name
     * @return string The path to the generated QR code
     */
    public function generateQRCode(string $token, string $name, string $eventName): string
    {
        // Generate locally so admission credentials are never sent to an external QR service.
        $path = 'qr-codes/' . Str::slug($name) . '-' . $token . '.svg';
        $image = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(300)->errorCorrection('H')
            ->generate(route('tickets.verify', ['token' => $token]));
        if (!Storage::disk('public')->put($path, (string) $image)) {
            throw new \RuntimeException('Unable to store the QR code.');
        }
        return $path;
    }

    /**
     * Delete a QR code
     *
     * @param string $path The path to the QR code
     * @return bool Whether the deletion was successful
     */
    public function deleteQRCode(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }

    public function png(string $token, int $size = 300): string
    {
        $matrix = \BaconQrCode\Encoder\Encoder::encode(
            route('tickets.verify', ['token' => $token]),
            \BaconQrCode\Common\ErrorCorrectionLevel::H(), 'UTF-8'
        )->getMatrix();
        $scale = max(1, intdiv($size, $matrix->getWidth() + 8));
        $dimension = ($matrix->getWidth() + 8) * $scale;
        $image = imagecreatetruecolor($dimension, $dimension);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        $black = imagecolorallocate($image, 0, 0, 0);
        for ($y = 0; $y < $matrix->getHeight(); $y++) {
            for ($x = 0; $x < $matrix->getWidth(); $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $left = ($x + 4) * $scale;
                    $top = ($y + 4) * $scale;
                    imagefilledrectangle($image, $left, $top, $left + $scale - 1, $top + $scale - 1, $black);
                }
            }
        }
        ob_start();
        try {
            imagepng($image);
            return ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }
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
