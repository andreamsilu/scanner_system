<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Attendee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketExportService
{
    protected $attendeeService;

    public function __construct(AttendeeService $attendeeService)
    {
        $this->attendeeService = $attendeeService;
    }

    /**
     * Export tickets to Excel
     *
     * @param Event $event
     * @param array $filters
     * @return string
     */
    public function exportToExcel(Event $event, array $filters = []): string
    {
        $attendees = $this->getFilteredAttendees($event, $filters);
        
        $data = $attendees->map(function ($attendee) {
            return [
                'Name' => $attendee->name,
                'Email' => $attendee->email,
                'Phone' => $attendee->phone,
                'Ticket Type' => $attendee->ticketType->name,
                'Ticket Code' => $attendee->ticket_code,
                'Status' => $attendee->status,
                'Registration Date' => $attendee->created_at->format('Y-m-d H:i:s')
            ];
        });

        $fileName = "tickets_{$event->id}_" . now()->format('Y-m-d_His') . '.xlsx';
        
        Excel::store(
            new \Maatwebsite\Excel\Concerns\FromCollection($data),
            $fileName,
            'public'
        );

        return $fileName;
    }

    /**
     * Export tickets to PDF
     *
     * @param Event $event
     * @param array $filters
     * @return string
     */
    public function exportToPDF(Event $event, array $filters = []): string
    {
        $attendees = $this->getFilteredAttendees($event, $filters);
        
        $pdf = PDF::loadView('exports.tickets', [
            'attendees' => $attendees,
            'event' => $event
        ]);

        $fileName = "tickets_{$event->id}_" . now()->format('Y-m-d_His') . '.pdf';
        
        $pdf->save(storage_path('app/public/' . $fileName));

        return $fileName;
    }

    /**
     * Export individual ticket PDF
     *
     * @param Attendee $attendee
     * @return string
     */
    public function exportIndividualTicket(Attendee $attendee): string
    {
        $pdf = PDF::loadView('exports.individual-ticket', [
            'attendee' => $attendee,
            'event' => $attendee->event
        ]);

        $fileName = "ticket_{$attendee->id}_" . now()->format('Y-m-d_His') . '.pdf';
        
        $pdf->save(storage_path('app/public/' . $fileName));

        return $fileName;
    }

    /**
     * Get filtered attendees
     *
     * @param Event $event
     * @param array $filters
     * @return Collection
     */
    protected function getFilteredAttendees(Event $event, array $filters = []): Collection
    {
        $query = $event->attendees()->with('ticketType');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['ticket_type_id'])) {
            $query->where('ticket_type_id', $filters['ticket_type_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }
} 