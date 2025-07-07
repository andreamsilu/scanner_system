<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\Attendee;
use App\Services\SmsService;
use App\Jobs\SendSingleSms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Exports\TicketsExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Intervention\Image\Facades\Image;
use Intervention\Image\Drivers\Gd\Driver;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TicketController extends Controller
{
    /**
     * The SMS service instance
     *
     * @var SmsService
     */
    protected SmsService $smsService;

    /**
     * Create a new controller instance
     */
    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Display a listing of the tickets.
     */
    public function index(Event $event)
    {
        $tickets = Ticket::with(['event', 'attendee'])
            ->where('event_id', $event->id)
            ->latest()
            ->paginate(20);

        return view('tickets.index', compact('tickets', 'event'));
    }

    /**
     * Generate new tickets for an event.
     */
    public function generate(Request $request, Event $event)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
            'ticket_type' => 'required|in:regular,vip,single,double'
        ]);

        $tickets = collect();
        
        for ($i = 0; $i < $request->quantity; $i++) {
            $ticket = $event->tickets()->create([
                'ticket_id' => Str::uuid(),
                'type' => $request->ticket_type,
                'status' => 'active'
            ]);
            
            $tickets->push($ticket);
        }

        return redirect()
            ->route('events.tickets', $event)
            ->with('success', "{$request->quantity} tickets generated successfully.");
    }

    /**
     * Display the specified ticket.
     */
    public function show(Event $event, $token)
    {
        $ticket = Ticket::whereHas('attendee', function($query) use ($token) {
            $query->where('token', $token);
        })
        ->where('event_id', $event->id)
        ->with(['attendee', 'scanLogs' => function($query) {
            $query->with('scanner')->latest();
        }])
        ->firstOrFail();

        return view('tickets.show', compact('event', 'ticket'));
    }

    /**
     * Show the form for printing a ticket.
     */
    public function print(Event $event, $token)
    {
        $ticket = Ticket::whereHas('attendee', function($query) use ($token) {
            $query->where('token', $token);
        })->where('event_id', $event->id)->firstOrFail();

        return view('tickets.print', compact('event', 'ticket'));
    }

    /**
     * Deactivate a specific ticket.
     */
    public function deactivate(Event $event, $token)
    {
        $ticket = Ticket::whereHas('attendee', function($query) use ($token) {
            $query->where('token', $token);
        })->where('event_id', $event->id)->firstOrFail();

        $ticket->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        return redirect()->route('events.tickets.index', $event)
            ->with('success', 'Ticket has been deactivated successfully.');
    }

    /**
     * Deactivate all tickets for an event.
     */
    public function deactivateAll(Event $event)
    {
        $event->tickets()->update(['status' => 'deactivated']);
        
        return redirect()
            ->back()
            ->with('success', 'All tickets deactivated successfully.');
    }

    /**
     * Export tickets for an event to CSV.
     */
    public function export(Event $event)
    {
        $tickets = $event->tickets()
            ->with('attendee')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tickets.csv"',
        ];

        $callback = function() use ($tickets) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['Ticket ID', 'Type', 'Status', 'Attendee', 'Created At']);
            
            // Add data
            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->ticket_id,
                    $ticket->type,
                    $ticket->status,
                    $ticket->attendee ? $ticket->attendee->name : 'Unassigned',
                    $ticket->created_at->format('Y-m-d H:i:s')
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show the form for creating a new ticket for an attendee.
     */
    public function create(Event $event, Attendee $attendee): View
    {
        // Check if attendee already has a ticket
        if ($attendee->ticket) {
            return redirect()
                ->route('events.attendees.show', ['event' => $event, 'attendee' => $attendee])
                ->with('error', 'This attendee already has a ticket.');
        }

        return view('tickets.create', compact('event', 'attendee'));
    }

    /**
     * Store a newly created ticket for an attendee.
     */
    public function store(Request $request, Event $event, Attendee $attendee): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Check if attendee already has a ticket
            if ($attendee->ticket) {
                throw new \Exception('This attendee already has a ticket.');
            }

            // Validate request
            $validated = $request->validate([
                'type' => ['required', 'string', Rule::in(['regular', 'vip', 'single', 'double'])],
            ]);

            // Generate ticket number
            $ticketNumber = Ticket::generateTicketNumber();

            // Create ticket
            $ticket = Ticket::create([
                'event_id' => $event->id,
                'attendee_id' => $attendee->id,
                'type' => $validated['type'],
                'status' => 'active',
                'ticket_number' => $ticketNumber,
                'token' => $attendee->token
            ]);

            // Log the ticket creation
            Log::info('Ticket created for attendee', [
                'attendee_id' => $attendee->id,
                'ticket_id' => $ticket->id,
                'event_id' => $event->id
            ]);

            // Send invitation SMS if attendee has a phone number
            if ($attendee->phone) {
                try {
                    // Dispatch SMS job to avoid blocking the response
                    SendSingleSms::dispatch(
                        $attendee->phone,
                        $this->formatInvitationMessage($event, $ticket),
                        'invitation',
                        [
                            'event_id' => $event->id,
                            'attendee_id' => $attendee->id,
                            'ticket_id' => $ticket->id,
                            'attendee_name' => $attendee->name,
                            'attendee_phone' => $attendee->phone,
                            'ticket_number' => $ticket->ticket_number,
                            'ticket_type' => $ticket->type,
                            'event_name' => $event->name,
                            'event_date' => $event->date ? \Carbon\Carbon::parse($event->date)->toDateTimeString() : null,
                            'location' => $event->location,
                            'venue' => $event->venue
                        ]
                    );

                    Log::info('Invitation SMS job dispatched for ticket creation', [
                        'attendee_id' => $attendee->id,
                        'ticket_id' => $ticket->id,
                        'phone' => $attendee->phone
                    ]);
                } catch (\Exception $smsException) {
                    Log::error('Failed to dispatch invitation SMS for ticket creation', [
                        'attendee_id' => $attendee->id,
                        'ticket_id' => $ticket->id,
                        'phone' => $attendee->phone,
                        'error' => $smsException->getMessage()
                    ]);
                    // Don't fail the ticket creation if SMS fails
                }
            } else {
                Log::info('No phone number available for SMS invitation', [
                    'attendee_id' => $attendee->id,
                    'ticket_id' => $ticket->id
                ]);
            }

            DB::commit();

            return redirect()
                ->route('events.attendees.show', ['event' => $event, 'attendee' => $attendee])
                ->with('success', 'Ticket created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ticket creation failed', [
                'error' => $e->getMessage(),
                'attendee_id' => $attendee->id,
                'event_id' => $event->id
            ]);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Format an invitation message for SMS
     *
     * @param Event $event The event
     * @param Ticket $ticket The ticket
     * @return string Formatted message
     */
    protected function formatInvitationMessage(Event $event, Ticket $ticket): string
    {
        $attendee = $ticket->attendee;
        $eventDate = $event->date ? \Carbon\Carbon::parse($event->date) : null;
        
        if ($eventDate) {
            $dayName = $eventDate->locale('sw')->isoFormat('dddd');
            $formattedTime = $eventDate->format('g:i');
            $period = $eventDate->format('A');
            $dateString = $dayName . ' ' . $eventDate->toLocaleDateString('en-GB') . ' saa ' . $formattedTime . ' ' . $period;
        } else {
            $dateString = 'tarehe itakayotangazwa';
        }

        return sprintf(
            "Ndugu %s, unakaribishwa kwenye %s itakayofanyika %s. Mahali: %s. Ukumbi: %s. Tiketi: %s (%s). Tafadhali wasilisha tiketi hii mlangoni. Maelezo: 0769289824",
            $attendee->name,
            $event->name,
            $dateString,
            $event->location,
            $event->venue,
            $ticket->ticket_number,
            ucfirst($ticket->type)
        );
    }

    /**
     * Verify a ticket's validity
     *
     * @param string $token
     * @return \Illuminate\View\View
     */
    public function verify(string $token)
    {
        $ticket = Ticket::whereHas('attendee', function ($query) use ($token) {
            $query->where('token', $token)
                  ->whereNull('deleted_at');
        })->first();

        if (!$ticket) {
            abort(404, 'Ticket not found');
        }

        $event = $ticket->event;
        $attendee = $ticket->attendee;

        return view('tickets.verify', compact('ticket', 'event', 'attendee'));
    }

    /**
     * Export a single ticket as PDF
     */
    public function exportTicket(Event $event, $token)
    {
        $ticket = Ticket::whereHas('attendee', function($query) use ($token) {
            $query->where('token', $token);
        })
        ->where('event_id', $event->id)
        ->with(['attendee', 'event'])
        ->firstOrFail();

        $pdf = \PDF::loadView('tickets.show', compact('ticket'));
        
        return $pdf->download('ticket-' . $ticket->token . '.pdf');
    }

    /**
     * Get tickets data for DataTable with server-side processing
     */
    public function getTicketsData(Request $request, Event $event)
    {
        $query = $event->tickets()
            ->with(['attendee', 'scanLogs' => function($query) {
                $query->latest();
            }]);

        // Handle search
        if ($request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function($q) use ($searchValue) {
                // Search in tickets table
                $q->where('tickets.ticket_number', 'like', '%' . $searchValue . '%')
                  ->orWhere('tickets.type', 'like', '%' . $searchValue . '%')
                  ->orWhere('tickets.status', 'like', '%' . $searchValue . '%')
                  ->orWhere('tickets.created_at', 'like', '%' . $searchValue . '%')
                  
                  // Search in attendees table
                  ->orWhereHas('attendee', function($q) use ($searchValue) {
                      $q->where('attendees.token', 'like', '%' . $searchValue . '%')
                        ->orWhere('attendees.name', 'like', '%' . $searchValue . '%')
                        ->orWhere('attendees.email', 'like', '%' . $searchValue . '%')
                        ->orWhere('attendees.phone', 'like', '%' . $searchValue . '%');
                  })
                  
                  // Search in scan logs
                  ->orWhereHas('scanLogs', function($q) use ($searchValue) {
                      $q->where('scan_logs.scanner_id', 'like', '%' . $searchValue . '%')
                        ->orWhere('scan_logs.status', 'like', '%' . $searchValue . '%')
                        ->orWhere('scan_logs.notes', 'like', '%' . $searchValue . '%')
                        ->orWhere('scan_logs.created_at', 'like', '%' . $searchValue . '%');
                  })
                  
                  // Search in scanner information
                  ->orWhereHas('scanLogs.scanner', function($q) use ($searchValue) {
                      $q->where('users.name', 'like', '%' . $searchValue . '%')
                        ->orWhere('users.email', 'like', '%' . $searchValue . '%');
                  });
            });
        }

        // Handle filters
        if ($request->ticket_type) {
            $query->where('type', $request->ticket_type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date_range) {
            $dates = explode(' - ', $request->date_range);
            if (count($dates) === 2) {
                $startDate = \Carbon\Carbon::parse($dates[0])->startOfDay();
                $endDate = \Carbon\Carbon::parse($dates[1])->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        // Get total count before pagination
        $totalRecords = $query->count();
        $totalFiltered = $totalRecords;

        // Define sortable columns and their relationships
        $sortableColumns = [
            'ticket_number' => 'tickets.ticket_number',
            'attendee' => 'attendees.token',
            'type' => 'tickets.type',
            'status' => 'tickets.status',
            'last_scan' => 'scan_logs.created_at',
            'created_at' => 'tickets.created_at'
        ];

        // Handle ordering
        $orderColumn = $request->order[0]['column'] ?? 6; // Default to created_at
        $orderDir = $request->order[0]['dir'] ?? 'desc';
        $columns = array_keys($sortableColumns);
        
        if (isset($columns[$orderColumn])) {
            $column = $columns[$orderColumn];
            $sortColumn = $sortableColumns[$column];

            // Handle special cases for relationships
            if ($column === 'attendee') {
                $query->leftJoin('attendees', 'tickets.attendee_id', '=', 'attendees.id')
                      ->orderBy($sortColumn, $orderDir)
                      ->select('tickets.*'); // Ensure we select only ticket columns
            } elseif ($column === 'last_scan') {
                $query->leftJoin('scan_logs', function($join) {
                    $join->on('tickets.id', '=', 'scan_logs.ticket_id')
                         ->whereRaw('scan_logs.id IN (SELECT MAX(id) FROM scan_logs GROUP BY ticket_id)');
                })
                ->orderBy($sortColumn, $orderDir)
                ->select('tickets.*'); // Ensure we select only ticket columns
            } else {
                $query->orderBy($sortColumn, $orderDir);
            }
        } else {
            // Default sorting by created_at desc if no valid column is specified
            $query->orderBy('tickets.created_at', 'desc');
        }

        // Apply pagination
        $start = $request->start ?? 0;
        $length = $request->length ?? 15;
        $tickets = $query->skip($start)->take($length)->get();

        // Transform data for DataTables
        $data = $tickets->map(function ($ticket) use ($event) {
            return [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'attendee' => $ticket->attendee ? [
                    'token' => $ticket->attendee->token,
                    'name' => $ticket->attendee->name
                ] : null,
                'type' => $ticket->type,
                'status' => [
                    'value' => $ticket->status,
                    'badge' => $ticket->status === 'active' ? 'success' : 
                             ($ticket->status === 'used' ? 'info' : 'danger')
                ],
                'last_scan' => $ticket->scanLogs->first() ? [
                    'time' => $ticket->scanLogs->first()->created_at->format('Y-m-d H:i:s'),
                    'diff' => $ticket->scanLogs->first()->created_at->diffForHumans()
                ] : null,
                'created_at' => $ticket->created_at->format('Y-m-d H:i:s'),
                'actions' => [
                    'view_url' => route('events.tickets.show', ['event' => $event->id, 'ticket' => $ticket->attendee?->token]),
                    'print_url' => route('events.tickets.print', ['event' => $event->id, 'ticket' => $ticket->attendee?->token]),
                    'deactivate_url' => $ticket->status === 'active' ? 
                        route('events.tickets.deactivate', ['event' => $event->id, 'ticket' => $ticket->attendee?->token]) : null
                ]
            ];
        });

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalFiltered,
            'data' => $data
        ]);
    }

    /**
     * Export all tickets in the specified format (Excel or PDF)
     *
     * @param string $format
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    public function exportAll($format = 'excel')
    {
        try {
            // Get all tickets with related data
            $tickets = DB::table('tickets')
                ->join('events', 'tickets.event_id', '=', 'events.id')
                ->leftJoin('attendees', 'tickets.attendee_id', '=', 'attendees.id')
                ->select([
                    'tickets.token',
                    'tickets.type',
                    'events.name as event_name',
                    'events.start_date as event_date',
                    'events.location as event_location',
                    'attendees.name as attendee_name',
                    'attendees.phone as attendee_phone'
                ])
                ->orderBy('tickets.created_at', 'desc')
                ->get();

            if ($format === 'excel') {
                return Excel::download(new TicketsExport($tickets), 'tickets-list-' . now()->format('Y-m-d-His') . '.xlsx');
            }

            // Generate PDF
            $pdf = PDF::loadView('tickets.export-pdf', [
                'tickets' => $tickets,
                'generatedAt' => now()->format('Y-m-d H:i:s')
            ]);

            return $pdf->download('tickets-list-' . now()->format('Y-m-d-His') . '.pdf');

        } catch (\Exception $e) {
            \Log::error('Ticket export failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to export tickets. Please try again.');
        }
    }

    /**
     * Download the ticket as a JPG image.
     */
    public function downloadJpg(Event $event, $token)
    {
        $ticket = Ticket::whereHas('attendee', function($query) use ($token) {
            $query->where('token', $token);
        })
        ->where('event_id', $event->id)
        ->with(['attendee', 'event'])
        ->firstOrFail();

        // Create base image
        $image = Image::canvas(800, 1200, '#ffffff');

        // Add background image
        $bgPath = public_path('images/image.png');
        if (file_exists($bgPath)) {
            $bg = Image::make($bgPath)->fit(800, 1200);
            $image->insert($bg, 'top-left', 0, 0);
            // Add semi-transparent overlay for readability
            $overlay = Image::canvas(800, 1200, [255,255,255,(int)(0.82*255)]);
            $image->insert($overlay, 'top-left', 0, 0);
        }

        // Add ticket details (simplified, can be styled further)
        $y = 80;
        $image->text('MWALIKO WA SENDOFF', 400, $y, function($font) {
            $font->color('#9CAF88');
            $font->size(36);
            $font->align('center');
        });
        $y += 60;
        $image->text('Familia ya MR & MRS PIENZI CHAMBILA', 400, $y, function($font) {
            $font->color('#5A6B47');
            $font->size(18);
            $font->align('center');
        });
        $y += 30;
        $image->text('wanayo furaha kukualika', 400, $y, function($font) {
            $font->color('#5A6B47');
            $font->size(16);
            $font->align('center');
        });
        $y += 40;
        $image->text('Kwa: ' . strtoupper($ticket->attendee->name), 400, $y, function($font) {
            $font->color('#5A6B47');
            $font->size(20);
            $font->align('center');
        });
        $y += 30;
        $image->text('Kwenye sherehe ya SENDOFF ya', 400, $y, function($font) {
            $font->color('#5A6B47');
            $font->size(16);
            $font->align('center');
        });
        $y += 30;
        $image->text('ALICE PIENZI CHAMBILA', 400, $y, function($font) {
            $font->color('#D2691E');
            $font->size(24);
            $font->align('center');
        });
        $y += 50;
        $image->text('Tarehe: Alhamisi, 05-06-2025', 400, $y, function($font) {
            $font->color('#5A6B47');
            $font->size(16);
            $font->align('center');
        });
        $y += 30;
        $image->text('Saa: 12:00 jioni', 400, $y, function($font) {
            $font->color('#5A6B47');
            $font->size(16);
            $font->align('center');
        });
        $y += 30;
        $image->text('Mahali: GBF Garden Hall, Dar es Salaam', 400, $y, function($font) {
            $font->color('#5A6B47');
            $font->size(16);
            $font->align('center');
        });
        $y += 50;
        $image->text('Tiketi: ' . $ticket->token, 400, $y, function($font) {
            $font->color('#D2691E');
            $font->size(18);
            $font->align('center');
        });
        $y += 30;
        $image->text('Aina: ' . strtoupper($ticket->type), 400, $y, function($font) {
            $font->color('#D2691E');
            $font->size(18);
            $font->align('center');
        });
        // Add QR code
        $qrCode = QrCode::format('png')
            ->size(180)
            ->generate(route('tickets.verify', $ticket->token));
        $qrImage = Image::make($qrCode);
        $image->insert($qrImage, 'bottom', 400 - 90, 100); // Centered horizontally
        // Footer
        $image->text('Wasilisha tiketi hii mlangoni', 400, 1100, function($font) {
            $font->color('#D2691E');
            $font->size(16);
            $font->align('center');
        });
        $image->text('Maelezo: 0769289824', 400, 1140, function($font) {
            $font->color('#5A6B47');
            $font->size(14);
            $font->align('center');
        });
        $jpgData = $image->encode('jpg', 90);
        $filename = 'ticket-' . $ticket->token . '.jpg';
        return response($jpgData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
} 