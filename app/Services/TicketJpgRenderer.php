<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Event;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TicketJpgRenderer
{
    private const CANVAS_WIDTH = 800;
    private const CANVAS_HEIGHT = 1200;
    private const QR_SIZE = 130;
    private const MARGIN = 40;
    
    private ImageManager $manager;
    private array $config;
    
    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
        $this->config = config('ticket.design', []);
    }

    /**
     * Render the ticket as a JPG and return the binary data.
     *
     * @param Ticket $ticket
     * @param Event $event
     * @return string JPEG binary data
     * @throws \Exception
     */
    public function render(Ticket $ticket, Event $event): string
    {
        try {
            $image = $this->createBaseImage();
            $this->addBackground($image);
            $this->addContent($image, $ticket, $event);
            $this->addQrCode($image, $ticket);
            $this->addBorder($image);
            
            return $image->toJpeg(90);
        } catch (\Exception $e) {
            Log::error('Ticket rendering failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function createBaseImage()
    {
        return $this->manager->create(self::CANVAS_WIDTH, self::CANVAS_HEIGHT, '#ffffff');
    }

    private function addBackground($image): void
    {
        $bgPath = $this->getAssetPath('images/image.png');
        if (!$bgPath || !file_exists($bgPath)) {
            Log::warning('Background image not found', ['path' => $bgPath]);
            return;
        }

        try {
            $bg = $this->manager->read($bgPath)->cover(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
            $image->place($bg, 0, 0);

            // Add semi-transparent overlay
            $overlay = $this->manager->create(
                self::CANVAS_WIDTH, 
                self::CANVAS_HEIGHT, 
                'rgba(255,255,255,0.35)'
            );
            $image->place($overlay, 0, 0);
        } catch (\Exception $e) {
            Log::error('Failed to add background', ['error' => $e->getMessage()]);
        }
    }

    private function addContent($image, Ticket $ticket, Event $event): void
    {
        $fontPath = $this->getFontPath();
        $y = 80;
        $xCenter = self::CANVAS_WIDTH / 2;

        $textRenderer = new TicketTextRenderer($image, $fontPath, $xCenter);
        
        // Main content with better spacing and responsive text
        $textRenderer->addText('MWALIKO WA SENDOFF', $y, 38, '#9CAF88', 50);
        $textRenderer->addText('Familia ya MZEE PAUL MSILU', $y, 24, '#5A6B47', 30);
        $textRenderer->addText('inayo furaha kukualika', $y, 18, '#5A6B47', 26);
        
        // Dynamic attendee name with word wrapping
        $attendeeName = 'Kwa: ' . strtoupper($ticket->attendee->name);
        $textRenderer->addWrappedText($attendeeName, $y, 24, '#2E4E1E', 36, self::CANVAS_WIDTH - 80);
        
        $textRenderer->addText('Kwenye sherehe ya SENDOFF ya', $y, 18, '#5A6B47', 26);
        $textRenderer->addText('ANITHA PAUL MSILU', $y, 30, '#D2691E', 40);
        
        // Dynamic event details
        $eventDate = $event->formatted_date ?? 'Ijumaa, 11-07-2025';
        $eventTime = $event->formatted_time ?? '12:00 jioni';
        $venue = $event->venue ?? 'Destiny Hall, Kwa Mathias, Kibaha - Pwani';
        
        $textRenderer->addText("🗓 Tarehe: {$eventDate}", $y, 18, '#5A6B47', 24);
        $textRenderer->addText("⏰ Saa: {$eventTime}", $y, 18, '#5A6B47', 24);
        $textRenderer->addWrappedText("📍 Mahali: {$venue}", $y, 16, '#5A6B47', 34, self::CANVAS_WIDTH - 80);
        
        // Ticket details
        $textRenderer->addText("🎫 Tiketi: {$ticket->token}", $y, 20, '#D2691E', 28);
        $textRenderer->addText('Aina ya Tiketi: ' . strtoupper($ticket->type), $y, 20, '#D2691E', 28);
        
        // Separator with better styling
        $textRenderer->addText(str_repeat('─', 30), $y, 16, '#C4D1B3', 26);
        $textRenderer->addText('Scan to Verify Ticket', $y, 14, '#2E4E1E', 20);
    }

    private function addQrCode($image, Ticket $ticket): void
    {
        try {
            $qrCode = QrCode::format('png')
                ->size(self::QR_SIZE)
                ->errorCorrection('M')
                ->margin(2)
                ->generate(route('tickets.verify', $ticket->token));
            
            $tempQrPath = tempnam(sys_get_temp_dir(), 'qr_');
            file_put_contents($tempQrPath, $qrCode);
            
            $qrImage = $this->manager->read($tempQrPath);
            $qrX = (self::CANVAS_WIDTH - self::QR_SIZE) / 2;
            $qrY = 880; // Fixed position for QR code
            
            $image->place($qrImage, $qrX, $qrY);
            
            // Add QR code background for better visibility
            $qrBg = $this->manager->create(
                self::QR_SIZE + 20, 
                self::QR_SIZE + 20, 
                'rgba(255,255,255,0.9)'
            );
            $image->place($qrBg, $qrX - 10, $qrY - 10);
            $image->place($qrImage, $qrX, $qrY);
            
            unlink($tempQrPath);
        } catch (\Exception $e) {
            Log::error('QR code generation failed', ['error' => $e->getMessage()]);
        }
    }

    private function addBorder($image): void
    {
        $borderPath = $this->getAssetPath('images/border-frame.png');
        if (!$borderPath || !file_exists($borderPath)) {
            return;
        }

        try {
            $frame = $this->manager->read($borderPath)->resize(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
            $image->place($frame, 0, 0);
        } catch (\Exception $e) {
            Log::error('Failed to add border', ['error' => $e->getMessage()]);
        }
    }

    private function getFontPath(): string
    {
        $fontPath = $this->getAssetPath('fonts/georgiab.ttf');
        
        if (!$fontPath || !file_exists($fontPath)) {
            Log::warning('Font file not found, using default', ['path' => $fontPath]);
            return ''; // Will use default system font
        }
        
        return $fontPath;
    }

    private function getAssetPath(string $path): ?string
    {
        // Try multiple locations
        $locations = [
            public_path($path),
            resource_path($path),
            Storage::path($path)
        ];
        
        foreach ($locations as $location) {
            if (file_exists($location)) {
                return $location;
            }
        }
        
        return null;
    }
}

class TicketTextRenderer
{
    private $image;
    private string $fontPath;
    private int $xCenter;

    public function __construct($image, string $fontPath, int $xCenter)
    {
        $this->image = $image;
        $this->fontPath = $fontPath;
        $this->xCenter = $xCenter;
    }

    public function addText(string $text, int &$y, int $size = 20, string $color = '#5A6B47', int $gap = 30): void
    {
        $this->image->text($text, $this->xCenter, $y, function ($font) use ($size, $color) {
            if ($this->fontPath) {
                $font->file($this->fontPath);
            }
            $font->size($size);
            $font->color($color);
            $font->align('center');
            $font->valign('top');
        });
        $y += $gap;
    }

    public function addWrappedText(string $text, int &$y, int $size = 20, string $color = '#5A6B47', int $gap = 30, int $maxWidth = 720): void
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';
        
        foreach ($words as $word) {
            $testLine = empty($currentLine) ? $word : $currentLine . ' ' . $word;
            
            // Simple approximation - in production, use imagettfbbox for accurate measurement
            if (strlen($testLine) * ($size * 0.6) > $maxWidth) {
                if (!empty($currentLine)) {
                    $lines[] = $currentLine;
                    $currentLine = $word;
                } else {
                    $lines[] = $word;
                }
            } else {
                $currentLine = $testLine;
            }
        }
        
        if (!empty($currentLine)) {
            $lines[] = $currentLine;
        }
        
        foreach ($lines as $line) {
            $this->addText($line, $y, $size, $color, $size + 10);
        }
        
        $y += $gap - (count($lines) * ($size + 10));
    }
}