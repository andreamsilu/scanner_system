<?php

namespace App\Services;

use App\Models\Event;
use App\Models\ScanLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class RealTimeStatsService
{
    protected $cacheTime = 60; // 1 minute cache

    /**
     * Get real-time event statistics
     *
     * @param Event $event
     * @return array
     */
    public function getEventStats(Event $event): array
    {
        $cacheKey = "event_stats_{$event->id}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($event) {
            return [
                'total_attendees' => $event->attendees()->count(),
                'checked_in' => $event->scanLogs()
                    ->where('status', 'success')
                    ->distinct('attendee_id')
                    ->count(),
                'pending' => $event->attendees()
                    ->whereNotIn('id', function ($query) use ($event) {
                        $query->select('attendee_id')
                            ->from('scan_logs')
                            ->where('event_id', $event->id)
                            ->where('status', 'success');
                    })
                    ->count(),
                'recent_scans' => $this->getRecentScans($event),
                'ticket_type_breakdown' => $this->getTicketTypeBreakdown($event)
            ];
        });
    }

    /**
     * Update real-time statistics
     *
     * @param Event $event
     * @param ScanLog $scanLog
     * @return void
     */
    public function updateStats(Event $event, ScanLog $scanLog): void
    {
        // Clear cache
        Cache::forget("event_stats_{$event->id}");
        
        // Broadcast update
        $stats = $this->getEventStats($event);
        Redis::publish('event.' . $event->id, json_encode([
            'type' => 'stats_update',
            'data' => $stats
        ]));
    }

    /**
     * Get recent scans
     *
     * @param Event $event
     * @return array
     */
    protected function getRecentScans(Event $event): array
    {
        return $event->scanLogs()
            ->with(['attendee', 'scanner'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($scan) {
                return [
                    'attendee_name' => $scan->attendee->name,
                    'scanner_name' => $scan->scanner->name,
                    'status' => $scan->status,
                    'timestamp' => $scan->created_at->format('Y-m-d H:i:s')
                ];
            })
            ->toArray();
    }

    /**
     * Get ticket type breakdown
     *
     * @param Event $event
     * @return array
     */
    protected function getTicketTypeBreakdown(Event $event): array
    {
        return $event->attendees()
            ->selectRaw('ticket_type_id, count(*) as count')
            ->groupBy('ticket_type_id')
            ->with('ticketType:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->ticketType->name,
                    'count' => $item->count
                ];
            })
            ->toArray();
    }

    /**
     * Get check-in rate over time
     *
     * @param Event $event
     * @return array
     */
    public function getCheckInRate(Event $event): array
    {
        $cacheKey = "check_in_rate_{$event->id}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($event) {
            return $event->scanLogs()
                ->where('status', 'success')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'count' => $item->count
                    ];
                })
                ->toArray();
        });
    }
} 