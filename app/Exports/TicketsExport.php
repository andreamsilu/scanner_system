<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $tickets;

    public function __construct($tickets)
    {
        $this->tickets = $tickets;
    }

    public function collection()
    {
        return $this->tickets;
    }

    public function headings(): array
    {
        return [
            'Ticket ID',
            'Token',
            'Ticket Number',
            'Attendee Name',
            'Phone',
            'Email',
            'Event Name',
            'Event Date',
            'Location',
            'Ticket Type',
            'Status',
            'Last Used',
            'Created At'
        ];
    }

    public function map($ticket): array
    {
        return [
            $ticket->id,
            $ticket->token,
            $ticket->ticket_number,
            $ticket->attendee_name ?? 'Unassigned',
            $ticket->attendee_phone ?? 'N/A',
            $ticket->attendee_email ?? 'N/A',
            $ticket->event_name,
            $ticket->event_date ? date('d/m/Y', strtotime($ticket->event_date)) : 'N/A',
            $ticket->event_location ?? 'N/A',
            ucfirst($ticket->type),
            ucfirst($ticket->status),
            $ticket->used_at ? date('d/m/Y H:i', strtotime($ticket->used_at)) : 'Never',
            date('d/m/Y H:i', strtotime($ticket->created_at))
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]], // Header row
            'A1:M1' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA']
                ]
            ],
            'A:M' => [
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT
                ]
            ]
        ];
    }
} 