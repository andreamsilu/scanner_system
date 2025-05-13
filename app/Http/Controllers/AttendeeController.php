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

class AttendeeController extends Controller
{
    protected $attendeeService;

    public function __construct(AttendeeService $attendeeService)
    {
        $this->attendeeService = $attendeeService;
    }

    public function index(): View
    {
        $attendees = Attendee::with(['event', 'ticketType'])
            ->latest()
            ->paginate(15);
        return view('attendees.index', compact('attendees'));
    }

    public function create(): View
    {
        $events = Event::where('status', 'active')->get();
        $ticketTypes = TicketType::all();
        return view('attendees.create', compact('events', 'ticketTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'ticket_type' => 'required|string|in:regular,vip,early_bird',
            'registration_date' => 'required|date',
            'status' => 'required|string|in:registered,checked_in,cancelled',
        ]);

        $attendee = $this->attendeeService->createAttendee($validated);

        return redirect()
            ->route('attendees.show', $attendee)
            ->with('success', 'Attendee created successfully.');
    }

    public function show(Attendee $attendee): View
    {
        return view('attendees.show', compact('attendee'));
    }

    public function edit(Attendee $attendee): View
    {
        $events = Event::where('status', 'active')->get();
        $ticketTypes = TicketType::all();
        return view('attendees.edit', compact('attendee', 'events', 'ticketTypes'));
    }

    public function update(Request $request, Attendee $attendee): RedirectResponse
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'status' => 'required|in:registered,checked_in,cancelled'
        ]);

        $this->attendeeService->updateAttendee($attendee, $validated);

        return redirect()
            ->route('attendees.show', $attendee)
            ->with('success', 'Attendee updated successfully.');
    }

    public function destroy(Attendee $attendee): RedirectResponse
    {
        $this->attendeeService->deleteAttendee($attendee);

        return redirect()
            ->route('attendees.index')
            ->with('success', 'Attendee deleted successfully.');
    }

    public function bulkUploadForm(): View
    {
        $recentUploads = DB::table('attendee_imports')
            ->latest()
            ->take(5)
            ->get();

        return view('attendees.bulk-upload', compact('recentUploads'));
    }

    public function bulkUpload(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        try {
            $csv = Reader::createFromPath($request->file('csv_file')->getPathname());
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $totalRecords = 0;
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($records as $offset => $record) {
                $totalRecords++;

                try {
                    $validated = $this->validateRecord($record);
                    $this->attendeeService->createAttendee($validated);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Row {$offset}: " . $e->getMessage();
                }
            }

            // Log the import
            DB::table('attendee_imports')->insert([
                'total_records' => $totalRecords,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'status' => $errorCount === 0 ? 'completed' : 'completed_with_errors',
                'errors' => json_encode($errors),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            $message = "Import completed. {$successCount} records imported successfully.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} records failed.";
            }

            return redirect()
                ->route('attendees.index')
                ->with('success', $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk upload failed: ' . $e->getMessage());

            return redirect()
                ->back()
                ->with('error', 'Failed to process the file. Please check the file format and try again.');
        }
    }

    protected function validateRecord(array $record): array
    {
        return [
            'event_id' => $record['event_id'],
            'name' => $record['name'],
            'email' => $record['email'],
            'phone' => $record['phone'] ?? null,
            'ticket_type_id' => $record['ticket_type_id'],
            'status' => $record['status']
        ];
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