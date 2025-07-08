<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EventCardController extends Controller
{
    /**
     * Display a listing of event cards.
     */
    public function index(): View
    {
        $attendees = Attendee::with(['event', 'ticket'])
            ->whereHas('event')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('event-cards.index', compact('attendees'));
    }

    /**
     * Display the specified event card.
     */
    public function show(Attendee $attendee): View
    {
        $attendee->load('event');
        return view('event-cards.show', compact('attendee'));
    }

    /**
     * Show the form for editing the specified event card.
     */
    public function edit(Attendee $attendee): View
    {
        $attendee->load('event');
        return view('event-cards.edit', compact('attendee'));
    }

    /**
     * Update the specified event card in storage.
     */
    public function update(Request $request, Attendee $attendee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $attendee->update($validated);

        return redirect()
            ->route('event-cards.show', $attendee)
            ->with('success', 'Event card updated successfully.');
    }

    /**
     * Remove the specified event card from storage.
     */
    public function destroy(Attendee $attendee)
    {
        $attendee->delete();

        return redirect()
            ->route('event-cards.index')
            ->with('success', 'Event card deleted successfully.');
    }

    /**
     * Download event card as JPG image.
     */
    public function download(Attendee $attendee): Response
    {
        $attendee->load('event');
        
        // Create image manager with GD driver
        $manager = new ImageManager(new Driver());
        
        // Create a new image canvas with Sage Green background
        $image = $manager->create(800, 1200, '#9CAF88'); // Sage Green background
        
        // Add text and styling to the image
        $this->addTextToImage($image, $attendee);
        
        // Generate QR code
        $qrCode = QrCode::format('png')
            ->size(200)
            ->generate(route('event-cards.verify', $attendee->qr_token));
        
        // Add QR code to image
        $qrImage = $manager->read($qrCode);
        $image->place($qrImage, 'bottom-right', 50, 50);
        
        // Convert to JPG and return
        $jpgData = $image->toJpeg(90);
        
        $filename = $attendee->name . '-' . $attendee->token . '.jpg';
        $filename = preg_replace('/[^a-zA-Z0-9-_.]/', '-', $filename);
        
        return response($jpgData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Verify event card by token.
     */
    public function verify(string $token): View
    {
        $attendee = Attendee::where('qr_token', $token)
            ->with('event')
            ->firstOrFail();

        return view('event-cards.verify', compact('attendee'));
    }

    /**
     * Add text content to the image.
     */
    private function addTextToImage($image, Attendee $attendee): void
    {
        $event = $attendee->event;
        
        // Header - Gold color
        $image->text('MWALIKO WA SENDOFF', 400, 80, [
            'color' => '#FFD700', // Gold
            'size' => 36,
            'align' => 'center'
        ]);
        
        // Invitation content - Dark Sage
        $y = 150;
        $image->text('Familia ya MZEE PAUL MSILU  wa Kibaha, Pwani,', 400, $y, [
            'color' => '#5A6B47', // Dark Sage
            'size' => 16,
            'align' => 'center'
        ]);
        
        $y += 30;
        $image->text('wanayo furaha kubwa kukualika / kuwaalika', 400, $y, [
            'color' => '#5A6B47', // Dark Sage
            'size' => 16,
            'align' => 'center'
        ]);
        
        $y += 40;
        $image->text('Ndg / Mhe / Dr / Prof / Mch / Bw / Bi / Bw & Bi', 400, $y, [
            'color' => '#5A6B47', // Dark Sage
            'size' => 16,
            'align' => 'center'
        ]);
        
        // Guest name label - Dark Sage
        $y += 60;
        $image->text('Jina la Mwalikwa:', 400, $y, [
            'color' => '#5A6B47', // Dark Sage
            'size' => 18,
            'align' => 'center'
        ]);
        
        // Guest name - Gold
        $y += 40;
        $image->text(strtoupper($attendee->name), 400, $y, [
            'color' => '#FFD700', // Gold
            'size' => 24,
            'align' => 'center'
        ]);
        
        // Event details - Dark Sage
        $y += 80;
        $image->text('Kwenye sherehe ya SENDOFF ya binti yao mpendwa', 400, $y, [
            'color' => '#5A6B47', // Dark Sage
            'size' => 16,
            'align' => 'center'
        ]);
        
        // Event name - Gold
        $y += 30;
        $image->text('ANITHA PAUL MSILU', 400, $y, [
            'color' => '#FFD700', // Gold
            'size' => 20,
            'align' => 'center'
        ]);
        
        // Date and time - Dark Sage
        $y += 40;
        $image->text('Itakayofanyika siku ya Alhamisi, tarehe 05-06-2025', 400, $y, [
            'color' => '#5A6B47', // Dark Sage
            'size' => 16,
            'align' => 'center'
        ]);
        
        $y += 30;
        $image->text('Kuanzia saa 12:00 jioni', 400, $y, [
            'color' => '#5A6B47', // Dark Sage
            'size' => 16,
            'align' => 'center'
        ]);
        
        // Venue - Dark Sage
        $y += 40;
        $image->text('Katika ukumbi wa GBF Garden Hall,', 400, $y, [
            'color' => '#5A6B47', // Dark Sage
            'size' => 16,
            'align' => 'center'
        ]);
        
        $y += 30;
        $image->text('uliopo Dar es Salaam – Tabata, Barakuda.', 400, $y, [
            'color' => '#5A6B47', // Dark Sage
            'size' => 16,
            'align' => 'center'
        ]);
        
        // Closing message - Dark Sage
        $y += 40;
        $image->text('Karibu tusherehekee kwa pamoja tukio hili maalum', 400, $y, [
            'color' => '#5A6B47', // Dark Sage
            'size' => 16,
            'align' => 'center'
        ]);
        
        $y += 30;
        $image->text('la furaha na kumbukumbu ya pekee.', 400, $y, [
            'color' => '#5A6B47', // Dark Sage
            'size' => 16,
            'align' => 'center'
        ]);
        
        // RSVP Section if enabled - Burnt Orange accents
        if ($event->rsvp_enabled && ($event->rsvp_option_1_phone || $event->rsvp_option_2_phone)) {
            $y += 60;
            $image->text('RSVP - Tafadhali Jibu', 400, $y, [
                'color' => '#D2691E', // Burnt Orange
                'size' => 20,
                'align' => 'center'
            ]);
            
            if ($event->rsvp_message) {
                $y += 30;
                $image->text($event->rsvp_message, 400, $y, [
                    'color' => '#5A6B47', // Dark Sage
                    'size' => 14,
                    'align' => 'center'
                ]);
            }
            
            if ($event->rsvp_option_1_label && $event->rsvp_option_1_phone) {
                $y += 30;
                $image->text($event->rsvp_option_1_label . ': ' . $event->rsvp_option_1_phone, 400, $y, [
                    'color' => '#D2691E', // Burnt Orange
                    'size' => 16,
                    'align' => 'center'
                ]);
            }
            
            if ($event->rsvp_option_2_label && $event->rsvp_option_2_phone) {
                $y += 30;
                $image->text($event->rsvp_option_2_label . ': ' . $event->rsvp_option_2_phone, 400, $y, [
                    'color' => '#D2691E', // Burnt Orange
                    'size' => 16,
                    'align' => 'center'
                ]);
            }
        }
        
        // Footer - Burnt Orange
        $image->text('Tafadhali wasilisha tiketi hii mlangoni', 400, 1100, [
            'color' => '#D2691E', // Burnt Orange
            'size' => 14,
            'align' => 'center'
        ]);
    }
} 