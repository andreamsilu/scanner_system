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
            'file_name' => $request->file('csv_file')->getClientOriginalName(),
            'file_size' => $request->file('csv_file')->getSize(),
            'file_type' => $request->file('csv_file')->getMimeType()
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

            // Store the file
            $path = $request->file('csv_file')->store('bulk-uploads');

            // Process the upload in the background
            dispatch(function() use ($path, $bulkUpload, $event) {
                $this->processUpload($path, $bulkUpload, $event);
            })->afterResponse();

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
                'user_id' => auth()->id()
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
                'event_id' => $event->id
            ]);

            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $records = [];

            if (in_array($extension, ['xlsx', 'xls'])) {
                // Process Excel file
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(Storage::path($path));
                $worksheet = $spreadsheet->getActiveSheet();
                $headers = [];
                $firstRow = true;

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

            $totalRecords = count($records);
            $bulkUpload->update(['total_records' => $totalRecords]);

            Log::info('Total records found', [
                'bulk_upload_id' => $bulkUpload->id,
                'total_records' => $totalRecords
            ]);

            $processed = 0;
            $failed = 0;
            $errors = [];

            foreach ($records as $index => $record) {
                try {
                    DB::beginTransaction();

                    // Validate required fields
                    if (empty($record['name']) || empty($record['phone'])) {
                        throw new \Exception('Name and phone are required fields');
                    }

                    // Generate unique token
                    $token = Str::random(7);
                    while (Attendee::where('token', $token)->exists()) {
                        $token = Str::random(7);
                    }

                    // Create attendee
                    $attendee = Attendee::create([
                        'event_id' => $event->id,
                        'name' => $record['name'] ?? '',
                        'phone' => $record['phone'] ?? '',
                        'email' => $record['email'] ?? null,
                        'position' => $record['position'] ?? null,
                        'token' => $token,
                        'bulk_upload_id' => $bulkUpload->id,
                        'registration_status' => 'registered',
                    ]);

                    // Create ticket
                    $ticket = Ticket::create([
                        'event_id' => $event->id,
                        'attendee_id' => $attendee->id,
                        'status' => 'active',
                        'ticket_number' => Ticket::generateTicketNumber(),
                    ]);

                    DB::commit();
                    $processed++;

                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    $errorMessage = "Row " . ($index + 2) . ": " . $e->getMessage();
                    $errors[] = $errorMessage;
                    
                    Log::error('Record processing failed', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'record_index' => $index + 2,
                        'error' => $e->getMessage(),
                        'record_data' => $record
                    ]);
                }

                // Update progress in database
                $bulkUpload->update([
                    'processed_records' => $processed,
                    'failed_records' => $failed,
                    'error_message' => !empty($errors) ? implode("\n", $errors) : null,
                ]);

                // Log progress every 10 records
                if ($processed % 10 === 0 || $failed % 10 === 0) {
                    Log::info('Upload progress update', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'processed' => $processed,
                        'failed' => $failed,
                        'total' => $totalRecords
                    ]);
                }
            }

            // Update final status
            $finalStatus = $failed === 0 ? 'completed' : 'failed';
            $bulkUpload->update(['status' => $finalStatus]);

            Log::info('Bulk upload processing completed', [
                'bulk_upload_id' => $bulkUpload->id,
                'status' => $finalStatus,
                'total_processed' => $processed,
                'total_failed' => $failed,
                'total_records' => $totalRecords
            ]);

            // Clean up the uploaded file
            Storage::delete($path);

        } catch (\Exception $e) {
            Log::error('Bulk upload processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'bulk_upload_id' => $bulkUpload->id,
                'event_id' => $event->id
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
} 