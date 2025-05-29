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
use App\Events\BulkUploadStatus;

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

            // Broadcast initial status
            broadcast(new BulkUploadStatus(
                $bulkUpload,
                'processing',
                ['percent' => 0],
                'Starting upload process...'
            ))->toOthers();

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

            return redirect()->route('events.attendees.index', $event)
                ->with('success', 'Bulk upload started. You will be notified when it\'s complete.');

        } catch (\Exception $e) {
            Log::error('Bulk upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_id' => $event->id,
                'user_id' => auth()->id()
            ]);

            if (isset($bulkUpload)) {
                broadcast(new BulkUploadStatus(
                    $bulkUpload,
                    'failed',
                    ['percent' => 0],
                    'Upload failed: ' . $e->getMessage(),
                    [$e->getMessage()]
                ))->toOthers();
            }
            
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
     * Process the uploaded CSV file
     */
    protected function processUpload(string $path, BulkUpload $bulkUpload, Event $event)
    {
        try {
            broadcast(new BulkUploadStatus(
                $bulkUpload,
                'processing',
                ['percent' => 5],
                'Reading file...'
            ))->toOthers();

            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $records = [];

            if (in_array($extension, ['xlsx', 'xls'])) {
                Log::info('Processing Excel file', [
                    'bulk_upload_id' => $bulkUpload->id,
                    'extension' => $extension
                ]);
                
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
                        Log::info('Excel headers found', [
                            'bulk_upload_id' => $bulkUpload->id,
                            'headers' => $headers
                        ]);
                        continue;
                    }

                    if (!empty(array_filter($rowData))) {
                        $records[] = array_combine($headers, $rowData);
                    }
                }
            } else {
                Log::info('Processing CSV file', [
                    'bulk_upload_id' => $bulkUpload->id,
                    'extension' => $extension
                ]);
                
                // Process CSV file
                $csv = Reader::createFromPath(Storage::path($path), 'r');
                $csv->setHeaderOffset(0);
                $records = $csv->getRecords();
            }

            $totalRecords = count($records);
            $bulkUpload->update(['total_records' => $totalRecords]);

            broadcast(new BulkUploadStatus(
                $bulkUpload,
                'processing',
                ['percent' => 10],
                "Found {$totalRecords} records to process"
            ))->toOthers();

            $processed = 0;
            $failed = 0;
            $errors = [];

            foreach ($records as $index => $record) {
                try {
                    DB::beginTransaction();

                    Log::info('Processing record', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'record_index' => $index + 2,
                        'record_data' => $record
                    ]);

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
                        'registration_status' => 'unchecked_in',
                    ]);

                    Log::info('Attendee created', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'attendee_id' => $attendee->id,
                        'name' => $attendee->name,
                        'token' => $token
                    ]);

                    // Create ticket
                    $ticket = Ticket::create([
                        'event_id' => $event->id,
                        'attendee_id' => $attendee->id,
                        'status' => 'active',
                    ]);

                    Log::info('Ticket created', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'ticket_id' => $ticket->id,
                        'attendee_id' => $attendee->id
                    ]);

                    // Generate QR code
                    $qrCodePath = $this->qrCodeService->generateQRCode(
                        $token,
                        $attendee->name,
                        $event->name
                    );

                    $attendee->update(['qr_code_path' => $qrCodePath]);

                    Log::info('QR code generated', [
                        'bulk_upload_id' => $bulkUpload->id,
                        'attendee_id' => $attendee->id,
                        'qr_code_path' => $qrCodePath
                    ]);

                    DB::commit();
                    $processed++;

                    // Calculate progress percentage
                    $percent = min(90, 10 + (($processed + $failed) / $totalRecords * 80));
                    
                    // Broadcast progress every 5 records or on errors
                    if ($processed % 5 === 0 || $failed > 0) {
                        broadcast(new BulkUploadStatus(
                            $bulkUpload,
                            'processing',
                            [
                                'percent' => $percent,
                                'processed' => $processed,
                                'failed' => $failed,
                                'total' => $totalRecords
                            ],
                            "Processing records... ({$processed} processed, {$failed} failed)"
                        ))->toOthers();
                    }

                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    $errorMessage = "Row " . ($index + 2) . ": " . $e->getMessage();
                    $errors[] = $errorMessage;
                    
                    // Broadcast error immediately
                    broadcast(new BulkUploadStatus(
                        $bulkUpload,
                        'processing',
                        [
                            'percent' => min(90, 10 + (($processed + $failed) / $totalRecords * 80)),
                            'processed' => $processed,
                            'failed' => $failed,
                            'total' => $totalRecords
                        ],
                        "Error processing record " . ($index + 2),
                        [$errorMessage]
                    ))->toOthers();

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
            }

            // Update final status
            $finalStatus = $failed === 0 ? 'completed' : 'failed';
            $bulkUpload->update(['status' => $finalStatus]);

            // Broadcast final status
            broadcast(new BulkUploadStatus(
                $bulkUpload,
                $finalStatus,
                ['percent' => 100],
                $failed === 0 
                    ? "Upload completed successfully. Processed {$processed} records."
                    : "Upload completed with errors. Processed {$processed} records, {$failed} failed.",
                $errors
            ))->toOthers();

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

            // Broadcast failure
            broadcast(new BulkUploadStatus(
                $bulkUpload,
                'failed',
                ['percent' => 0],
                'Upload failed: ' . $e->getMessage(),
                [$e->getMessage()]
            ))->toOthers();
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
} 