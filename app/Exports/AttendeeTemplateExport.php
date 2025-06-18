<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithStartCell;
use Maatwebsite\Excel\Concerns\WithTitle;

class AttendeeTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting, WithStartCell, WithTitle
{
    protected $headers;
    protected $data;

    public function __construct(array $headers, array $data)
    {
        $this->headers = $headers;
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function startCell(): string
    {
        return 'A3'; // Start after instructions
    }

    public function title(): string
    {
        return 'Attendee Template';
    }

    public function styles(Worksheet $sheet)
    {
        // Add instructions at the top
        $sheet->setCellValue('A1', 'ATTENDEE UPLOAD TEMPLATE');
        $sheet->setCellValue('A2', 'Instructions: Required fields must be filled. Optional fields can be left empty.');
        
        // Style the instructions
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A1:A2')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));

        return [
            3 => ['font' => ['bold' => true]], // Header row (row 3 after instructions)
            'A3:E3' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA']
                ]
            ],
            'A4:E6' => [
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