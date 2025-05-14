<?php

namespace App\Services;

use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class BulkUploadService
{
    protected $attendeeService;

    public function __construct(AttendeeService $attendeeService)
    {
        $this->attendeeService = $attendeeService;
    }

    /**
     * Process bulk attendee upload
     *
     * @param array $data
     * @param Event $event
     * @return array
     */
    public function processBulkUpload(array $data, Event $event): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        DB::beginTransaction();
        try {
            foreach ($data as $row) {
                try {
                    $this->validateAttendeeData($row);
                    
                    $attendee = $this->attendeeService->create([
                        'event_id' => $event->id,
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'phone' => $row['phone'] ?? null,
                        'ticket_type_id' => $row['ticket_type_id'],
                        'status' => 'registered'
                    ]);

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $row,
                        'error' => $e->getMessage()
                    ];
                    Log::error('Bulk upload error: ' . $e->getMessage(), [
                        'row' => $row,
                        'event_id' => $event->id
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk upload transaction failed: ' . $e->getMessage());
            throw $e;
        }

        return $results;
    }

    /**
     * Import attendees from Excel file
     *
     * @param string $filePath
     * @param Event $event
     * @return array
     */
    public function importFromExcel(string $filePath, Event $event): array
    {
        $data = Excel::toArray([], $filePath)[0];
        $headers = array_shift($data);
        
        $formattedData = collect($data)->map(function ($row) use ($headers) {
            return array_combine($headers, $row);
        })->toArray();

        return $this->processBulkUpload($formattedData, $event);
    }

    /**
     * Validate attendee data
     *
     * @param array $data
     * @throws \Exception
     */
    protected function validateAttendeeData(array $data): void
    {
        $required = ['name', 'email', 'ticket_type_id'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email format: {$data['email']}");
        }
    }
} 