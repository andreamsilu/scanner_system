<?php

namespace App\Services;

use App\Models\EventCard;
use App\Models\ScanLog;
use Illuminate\Support\Facades\Log;

class ScannerService
{
    /**
     * Validate and process a QR code scan.
     */
    public function validateScan(string $token, string $scannedBy): array
    {
        try {
            $card = EventCard::where('token', $token)->first();

            if (!$card) {
                return [
                    'success' => false,
                    'message' => 'Invalid QR code. Card not found.'
                ];
            }

            if (!$card->is_active) {
                return [
                    'success' => false,
                    'message' => 'This card is no longer active.'
                ];
            }

            if ($card->is_used) {
                return [
                    'success' => false,
                    'message' => 'This card has already been used.'
                ];
            }

            // Create scan log
            ScanLog::create([
                'event_card_id' => $card->id,
                'event_id' => $card->event_id,
                'attendee_id' => $card->attendee_id,
                'scanned_by' => $scannedBy,
                'status' => 'success'
            ]);

            // Mark card as used
            $card->update(['is_used' => true]);

            return [
                'success' => true,
                'message' => 'Check-in successful! Welcome ' . $card->attendee->name
            ];

        } catch (\Exception $e) {
            Log::error('Scan validation error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error processing scan. Please try again.'
            ];
        }
    }

    /**
     * Get scan statistics.
     */
    public function getStatistics(): array
    {
        return [
            'today_scans' => ScanLog::whereDate('created_at', today())->count(),
            'total_scans' => ScanLog::count(),
            'active_events' => \App\Models\Event::where('status', 'active')->get(),
            'recent_scans' => ScanLog::with(['attendee', 'event'])
                ->latest()
                ->take(10)
                ->get()
        ];
    }
}
