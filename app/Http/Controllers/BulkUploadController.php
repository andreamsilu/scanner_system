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
        try {
            // Validate file
            $request->validate([
                'csv_file' => [
                    'required',
                    'file',
                    'mimes:xlsx,xls,csv',
                    'max:10240',
                ],
            ], [
                'csv_file.required' => 'Please select a file to upload.',
                'csv_file.file' => 'The uploaded file is invalid.',
                'csv_file.mimes' => 'Only Excel (.xlsx, .xls) and CSV (.csv) files are allowed.',
                'csv_file.max' => 'The file size must not exceed 10MB.',
            ]);

            // Get file details
            $file = $request->file('csv_file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $mimeType = $file->getMimeType();

            // Create bulk upload record
            $bulkUpload = BulkUpload::create([
                'event_id' => $event->id,
                'user_id' => auth()->id(),
                'filename' => $originalName,
                'status' => 'processing',
            ]);

            // Store the file in the bulk-uploads directory
            $path = $file->storeAs(
                'bulk-uploads',
                $bulkUpload->id . '_' . time() . '.' . $extension
            );
            
            if (!$path) {
                throw new \Exception('Failed to store the uploaded file.');
            }

            // Process the upload in the background
            dispatch(function() use ($path, $bulkUpload, $event, $originalName, $mimeType) {
                Log::info('Starting bulk upload process', [
                    'event_id' => $event->id,
                    'event_name' => $event->name,
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                    'file_name' => $originalName,
                    'file_size' => Storage::size($path),
                    'file_type' => $mimeType,
                    'timestamp' => now()->toDateTimeString()
                ]);

                Log::info('Bulk upload record created', [
                    'bulk_upload_id' => $bulkUpload->id,
                    'event_id' => $event->id,
                    'user_id' => auth()->id(),
                    'timestamp' => now()->toDateTimeString()
                ]);

                Log::info('File stored successfully', [
                    'bulk_upload_id' => $bulkUpload->id,
                    'file_path' => $path,
                    'storage_path' => Storage::path($path),
                    'timestamp' => now()->toDateTimeString()
                ]);

                $this->processUpload($path, $bulkUpload, $event);
            })->afterResponse();

            // Return success response
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded successfully. Processing will continue in the background.',
                    'redirect' => route('attendees.bulk-upload.status', ['event' => $event->id, 'bulkUpload' => $bulkUpload->id])
                ]);
            }

            return redirect()
                ->route('attendees.bulk-upload.status', ['event' => $event->id, 'bulkUpload' => $bulkUpload->id])
                ->with('success', 'File uploaded successfully. Processing will continue in the background.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'errors' => $e->errors(),
                'event_id' => $event->id,
                'user_id' => auth()->id(),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . collect($e->errors())->first()[0]
                ], 422);
            }

            return back()->withErrors($e->errors());
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
                    'message' => 'Failed to upload file: ' . $e->getMessage()
                ], 422);
            }

            return back()->with('error', 'Failed to upload file: ' . $e->getMessage());
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
                        'token' => str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT),
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