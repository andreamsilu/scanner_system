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

        // Add background image if exists
        $bgPath = public_path('images/image.png');
        if (file_exists($bgPath)) {
            $bg = $manager->read($bgPath)->cover(800, 1200);
            $image->place($bg, 'top-left', 0, 0);

            // Add a semi-transparent white overlay for better readability
            $overlay = $manager->create(800, 1200, [255, 255, 255, 0.82]);
            $image->place($overlay, 'top-left', 0, 0);
        }

        $fontPath = public_path('fonts/georgiab.ttf');
        $y = 80;

        // Main Header
        $image->text('MWALIKO WA SENDOFF', 400, $y, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#9CAF88');
            $font->size(42);
            $font->align('center');
        });

        $y += 70;

        // Subheader
        $image->text('Familia ya MZEE PAUL MSILU', 400, $y, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(26);
            $font->align('center');
        });

        $y += 40;

        $image->text('wanayo furaha kukualika', 400, $y, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(20);
            $font->align('center');
        });

        $y += 50;

        // Invitee Name
        $image->text('Kwa: ' . strtoupper($ticket->attendee->name), 400, $y, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#2E4E1E');
            $font->size(28);
            $font->align('center');
        });

        $y += 60;

        // Event Description
        $image->text('Kwenye sherehe ya SENDOFF ya', 400, $y, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(20);
            $font->align('center');
        });

        $y += 35;

        $image->text('ANITHA PAUL MSILU', 400, $y, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#D2691E');
            $font->size(34);
            $font->align('center');
        });

        $y += 60;

        // Date, Time, Venue
        $image->text('🗓 Tarehe: Ijumaa, 11-07-2025', 400, $y, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(20);
            $font->align('center');
        });

        $y += 35;

        $image->text('⏰ Saa: 12:00 jioni', 400, $y, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(20);
            $font->align('center');
        });

        $y += 35;

        $image->text('📍 Mahali: Destiny Hall, Kwa Mathias, Kibaha - Pwani', 400, $y, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(18);
            $font->align('center');
        });

        $y += 70;

        // Ticket ID and Type
        $image->text('🎫 Tiketi: ' . $ticket->token, 400, $y, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#D2691E');
            $font->size(22);
            $font->align('center');
        });

        $y += 35;

        $image->text('Aina ya Tiketi: ' . strtoupper($ticket->type), 400, $y, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#D2691E');
            $font->size(22);
            $font->align('center');
        });

        $y += 60;

        // Optional Divider Line
        $image->line([100, $y, 700, $y], function ($draw) {
            $draw->color('#C4D1B3');
            $draw->width(1);
        });

        $y += 40;

        // QR Code
        $qrCode = QrCode::format('png')
            ->size(140)
            ->generate(route('tickets.verify', $ticket->token));
        $tempQrPath = tempnam(sys_get_temp_dir(), 'qr_');
        file_put_contents($tempQrPath, $qrCode);
        $qrImage = $manager->read($tempQrPath);
        $image->place($qrImage, 'bottom', 400 - 70, 160); // Moved up from bottom
        unlink($tempQrPath);

        // Footer
        $image->text('Wasilisha tiketi hii mlangoni', 400, 1040, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#D2691E');
            $font->size(16);
            $font->align('center');
        });

        $image->text('T-KADI: 0769289824', 400, 1070, function ($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(14);
            $font->align('center');
        });

        return $image->toJpeg(90);
    }
}
