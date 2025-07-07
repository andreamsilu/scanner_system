<?php

$y = 80;

// Main Header
$image->text('MWALIKO WA SENDOFF', 400, $y, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#9CAF88');
    $font->size(42);
    $font->align('center');
});

$y += 70;

// Subheader
$image->text('Familia ya MZEE PAUL MSILU', 400, $y, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#5A6B47');
    $font->size(26);
    $font->align('center');
});
$y += 40;

$image->text('wanayo furaha kukualika', 400, $y, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#5A6B47');
    $font->size(20);
    $font->align('center');
});

$y += 50;

// Invitee Name
$image->text('Kwa: ' . strtoupper($ticket->attendee->name), 400, $y, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#2E4E1E');
    $font->size(28);
    $font->align('center');
});

$y += 60;

// Event Description
$image->text('Kwenye sherehe ya SENDOFF ya', 400, $y, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#5A6B47');
    $font->size(20);
    $font->align('center');
});

$y += 35;

$image->text('ANITHA PAUL MSILU', 400, $y, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#D2691E');
    $font->size(34);
    $font->align('center');
});

$y += 60;

// Date, Time, Venue
$image->text('🗓 Tarehe: Ijumaa, 11-07-2025', 400, $y, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#5A6B47');
    $font->size(20);
    $font->align('center');
});

$y += 35;

$image->text('⏰ Saa: 12:00 jioni', 400, $y, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#5A6B47');
    $font->size(20);
    $font->align('center');
});

$y += 35;

$image->text('📍 Mahali: Destiny Hall, Kwa Mathias, Kibaha - Pwani', 400, $y, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#5A6B47');
    $font->size(18);
    $font->align('center');
});

$y += 70;

// Ticket ID and Type
$image->text('🎫 Tiketi: ' . $ticket->token, 400, $y, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#D2691E');
    $font->size(22);
    $font->align('center');
});

$y += 35;

$image->text('Aina ya Tiketi: ' . strtoupper($ticket->type), 400, $y, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#D2691E');
    $font->size(22);
    $font->align('center');
});

$y += 60;

// Add decorative line or small dots as divider (optional)

$image->line([100, $y, 700, $y], function ($draw) {
    $draw->color('#C4D1B3');
    $draw->width(1);
});

$y += 40;

// QR Code
$image->place($qrImage, 'bottom', 400 - 70, 160); // moved up for spacing

// Footer
$image->text('Wasilisha tiketi hii mlangoni', 400, 1040, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#D2691E');
    $font->size(16);
    $font->align('center');
});

$image->text('T-KADI: 0769289824', 400, 1070, function($font) use ($fontPath) {
    $font->filename($fontPath);
    $font->color('#5A6B47');
    $font->size(14);
    $font->align('center');
});
