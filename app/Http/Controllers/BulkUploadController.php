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
            $errors = [];
            $records = $this->getRecordsFromFile($path, pathinfo($path, PATHINFO_EXTENSION));

            foreach ($records as $index => $record) {
                try {
                    DB::beginTransaction();

                    // Create attendee
                    $attendee = Attendee::create([
                        'event_id' => $event->id,
                        'name' => $record['name'],
                        'email' => $record['email'] ?? null,
                        'phone' => $record['phone'],
                        'position' => $record['position'] ?? null,
                        'token' => Str::random(32),
                        'bulk_upload_id' => $bulkUpload->id,
                    ]);

                    // Generate QR code
                    $qrCode = $this->qrCodeService->generateForAttendee($attendee);

                    // Create ticket
                    Ticket::create([
                        'event_id' => $event->id,
                        'attendee_id' => $attendee->id,
                        'qr_code' => $qrCode,
                        'status' => 'active',
                    ]);

                    DB::commit();

                    Log::info('Record processed successfully', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'record_index' => $index + 1,
                        'attendee_id' => $attendee->id,
                        'timestamp' => now()->toDateTimeString()
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
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
            }

            // Update final status
            $bulkUpload->update([
                'status' => empty($errors) ? 'completed' : 'failed',
                'error_log' => json_encode($errors),
                'completed_at' => now()
            ]);

            // Clean up the uploaded file
            Storage::delete($path);

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
                'error_log' => json_encode([['error' => $e->getMessage()]]),
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
            'message' => $this->getStatusMessage($bulkUpload),
            'errors' => $bulkUpload->error_log ? json_decode($bulkUpload->error_log, true) : []
        ]);
    }

    /**
     * Get status message based on upload status
     */
    protected function getStatusMessage(BulkUpload $bulkUpload): string
    {
        if ($bulkUpload->status === 'completed') {
            return "Upload completed successfully.";
        }
        if ($bulkUpload->status === 'failed') {
            return "Upload failed. Please check the error log for details.";
        }
        return 'Processing upload...';
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