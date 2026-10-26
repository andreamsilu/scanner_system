<?php

namespace App\Http\Controllers;

use App\Models\BulkUpload;
use App\Models\Event;
use App\Models\Attendee;
use App\Models\Ticket;
use App\Services\QRCodeService;
use App\Services\SmsService;
use App\Jobs\SendSingleSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use League\Csv\Reader;
use Illuminate\Support\Facades\Log;
use Endroid\QrCode\QrCode;
use Maatwebsite\Excel\Excel;
use App\Exports\AttendeeTemplateExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class BulkUploadController extends Controller
{
    protected $qrCodeService;
    protected $smsService;

    public function __construct(QRCodeService $qrCodeService, SmsService $smsService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->smsService = $smsService;
    }

    /**
     * Show the bulk upload form
     */
    public function create(Event $event): View
    {
        return view('attendees.bulk-upload', compact('event'));
    }

    /**
     * Download the attendee template
     */
    public function downloadTemplate(Event $event = null)
    {
        $eventName = $event ? $event->name : 'Event';
        $safeEventName = preg_replace('/[^a-zA-Z0-9\s-]/', '', $eventName);
        $safeEventName = str_replace(' ', '_', $safeEventName);

        $headers = [
            'name (required)',
            'email (optional)',
            'phone (required)',
            'position (optional)',
            'ticket_type (required)'
        ];

        $data = [
            [
                'John Doe',
                'john@example.com',
                '1234567890',
                'Manager',
                'regular'
            ],
            [
                'Jane Smith',
                'jane@example.com',
                '0987654321',
                'Director',
                'vip'
            ],
            [
                'Bob Wilson',
                '', // Empty email to show it's optional
                '5551234567',
                '', // Empty position to show it's optional
                'single'
            ]
        ];

        return \Maatwebsite\Excel\Facades\Excel::download(
            new AttendeeTemplateExport($headers, $data, $eventName),
            'attendee_template_' . $safeEventName . '.xlsx'
        );
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

            \App\Jobs\ProcessBulkUpload::dispatch($bulkUpload->id, $path)->afterCommit();

            // Return success response
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded successfully. Processing will continue in the background.',
                    'redirect' => route('events.attendees.bulk-upload.status', ['event' => $event->id, 'bulkUpload' => $bulkUpload->id])
                ]);
            }

            return redirect()
                ->route('events.attendees.bulk-upload.status', ['event' => $event->id, 'bulkUpload' => $bulkUpload->id])
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
     * Show upload status
     */
    public function status(Event $event, BulkUpload $bulkUpload): View
    {
        // Ensure the bulk upload belongs to the event
        if ($bulkUpload->event_id !== $event->id) {
            abort(404);
        }

        $bulkUpload->load(['event', 'user', 'attendees']);
        return view('attendees.bulk-upload-status', compact('bulkUpload', 'event'));
    }

    /**
     * Get current upload status
     */
    public function getStatus(Event $event, BulkUpload $bulkUpload)
    {
        // Ensure the bulk upload belongs to the event
        if ($bulkUpload->event_id !== $event->id) {
            abort(404);
        }

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




}
