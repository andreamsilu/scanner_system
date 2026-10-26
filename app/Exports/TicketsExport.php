<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle, WithProperties
{
    protected $tickets;
    protected $event;

    public function __construct($tickets, $event = null)
    {
        $this->tickets = $tickets;
        $this->event = $event;
    }

    public function collection()
    {
        return $this->tickets;
    }

    public function title(): string
    {
        return $this->event ? $this->event->name . ' - Tickets' : 'Tickets';
    }

    public function properties(): array
    {
        return [
            'title' => $this->event ? $this->event->name . ' - Tickets List' : 'Tickets List',
            'subject' => 'Event Tickets Export',
            'description' => 'Tickets list for ' . ($this->event ? $this->event->name : 'Event'),
            'creator' => 'Scannable Event System',
            'total_tickets' => count($this->tickets)
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Attendee Name',
            'Ticket Number',
            'Ticket Type'
        ];
    }

    public function map($ticket): array
    {
        static $index = 0;
        $index++;
        
        return [
            $index,
            $ticket->attendee_name ?? 'Unassigned',
            $ticket->ticket_number,
            ucfirst($ticket->type)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Header row
            'A1:D1' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA']
                ]
            ],
            'A:A' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                ],
                'font' => ['bold' => true]
            ],
            'B:D' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT
                ]
            ]
        ];
    }
}
