<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Event;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TicketJpgRenderer
{
    /**
     * Render the ticket as a JPG and return the binary data.
     *
     * @param Ticket $ticket
     * @param Event $event
     * @return string JPEG binary data
     */
    public static function render(Ticket $ticket, Event $event): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->create(800, 1200, '#ffffff');

        // === Background ===
        $bgPath = public_path('images/image.png');
        if (file_exists($bgPath)) {
            $bg = $manager->make($bgPath)->fit(800, 1200);
            $image->insert($bg, 'top-left', 0, 0);

            // === White semi-transparent overlay covering whole card ===
            $image->rectangle(0, 0, 800, 1200, function ($draw) {
                // Alpha: 0 (opaque) to 127 (fully transparent)
                // 90 ~ about 70% opacity white overlay
                $draw->background([255, 255, 255, 90]);
            });
        }

        $fontPath = public_path('fonts/georgiab.ttf');
        $y = 80;
        $xCenter = 400;

        // Helper to place centered text
        $addText = function (string $text, int &$y, int $size = 20, string $color = '#5A6B47', int $gap = 30) use ($image, $fontPath, $xCenter) {
            $image->text($text, $xCenter, $y, function ($font) use ($fontPath, $size, $color) {
                $font->file($fontPath);
                $font->size($size);
                $font->color($color);
                $font->align('center');
                $font->valign('top');
            });
            $y += $gap;
        };

        // === Main Content ===
        $addText('MWALIKO WA SENDOFF', $y, 38, '#9CAF88', 50);
        $addText('Familia ya MZEE PAUL MSILU', $y, 24, '#5A6B47', 30);
        $addText('inayo furaha kukualika', $y, 18, '#5A6B47', 26);
        $addText('Kwa: ' . strtoupper($ticket->attendee->name), $y, 24, '#2E4E1E', 36);
        $addText('Kwenye sherehe ya SENDOFF ya', $y, 18, '#5A6B47', 26);
        $addText('ANITHA PAUL MSILU', $y, 30, '#D2691E', 40);
        $addText('🗓 Tarehe: Ijumaa, 11-07-2025', $y, 18, '#5A6B47', 24);
        $addText('⏰ Saa: 12:00 jioni', $y, 18, '#5A6B47', 24);
        $addText('📍 Mahali: Destiny Hall, Kwa Mathias, Kibaha - Pwani', $y, 16, '#5A6B47', 34);
        $addText('🎫 Tiketi: ' . $ticket->token, $y, 20, '#D2691E', 28);
        $addText('Aina ya Tiketi: ' . strtoupper($ticket->type), $y, 20, '#D2691E', 28);
        $addText('------------------------------', $y, 16, '#C4D1B3', 26);
        $addText('Scan to Verify Ticket', $y, 14, '#2E4E1E', 20);

        // === QR Code ===
        $qrCode = QrCode::format('png')
            ->size(130)
            ->generate(route('tickets.verify', $ticket->token));
        $tempQrPath = tempnam(sys_get_temp_dir(), 'qr_');
        file_put_contents($tempQrPath, $qrCode);
        $qrImage = $manager->make($tempQrPath);
        $image->insert($qrImage, 'top-left', 335, $y);
        unlink($tempQrPath);

        $y += 150;

        // === Footer ===
        $addText('Wasilisha tiketi hii mlangoni', $y, 14, '#D2691E', 24);
        $addText('T-KADI: 0769289824', $y, 14, '#5A6B47', 20);

        // === Optional border frame ===
        $borderPath = public_path('images/border-frame.png');
        if (file_exists($borderPath)) {
            $frame = $manager->make($borderPath)->resize(800, 1200);
            $image->insert($frame, 'top-left', 0, 0);
        }

        return $image->encode('jpg', 90)->getEncoded();
    }
}
 