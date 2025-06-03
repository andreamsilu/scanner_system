<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use Illuminate\Support\Facades\Validator;
use App\Models\ScanLog;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Http\JsonResponse;

class AttendeeController extends Controller
{
    /**
     * Display a listing of the attendees.
     */
    public function index(Event $event, Request $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = $event->attendees()->with(['event', 'ticket']);

            // Apply search
            if ($request->search) {
                $searchValue = $request->search;
                $query->where(function($q) use ($searchValue) {
                    $q->where('name', 'like', '%' . $searchValue . '%')
                      ->orWhere('email', 'like', '%' . $searchValue . '%')
                      ->orWhere('phone', 'like', '%' . $searchValue . '%')
                      ->orWhere('token', 'like', '%' . $searchValue . '%');
                });
            }

            // Apply filters
            if ($request->event_filter) {
                $query->where('event_id', $request->event_filter);
            }

            if ($request->status) {
                $query->where('registration_status', $request->status);
            }

            // Get total count before pagination
            $totalRecords = $query->count();

            // Apply ordering
            if ($request->order) {
                $orderColumn = $request->order['column'] ?? 6; // Default to registration date
                $orderDir = $request->order['dir'] ?? 'desc';
                $columns = ['name', 'phone', 'token', 'registration_status', 'ticket_status', 'ticket_type', 'created_at'];
                
                if (isset($columns[$orderColumn])) {
                    $query->orderBy($columns[$orderColumn], $orderDir);
                }
            } else {
                $query->latest();
            }

            // Apply pagination
            $attendees = $query->paginate(15);

            // Transform data for DataTables
            $data = $attendees->map(function ($attendee) {
                return [
                    'name' => $attendee->name,
                    'phone' => $attendee->phone,
                    'token' => $attendee->token,
                    'registration_status' => [
                        'status' => $attendee->registration_status,
                        'badge' => $attendee->registration_status === 'checked_in' ? 'success' : 
                                 ($attendee->registration_status === 'cancelled' ? 'danger' : 'info')
                    ],
                    'ticket_status' => $attendee->ticket ? [
                        'status' => $attendee->ticket->status,
                        'badge' => $attendee->ticket->status === 'valid' ? 'success' : 'danger'
                    ] : ['status' => 'No Ticket', 'badge' => 'secondary'],
                    'ticket_type' => $attendee->ticket ? [
                        'type' => ucfirst($attendee->ticket->type),
                        'badge' => 'primary'
                    ] : null,
                    'created_at' => $attendee->created_at->format('d-m-Y H:i'),
                    'actions' => [
                        'view_url' => route('events.attendees.show', ['event' => $attendee->event_id, 'attendee' => $attendee->id]),
                        'edit_url' => route('events.attendees.edit', ['event' => $attendee->event_id, 'attendee' => $attendee->id]),
                        'delete_url' => route('events.attendees.destroy', ['event' => $attendee->event_id, 'attendee' => $attendee->id])
                    ]
                ];
            });

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $data,
                'pagination' => [
                    'first_item' => $attendees->firstItem(),
                    'last_item' => $attendees->lastItem(),
                    'total' => $attendees->total(),
                    'current_page' => $attendees->currentPage(),
                    'last_page' => $attendees->lastPage(),
                    'per_page' => $attendees->perPage()
                ]
            ]);
        }

        $events = Event::select('id', 'name')->orderBy('name')->get();
        $attendees = $event->attendees()->with(['event', 'ticket'])->paginate(15);
        return view('attendees.index', compact('events', 'event', 'attendees'));
    }

    /**
     * Show the form for creating a new attendee.
     */
    public function create(Event $event): View
    {
        return view('attendees.create', compact('event'));
    }

    /**
     * Store a newly created attendee in storage.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone' => 'required|string|max:20',
            'position' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'requires_ticket' => 'boolean',
            'ticket_type' => 'required_if:requires_ticket,1|in:single,double,regular,vip'
        ]);

        try {
            DB::beginTransaction();

            // Set default email if none provided
            if (empty($validated['email'])) {
                $validated['email'] = 'admin@gmail.com';
            }

            // Check if attendee already exists for this event
            $existingAttendee = $event->attendees()
                ->where('email', $validated['email'])
                ->first();

            if ($existingAttendee) {
                throw new \Exception('An attendee with this email already exists for this event.');
            }

            // Generate unique token (7 digits)
            do {
                $token = str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            } while (Attendee::where('token', $token)->exists());

            // Generate QR code with check-in URL
            $checkInUrl = route('scanner.check-in', ['token' => $token], true);
            $qrCode = QrCode::format('png')
                ->size(300)
                ->errorCorrection('H')
                ->generate($checkInUrl);

            // Sanitize name for filename
            $sanitizedName = preg_replace('/[^a-zA-Z0-9-]/', '-', strtolower($validated['name']));
            $sanitizedName = preg_replace('/-+/', '-', $sanitizedName);
            $sanitizedName = trim($sanitizedName, '-');

            // Store QR code image with attendee name in filename
            $qrPath = 'qr-codes/' . $sanitizedName . '-' . $token . '.png';
            Storage::disk('public')->put($qrPath, $qrCode);

            // Create attendee with token and QR code
            $attendeeData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'token' => $token,
                'qr_code' => $qrPath,
                'registration_status' => 'unchecked_in',
                'registered_at' => now()
            ];

            // Add optional fields if they are present
            if (isset($validated['phone'])) {
                $attendeeData['phone'] = $validated['phone'];
            }
            if (isset($validated['position'])) {
                $attendeeData['position'] = $validated['position'];
            }
            if (isset($validated['notes'])) {
                $attendeeData['notes'] = $validated['notes'];
            }

            // Create attendee
            $attendee = $event->attendees()->create($attendeeData);

            // Generate ticket if required
            if ($request->boolean('requires_ticket')) {
                // Generate ticket number
                $ticketNumber = 'TICKET-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

                // Create ticket for attendee
                $ticketData = [
                    'event_id' => $event->id,
                    'type' => $request->input('ticket_type'),
                    'status' => 'active',
                    'token' => $token,
                    'ticket_number' => $ticketNumber,
                    'attendee_id' => $attendee->id,
                    'created_at' => now()
                ];

                // Create the ticket
                $ticket = \App\Models\Ticket::create($ticketData);

                // Log the ticket creation
                Log::info('Ticket created for attendee', [
                    'attendee_id' => $attendee->id,
                    'ticket_id' => $ticket->id,
                    'event_id' => $event->id
                ]);
            }

            DB::commit();

            return redirect()
                ->route('events.attendees.index', $event)
                ->with('success', 'Attendee registered successfully with ' . ($request->boolean('requires_ticket') ? 'ticket' : 'registration') . '.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Attendee registration failed', [
                'error' => $e->getMessage(),
                'event_id' => $event->id,
                'data' => $validated
            ]);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified attendee.
     */
    public function show(Event $event, Attendee $attendee): View
    {
        $attendee->load([
            'event',
            'ticket.scanLogs' => function($query) {
                $query->with('scanner')->latest();
            }
        ]);
        
        return view('attendees.show', compact('event', 'attendee'));
    }

    /**
     * Show the form for editing the specified attendee.
     */
    public function edit(Event $event, Attendee $attendee): View
    {
        $events = Event::select('id', 'name')->orderBy('name')->get();
        return view('attendees.edit', compact('event', 'attendee', 'events'));
    }

    /**
     * Update the specified attendee in storage.
     */
    public function update(Request $request, Event $event, Attendee $attendee): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'position' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            // Set default email if none provided
            if (empty($validated['email'])) {
                $validated['email'] = 'admin@gmail.com';
            }

            // Check if email is being changed and if it's unique for this event
            if ($validated['email'] !== $attendee->email) {
                $exists = $event->attendees()
                    ->where('email', $validated['email'])
                    ->where('id', '!=', $attendee->id)
                    ->exists();

                if ($exists) {
                    throw new \Exception('An attendee with this email already exists for this event.');
                }
            }

            $attendee->update($validated);

            DB::commit();

            return redirect()
                ->route('events.attendees.index', $event)
                ->with('success', 'Attendee updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Attendee update failed', [
                'error' => $e->getMessage(),
                'attendee_id' => $attendee->id,
                'event_id' => $event->id,
                'data' => $validated
            ]);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified attendee from storage.
     */
    public function destroy(Event $event, Attendee $attendee): RedirectResponse
    {
        try {
            DB::beginTransaction();
            
            // Delete associated ticket first
            if ($attendee->ticket) {
                $attendee->ticket->delete();
            }
            
            $attendee->delete();
            
            DB::commit();

            return redirect()
                ->route('events.attendees.index', $event)
                ->with('success', 'Attendee deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Attendee deletion failed', [
                'error' => $e->getMessage(),
                'attendee_id' => $attendee->id,
                'event_id' => $event->id
            ]);

            return back()->with('error', 'Failed to remove attendee. Please try again.');
        }
    }

    /**
     * Show the form for bulk uploading attendees.
     */
    public function showBulkUploadForm(Event $event): View
    {
        return view('attendees.bulk-upload', compact('event'));
    }

    /**
     * Process the bulk upload of attendees.
     */
    public function bulkUpload(Request $request, Event $event): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        
        DB::beginTransaction();
        try {
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            while ($row = fgetcsv($handle)) {
                try {
                    $data = array_combine($header, $row);
                    
                    // Validate required fields
                    if (empty($data['name']) || empty($data['email'])) {
                        throw new \Exception('Name and email are required');
                    }

                    // Check for duplicate email
                    $exists = $event->attendees()
                        ->where('email', $data['email'])
                        ->exists();

                    if ($exists) {
                        throw new \Exception('Duplicate email: ' . $data['email']);
                    }

                    // Create attendee with unchecked_in status
                    $attendee = $event->attendees()->create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'] ?? null,
                        'position' => $data['position'] ?? null,
                        'registration_status' => 'unchecked_in',
                        'registered_at' => now()
                    ]);

                    // Create ticket
                    $attendee->ticket()->create([
                        'event_id' => $event->id,
                        'type' => $data['ticket_type'] ?? 'regular',
                        'status' => 'active'
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row " . ($successCount + $errorCount) . ": " . $e->getMessage();
                }
            }
            
            DB::commit();

            $message = "Upload completed. Successfully imported {$successCount} attendees.";
            if ($errorCount > 0) {
                $message .= " Failed to import {$errorCount} attendees.";
            }

            return redirect()
                ->route('events.attendees.index', $event)
                ->with('success', $message)
                ->with('errors', $errors);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk upload failed', [
                'error' => $e->getMessage(),
                'event_id' => $event->id
            ]);

            return back()
                ->with('error', 'Error processing file. Please check the format and try again.');
        } finally {
            fclose($handle);
        }
    }

    /**
     * Download the bulk upload template.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendees_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['name', 'email', 'phone', 'position', 'ticket_type']);
            
            // Add example row
            fputcsv($file, ['John Doe', 'john@example.com', '+1234567890', 'Developer', 'regular']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Check in an attendee.
     */
    public function checkIn(Event $event, Attendee $attendee): RedirectResponse
    {
        try {
            DB::beginTransaction();

            if (!$attendee->ticket) {
                throw new \Exception('Attendee does not have a ticket.');
            }

            if ($attendee->registration_status === 'checked_in') {
                throw new \Exception('Attendee has already been checked in.');
            }

            // Create scan log
            $scan = \App\Models\Scan::create([
                'ticket_id' => $attendee->ticket->id,
                'event_id' => $event->id,
                'scanner_id' => auth()->id(),
                'scanned_at' => now(),
                'status' => 'success'
            ]);

            // Update attendee status
            $attendee->update([
                'registration_status' => 'checked_in',
                'checked_in_at' => now()
            ]);

            // Update ticket status
            $attendee->ticket->update([
                'status' => 'used',
                'last_scanned_at' => now()
            ]);

            DB::commit();

            return redirect()
                ->route('events.attendees.index', $event)
                ->with('success', 'Attendee checked in successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Attendee check-in failed', [
                'error' => $e->getMessage(),
                'attendee_id' => $attendee->id,
                'event_id' => $event->id
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Validate an attendee by their token
     */
    public function validateToken(string $token)
    {
        $attendee = Attendee::where('token', $token)
            ->with(['event', 'ticket'])
            ->firstOrFail();

        // Check if the attendee has a valid ticket
        if (!$attendee->hasValidTicket()) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Invalid or expired ticket',
                'attendee' => $attendee->only(['name', 'email'])
            ], 400);
        }

        // Check if the ticket has already been used
        if ($attendee->ticket->status === 'used') {
            return response()->json([
                'status' => 'used',
                'message' => 'Ticket has already been used',
                'attendee' => $attendee->only(['name', 'email']),
                'used_at' => $attendee->ticket->used_at
            ], 400);
        }

        // Mark ticket as used
        DB::transaction(function () use ($attendee) {
            $attendee->ticket->update([
                'status' => 'used',
                'used_at' => now()
            ]);

            // Log the scan
            ScanLog::create([
                'scanner_id' => auth()->id(),
                'ticket_id' => $attendee->ticket->id,
                'scanned_at' => now(),
                'status' => 'success'
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Ticket validated successfully',
            'attendee' => $attendee->only(['name', 'email', 'position']),
            'event' => $attendee->event->only(['name', 'venue', 'start_date'])
        ]);
    }

    /**
     * Regenerate QR code for an attendee
     */
    public function regenerateQrCode(Event $event, Attendee $attendee): RedirectResponse
    {
        try {
            // Delete old QR code if exists
            if ($attendee->qr_code) {
                Storage::disk('public')->delete($attendee->qr_code);
            }

            // Generate new QR code with check-in URL
            $checkInUrl = route('scanner.check-in', ['token' => $attendee->token], true);
            $qrCode = QrCode::format('png')
                ->size(300)
                ->errorCorrection('H')
                ->generate($checkInUrl);

            // Sanitize name for filename
            $sanitizedName = preg_replace('/[^a-zA-Z0-9-]/', '-', strtolower($attendee->name));
            $sanitizedName = preg_replace('/-+/', '-', $sanitizedName);
            $sanitizedName = trim($sanitizedName, '-');

            // Store QR code image with attendee name in filename
            $path = 'qr-codes/' . $sanitizedName . '-' . $attendee->token . '.png';
            Storage::disk('public')->put($path, $qrCode);

            // Update attendee record
            $attendee->update(['qr_code' => $path]);

            return redirect()
                ->route('events.attendees.show', ['event' => $event, 'attendee' => $attendee])
                ->with('success', 'QR code regenerated successfully.');
        } catch (\Exception $e) {
            Log::error('QR code regeneration failed', [
                'error' => $e->getMessage(),
                'attendee_id' => $attendee->id,
                'event_id' => $event->id
            ]);

            return back()->with('error', 'Failed to regenerate QR code. Please try again.');
        }
    }

    /**
     * Cancel an attendee's registration.
     */
    public function cancel(Event $event, Attendee $attendee): RedirectResponse
    {
        try {
            DB::beginTransaction();

            if ($attendee->registration_status === 'checked_in') {
                throw new \Exception('Cannot cancel a checked-in attendee.');
            }

            $attendee->update([
                'registration_status' => 'cancelled',
                'cancelled_at' => now()
            ]);

            // If there's an associated ticket, update its status
            if ($attendee->ticket) {
                $attendee->ticket->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now()
                ]);
            }

            DB::commit();

            return redirect()
                ->route('events.attendees.index', $event)
                ->with('success', 'Registration cancelled successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Attendee cancellation failed', [
                'error' => $e->getMessage(),
                'attendee_id' => $attendee->id,
                'event_id' => $event->id
            ]);

            return back()->with('error', $e->getMessage());
        }
    }
} 