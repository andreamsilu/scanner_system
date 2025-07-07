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

        // Add background image
        $bgPath = public_path('images/image.png');
        if (file_exists($bgPath)) {
            $bg = $manager->read($bgPath)->cover(800, 1200);
            $image->place($bg, 'top-left', 0, 0);
            // Add a white semi-transparent overlay for maximum readability
            $overlay = $manager->create(800, 1200, [255,255,255,0.82]);
            $image->place($overlay, 'top-left', 0, 0);
        }

        $fontPath = public_path('fonts/georgiab.ttf');
        $y = 100;
        $image->text('MWALIKO WA SENDOFF', 400, $y, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#9CAF88');
            $font->size(40);
            $font->align('center');
        });
        $y += 60;
        $image->text('Familia ya MR & MRS PIENZI CHAMBILA', 400, $y, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(22);
            $font->align('center');
        });
        $y += 30;
        $image->text('wanayo furaha kukualika', 400, $y, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(18);
            $font->align('center');
        });
        $y += 40;
        $image->text('Kwa: ' . strtoupper($ticket->attendee->name), 400, $y, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(26);
            $font->align('center');
        });
        $y += 50;
        $image->text('Kwenye sherehe ya SENDOFF ya', 400, $y, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(18);
            $font->align('center');
        });
        $y += 30;
        $image->text('ALICE PIENZI CHAMBILA', 400, $y, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#D2691E');
            $font->size(32);
            $font->align('center');
        });
        $y += 50;
        $image->text('Tarehe: Alhamisi, 05-06-2025', 400, $y, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(18);
            $font->align('center');
        });
        $y += 30;
        $image->text('Saa: 12:00 jioni', 400, $y, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(18);
            $font->align('center');
        });
        $y += 30;
        $image->text('Mahali: GBF Garden Hall, Dar es Salaam', 400, $y, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(18);
            $font->align('center');
        });
        $y += 60;
        $image->text('Tiketi: ' . $ticket->token, 400, $y, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#D2691E');
            $font->size(22);
            $font->align('center');
        });
        $y += 30;
        $image->text('Aina: ' . strtoupper($ticket->type), 400, $y, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#D2691E');
            $font->size(22);
            $font->align('center');
        });
        $y += 60;

        // QR code
        $qrCode = QrCode::format('png')
            ->size(140)
            ->generate(route('tickets.verify', $ticket->token));
        $tempQrPath = tempnam(sys_get_temp_dir(), 'qr_');
        file_put_contents($tempQrPath, $qrCode);
        $qrImage = $manager->read($tempQrPath);
        $image->place($qrImage, 'bottom', 400 - 70, 80);
        unlink($tempQrPath);

        // Footer
        $image->text('Wasilisha tiketi hii mlangoni', 400, 1050, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#D2691E');
            $font->size(16);
            $font->align('center');
        });
        $image->text('Maelezo: 0769289824', 400, 1080, function($font) use ($fontPath) {
            $font->filename($fontPath);
            $font->color('#5A6B47');
            $font->size(14);
            $font->align('center');
        });

        return $image->toJpeg(90);
    }
} 