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

class AttendeeController extends Controller
{
    /**
     * Display a listing of the attendees.
     */
    public function index(): View
    {
        $attendees = Attendee::with('events')
            ->latest()
            ->paginate(15);

        return view('attendees.index', compact('attendees'));
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
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            // Check if attendee already exists for this event
            $existingAttendee = $event->attendees()
                ->where('email', $validated['email'])
                ->first();

            if ($existingAttendee) {
                throw new \Exception('An attendee with this email already exists for this event.');
            }

            // Create attendee
            $attendee = $event->attendees()->create($validated);

            // Create ticket for attendee
            $ticket = $attendee->ticket()->create([
                'event_id' => $event->id,
                'type' => 'regular',
                'status' => 'active'
            ]);

            DB::commit();

            return redirect()
                ->route('events.attendees', $event)
                ->with('success', 'Attendee registered successfully.');
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
        return view('attendees.show', compact('event', 'attendee'));
    }

    /**
     * Show the form for editing the specified attendee.
     */
    public function edit(Event $event, Attendee $attendee): View
    {
        return view('attendees.edit', compact('event', 'attendee'));
    }

    /**
     * Update the specified attendee in storage.
     */
    public function update(Request $request, Event $event, Attendee $attendee): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'registration_status' => 'required|in:registered,checked_in,cancelled',
            'notes' => 'nullable|string',
        ]);

        $attendee->update($validated);

        return redirect()
            ->route('events.attendees', $event)
            ->with('success', 'Attendee updated successfully.');
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
                ->route('events.attendees', $event)
                ->with('success', 'Attendee removed successfully.');
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

                    // Create attendee
                    $attendee = $event->attendees()->create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'] ?? null,
                        'position' => $data['position'] ?? null
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
                ->route('events.attendees', $event)
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
} 