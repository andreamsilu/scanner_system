<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AttendeeTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    protected $headers;
    protected $data;
    protected $eventName;

    public function __construct(array $headers, array $data, string $eventName = 'Event')
    {
        $this->headers = $headers;
        $this->data = $data;
        $this->eventName = $eventName;
    }

    public function array(): array
    {
        // Add instructions as the first two rows
        $instructions = [
            ['ATTENDEE UPLOAD TEMPLATE - ' . $this->eventName, '', '', '', ''],
            ['Instructions: Required fields must be filled. Optional fields can be left empty.', '', '', '', ''],
            ['', '', '', '', ''], // Empty row for spacing
        ];
        
        return array_merge($instructions, $this->data);
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'font' => ['color' => ['rgb' => 'FF0000']]], // Title
            2 => ['font' => ['italic' => true, 'size' => 10], 'font' => ['color' => ['rgb' => 'FF0000']]], // Instructions
            4 => ['font' => ['bold' => true]], // Header row (row 4 after instructions)
            'A4:E4' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA']
                ]
            ],
            'A5:E7' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2']
                ]
            ]
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT, // Phone number column
        ];
    }
} 