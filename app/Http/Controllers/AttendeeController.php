<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use App\Models\Event;
use App\Models\TicketType;
use App\Services\AttendeeService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use Illuminate\Support\Facades\Validator;

class AttendeeController extends Controller
{
    protected $attendeeService;

    public function __construct(AttendeeService $attendeeService)
    {
        $this->attendeeService = $attendeeService;
    }

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
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'registration_status' => 'required|in:registered,checked_in,cancelled',
            'notes' => 'nullable|string',
        ]);

        $attendee = $event->attendees()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'registration_status' => $validated['registration_status'],
            'notes' => $validated['notes'],
            'registered_at' => now(),
        ]);

        return redirect()
            ->route('events.attendees', $event)
            ->with('success', 'Attendee added successfully.');
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
        $attendee->delete();

        return redirect()
            ->route('events.attendees', $event)
            ->with('success', 'Attendee removed successfully.');
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
            while ($row = fgetcsv($handle)) {
                $data = array_combine($header, $row);
                
                $event->attendees()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'registration_status' => 'registered',
                    'registered_at' => now(),
                ]);
            }
            
            DB::commit();
            return redirect()
                ->route('events.attendees', $event)
                ->with('success', 'Attendees uploaded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Error uploading attendees. Please check your file format.');
        } finally {
            fclose($handle);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendees_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['event_id', 'name', 'email', 'phone', 'ticket_type_id', 'status']);
            
            // Add sample data
            fputcsv($file, ['1', 'John Doe', 'john@example.com', '1234567890', '1', 'registered']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
} 