<?php

namespace App\Http\Controllers;

use App\Models\BulkUpload;
use App\Models\Event;
use App\Models\Attendee;
use App\Models\Ticket;
use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use League\Csv\Reader;
use Illuminate\Support\Facades\Log;
use Endroid\QrCode\QrCode;

class BulkUploadController extends Controller
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Show the bulk upload form
     */
    public function create(Event $event): View
    {
        return view('attendees.bulk-upload', compact('event'));
    }

    /**
     * Process the bulk upload
     */
    public function store(Request $request, Event $event)
    {
        Log::info('Starting bulk upload process', [
            'event_id' => $event->id,
            'event_name' => $event->name,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'file_name' => $request->file('csv_file')->getClientOriginalName(),
            'file_size' => $request->file('csv_file')->getSize(),
            'file_type' => $request->file('csv_file')->getMimeType(),
            'timestamp' => now()->toDateTimeString()
        ]);

        $request->validate([
            'csv_file' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ], [
            'csv_file.mimes' => 'Only Excel (.xlsx, .xls) and CSV (.csv) files are allowed.',
            'csv_file.max' => 'The file size must not exceed 10MB.',
        ]);

        try {
            // Create bulk upload record
            $bulkUpload = BulkUpload::create([
                'event_id' => $event->id,
                'user_id' => auth()->id(),
                'filename' => $request->file('csv_file')->getClientOriginalName(),
                'status' => 'processing',
            ]);

            Log::info('Bulk upload record created', [
                'bulk_upload_id' => $bulkUpload->id,
                'event_id' => $event->id,
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString()
            ]);

            // Store the file
            $path = $request->file('csv_file')->store('bulk-uploads');
            
            Log::info('File stored successfully', [
                'bulk_upload_id' => $bulkUpload->id,
                'file_path' => $path,
                'timestamp' => now()->toDateTimeString()
            ]);

            // Process the upload in the background
            dispatch(function() use ($path, $bulkUpload, $event) {
                $this->processUpload($path, $bulkUpload, $event);
            })->afterResponse();

            Log::info('Bulk upload job dispatched', [
                'bulk_upload_id' => $bulkUpload->id,
                'event_id' => $event->id,
                'timestamp' => now()->toDateTimeString()
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bulk upload started successfully.',
                    'bulk_upload_id' => $bulkUpload->id,
                    'redirect' => route('events.attendees.bulk-upload.status', ['event' => $event, 'bulkUpload' => $bulkUpload])
                ]);
            }

            return redirect()->route('events.attendees.bulk-upload.status', ['event' => $event, 'bulkUpload' => $bulkUpload])
                ->with('success', 'Bulk upload started. You will be notified when it\'s complete.');

        } catch (\Exception $e) {
            Log::error('Bulk upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_id' => $event->id,
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process upload: ' . $e->getMessage()
                ], 422);
            }

            return back()->with('error', 'Failed to process upload: ' . $e->getMessage());
        }
    }

    /**
     * Process the uploaded file
     */
    protected function processUpload(string $path, BulkUpload $bulkUpload, Event $event)
    {
        try {
            Log::info('Starting file processing', [
                'bulk_upload_id' => $bulkUpload->id,
                'file_path' => $path,
                'event_id' => $event->id,
                'timestamp' => now()->toDateTimeString()
            ]);

            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $records = $this->getRecordsFromFile($path, $extension);
            $totalRecords = count($records);
            
            Log::info('File records loaded', [
                'bulk_upload_id' => $bulkUpload->id,
                'total_records' => $totalRecords,
                'file_type' => $extension,
                'timestamp' => now()->toDateTimeString()
            ]);

            $processed = 0;
            $failed = 0;
            $errors = [];

            foreach ($records as $index => $record) {
                try {
                    Log::info('Processing record', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'record_index' => $index + 1,
                        'total_records' => $totalRecords,
                        'record_data' => $record,
                        'timestamp' => now()->toDateTimeString()
                    ]);

                    // Validate required fields
                    if (empty($record['name']) || empty($record['phone'])) {
                        throw new \Exception('Name and phone are required fields');
                    }

                    // Set default email if none provided
                    if (empty($record['email'])) {
                        $record['email'] = 'admin@gmail.com';
                        Log::info('Default email set for record', [
                            'bulk_upload_id' => $bulkUpload->id,
                            'record_index' => $index + 1,
                            'email' => $record['email']
                        ]);
                    }

                    // Validate email format
                    if (!filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
                        throw new \Exception('Invalid email format: ' . $record['email']);
                    }

                    // Check for existing attendee
                    $existingAttendee = Attendee::where('event_id', $event->id)
                        ->where('email', $record['email'])
                        ->first();

                    if ($existingAttendee) {
                        throw new \Exception("Duplicate email '{$record['email']}' for this event. Attendee already exists with name: {$existingAttendee->name}");
                    }

                    DB::beginTransaction();

                    // Generate unique token
                    do {
                        $token = str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
                    } while (Attendee::where('token', $token)->exists());

                    Log::info('Token generated for record', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'record_index' => $index + 1,
                        'token' => $token
                    ]);

                    // Generate QR code
                    $checkInUrl = route('scanner.check-in', ['token' => $token], true);
                    $qrCode = QrCode::format('png')
                        ->size(300)
                        ->errorCorrection('H')
                        ->generate($checkInUrl);

                    // Store QR code
                    $sanitizedName = preg_replace('/[^a-zA-Z0-9-]/', '-', strtolower($record['name']));
                    $sanitizedName = preg_replace('/-+/', '-', $sanitizedName);
                    $sanitizedName = trim($sanitizedName, '-');
                    $qrPath = 'qr-codes/' . $sanitizedName . '-' . $token . '.png';
                    Storage::disk('public')->put($qrPath, $qrCode);

                    Log::info('QR code generated and stored', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'record_index' => $index + 1,
                        'qr_path' => $qrPath
                    ]);

                    // Create attendee
                    $attendee = Attendee::create([
                        'event_id' => $event->id,
                        'name' => $record['name'],
                        'phone' => $record['phone'],
                        'email' => $record['email'] ?? null,
                        'position' => $record['position'] ?? null,
                        'token' => $token,
                        'qr_code' => $qrPath,
                        'registration_status' => 'unchecked_in',
                        'registered_at' => now()
                    ]);

                    Log::info('Attendee created', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'record_index' => $index + 1,
                        'attendee_id' => $attendee->id,
                        'name' => $attendee->name
                    ]);

                    // Create ticket
                    $ticketNumber = 'TICKET-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                    $ticket = Ticket::create([
                        'event_id' => $event->id,
                        'attendee_id' => $attendee->id,
                        'type' => $record['ticket_type'] ?? 'regular',
                        'status' => 'active',
                        'ticket_number' => $ticketNumber,
                        'token' => $token
                    ]);

                    Log::info('Ticket created', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'record_index' => $index + 1,
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticketNumber
                    ]);

                    DB::commit();
                    $processed++;

                    Log::info('Record processed successfully', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'record_index' => $index + 1,
                        'attendee_id' => $attendee->id,
                        'ticket_id' => $ticket->id
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    $errors[] = [
                        'row' => $index + 2,
                        'error' => $e->getMessage(),
                        'data' => $record
                    ];

                    Log::error('Record processing failed', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'record_index' => $index + 1,
                        'error' => $e->getMessage(),
                        'record_data' => $record,
                        'timestamp' => now()->toDateTimeString()
                    ]);
                }

                // Update progress
                $progress = ($processed + $failed) / $totalRecords * 100;
                $bulkUpload->update([
                    'progress' => $progress,
                    'processed_records' => $processed,
                    'failed_records' => $failed,
                    'error_log' => json_encode($errors),
                    'status' => ($processed + $failed) === $totalRecords ? 'completed' : 'processing'
                ]);

                Log::info('Progress updated', [
                    'bulk_upload_id' => $bulkUpload->id,
                    'progress' => $progress,
                    'processed' => $processed,
                    'failed' => $failed,
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            // Final status update
            $bulkUpload->update([
                'status' => $failed === $totalRecords ? 'failed' : 'completed',
                'completed_at' => now()
            ]);

            Log::info('Bulk upload completed', [
                'bulk_upload_id' => $bulkUpload->id,
                'total_records' => $totalRecords,
                'processed' => $processed,
                'failed' => $failed,
                'final_status' => $bulkUpload->status,
                'timestamp' => now()->toDateTimeString()
            ]);

            // Clean up the uploaded file
            Storage::delete($path);
            
            Log::info('Temporary file cleaned up', [
                'bulk_upload_id' => $bulkUpload->id,
                'file_path' => $path,
                'timestamp' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk upload processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'bulk_upload_id' => $bulkUpload->id,
                'event_id' => $event->id,
                'timestamp' => now()->toDateTimeString()
            ]);
            
            $bulkUpload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show upload status
     */
    public function status(BulkUpload $bulkUpload): View
    {
        $bulkUpload->load(['event', 'user', 'attendees']);
        return view('attendees.bulk-upload-status', compact('bulkUpload'));
    }

    /**
     * Get current upload status
     */
    public function getStatus(BulkUpload $bulkUpload)
    {
        return response()->json([
            'status' => $bulkUpload->status,
            'progress' => [
                'percent' => $bulkUpload->total_records > 0 
                    ? round(($bulkUpload->processed_records + $bulkUpload->failed_records) / $bulkUpload->total_records * 100)
                    : 0,
                'processed' => $bulkUpload->processed_records,
                'failed' => $bulkUpload->failed_records,
                'total' => $bulkUpload->total_records
            ],
            'message' => $this->getStatusMessage($bulkUpload),
            'errors' => $bulkUpload->error_message ? explode("\n", $bulkUpload->error_message) : []
        ]);
    }

    /**
     * Get status message based on upload status
     */
    protected function getStatusMessage(BulkUpload $bulkUpload): string
    {
        if ($bulkUpload->status === 'completed') {
            return "Upload completed successfully. Processed {$bulkUpload->processed_records} records.";
        }
        if ($bulkUpload->status === 'failed') {
            return "Upload failed: {$bulkUpload->error_message}";
        }
        if ($bulkUpload->status === 'processing') {
            return "Processing records... ({$bulkUpload->processed_records} processed, {$bulkUpload->failed_records} failed)";
        }
        return 'Starting upload process...';
    }

    private function getRecordsFromFile($path, $extension)
    {
        if (in_array($extension, ['xlsx', 'xls'])) {
            // Process Excel file
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(Storage::path($path));
            $worksheet = $spreadsheet->getActiveSheet();
            $headers = [];
            $firstRow = true;
            $records = [];

            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];

                foreach ($cellIterator as $cell) {
                    $value = $cell->getValue();
                    if ($firstRow) {
                        $headers[] = strtolower(trim($value));
                    } else {
                        $rowData[] = $value;
                    }
                }

                if ($firstRow) {
                    $firstRow = false;
                    continue;
                }

                if (!empty(array_filter($rowData))) {
                    $records[] = array_combine($headers, $rowData);
                }
            }
        } else {
            // Process CSV file
            $csv = Reader::createFromPath(Storage::path($path), 'r');
            $csv->setHeaderOffset(0);
            $records = iterator_to_array($csv->getRecords());
        }

        return $records;
    }
} 