<?php

namespace App\Services;

use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AttendeeService
{
    /**
     * Process bulk upload of attendees
     */
    public function processBulkUpload(UploadedFile $file, int $eventId): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        try {
            $event = Event::findOrFail($eventId);
            $data = $this->readFile($file);
            
            DB::beginTransaction();
            
            foreach ($data as $index => $row) {
                try {
                    $this->validateRow($row);
                    
                    Attendee::create([
                        'event_id' => $eventId,
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'phone' => $row['phone'],
                        'ticket_type' => $row['ticket_type'] ?? 'regular',
                        'status' => $row['status'] ?? 'pending',
                        'registration_date' => now(),
                    ]);
                    
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk upload failed: ' . $e->getMessage());
            throw $e;
        }

        return $results;
    }

    /**
     * Read and parse the uploaded file
     */
    private function readFile(UploadedFile $file): Collection
    {
        $extension = $file->getClientOriginalExtension();
        $path = $file->getRealPath();

        if ($extension === 'csv') {
            return $this->readCsv($path);
        }

        return $this->readExcel($path);
    }

    /**
     * Read CSV file
     */
    private function readCsv(string $path): Collection
    {
        $data = collect();
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data->push(array_combine($headers, $row));
        }

        fclose($handle);
        return $data;
    }

    /**
     * Read Excel file
     */
    private function readExcel(string $path): Collection
    {
        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = collect();
        $headers = [];

        foreach ($worksheet->getRowIterator() as $index => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            $rowData = [];

            foreach ($cellIterator as $cell) {
                if ($index === 1) {
                    $headers[] = $cell->getValue();
                } else {
                    $rowData[] = $cell->getValue();
                }
            }

            if ($index > 1) {
                $data->push(array_combine($headers, $rowData));
            }
        }

        return $data;
    }

    /**
     * Validate a row of data
     */
    private function validateRow(array $row): void
    {
        if (empty($row['name'])) {
            throw new \Exception('Name is required');
        }

        if (empty($row['email'])) {
            throw new \Exception('Email is required');
        }

        if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format');
        }

        if (empty($row['phone'])) {
            throw new \Exception('Phone is required');
        }

        if (isset($row['ticket_type']) && !in_array($row['ticket_type'], ['regular', 'vip', 'early_bird'])) {
            throw new \Exception('Invalid ticket type');
        }

        if (isset($row['status']) && !in_array($row['status'], ['pending', 'confirmed', 'cancelled'])) {
            throw new \Exception('Invalid status');
        }
    }
} 