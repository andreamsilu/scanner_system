<?php

namespace App\Http\Controllers;

use App\Models\Attendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PDF;

class EventCardManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Attendee::query();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('qr_token', 'like', "%{$search}%");
            });
        }

        // Filter by scan status
        if ($request->has('status')) {
            $query->where('scanned', $request->input('status') === 'scanned');
        }

        $attendees = $query->latest()->paginate(10);

        return view('event-cards.index', compact('attendees'));
    }

    public function create()
    {
        return view('event-cards.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:attendees',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        $attendee = Attendee::create($validated);

        return redirect()
            ->route('event-cards')
            ->with('success', 'Event card created successfully.');
    }

    public function edit(Attendee $attendee)
    {
        return view('event-cards.edit', compact('attendee'));
    }

    public function update(Request $request, Attendee $attendee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:attendees,email,' . $attendee->id,
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        $attendee->update($validated);

        return redirect()
            ->route('event-cards')
            ->with('success', 'Event card updated successfully.');
    }

    public function destroy(Attendee $attendee)
    {
        $attendee->delete();

        return redirect()
            ->route('event-cards')
            ->with('success', 'Event card deleted successfully.');
    }

    public function download(Attendee $attendee)
    {
        // Generate QR code and PDF logic here
        $pdf = PDF::loadView('pdf.event-card', compact('attendee'));
        
        return $pdf->download("event-card-{$attendee->qr_token}.pdf");
    }
} 