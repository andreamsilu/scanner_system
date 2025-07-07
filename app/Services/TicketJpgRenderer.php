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
     * Render the ticket as a JPG and return the binary data
     *
     * @param Ticket $ticket
     * @param Event $event
     * @return string JPEG binary data
     */
    public static function render(Ticket $ticket, Event $event): string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->create(800, 1200, '#ffffff');

        // Optional background
        $bgPath = public_path('images/image.png');
        if (file_exists($bgPath)) {
            $bg = $manager->read($bgPath)->cover(800, 1200);
            $image->place($bg, 'top-left', 0, 0);

            // Darker overlay for text readability
            $overlay = $manager->create(800, 1200, [0, 0, 0, 0.4]); // black with opacity
            $image->place($overlay, 'top-left', 0, 0);
        }

        $fontPath = public_path('fonts/georgiab.ttf');
        $y = 80;
        $xCenter = 400;

        // Helper function to simplify text
        $addText = function ($text, $yPos, $size = 20, $color = '#5A6B47') use ($image, $fontPath, $xCenter) {
            $image->text($text, $xCenter, $yPos, function ($font) use ($fontPath, $size, $color) {
                $font->filename($fontPath);
                $font->size($size);
                $font->color($color);
                $font->align('center');
            });
        };

        // Main Title
        $addText('MWALIKO WA SENDOFF', $y, 44, '#9CAF88');
        $y += 70;

        // Host family
        $addText('Familia ya MZEE PAUL MSILU', $y, 26, '#5A6B47');
        $y += 40;

        $addText('inayo furaha kukualika', $y, 20);
        $y += 50;

        // Invitee name
        $addText('Kwa: ' . strtoupper($ticket->attendee->name), $y, 28, '#2E4E1E');
        $y += 60;

        // Event subject
        $addText('Kwenye sherehe ya SENDOFF ya', $y);
        $y += 35;

        $addText('ANITHA PAUL MSILU', $y, 34, '#D2691E');
        $y += 60;

        // Date & time
        $addText('🗓 Tarehe: Ijumaa, 11-07-2025', $y);
        $y += 35;

        $addText('⏰ Saa: 12:00 jioni', $y);
        $y += 35;

        // Venue
        $addText('📍 Mahali: Destiny Hall, Kwa Mathias, Kibaha - Pwani', $y, 18);
        $y += 70;

        // Ticket info
        $addText('🎫 Tiketi: ' . $ticket->token, $y, 22, '#D2691E');
        $y += 35;

        $addText('Aina ya Tiketi: ' . strtoupper($ticket->type), $y, 22, '#D2691E');
        $y += 50;

        // Divider
        $addText('------------------------------', $y, 18, '#C4D1B3');
        $y += 40;

        // QR label
        $addText('Scan to Verify Ticket', $y, 16, '#2E4E1E');
        $y += 10;

        // QR Code
        $qrCode = QrCode::format('png')
            ->size(140)
            ->generate(route('tickets.verify', $ticket->token));

        $tempQrPath = tempnam(sys_get_temp_dir(), 'qr_');
        file_put_contents($tempQrPath, $qrCode);
        $qrImage = $manager->read($tempQrPath);
        $image->place($qrImage, 'top-left', 330, $y);
        unlink($tempQrPath);

        $y += 160;

        // Footer
        $addText('Wasilisha tiketi hii mlangoni', $y, 16, '#D2691E');
        $y += 30;

        $addText('T-KADI: 0769289824', $y, 14);

        return $image->toJpeg(90);
    }
}
