<?php

namespace App\Http\Controllers;

use App\Models\CardTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CardTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = CardTemplate::with(['creator', 'updater'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('card-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $types = CardTemplate::getTypes();
        $defaultTemplate = $this->getDefaultTemplate();
        
        return view('card-templates.create', compact('types', 'defaultTemplate'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'type' => 'required|string|in:' . implode(',', array_keys(CardTemplate::getTypes())),
            'is_active' => 'boolean',
            'preview_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        // Handle preview image upload
        if ($request->hasFile('preview_image')) {
            $path = $request->file('preview_image')->store('card-templates/previews', 'public');
            $data['preview_image'] = $path;
        }

        CardTemplate::create($data);

        return redirect()->route('card-templates.index')
            ->with('success', 'Card template created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(CardTemplate $cardTemplate)
    {
        $cardTemplate->load(['creator', 'updater']);
        
        return view('card-templates.show', compact('cardTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CardTemplate $cardTemplate)
    {
        $types = CardTemplate::getTypes();
        
        return view('card-templates.edit', compact('cardTemplate', 'types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CardTemplate $cardTemplate)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'type' => 'required|string|in:' . implode(',', array_keys(CardTemplate::getTypes())),
            'is_active' => 'boolean',
            'preview_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'settings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['updated_by'] = Auth::id();

        // Handle preview image upload
        if ($request->hasFile('preview_image')) {
            // Delete old preview image
            if ($cardTemplate->preview_image) {
                Storage::disk('public')->delete($cardTemplate->preview_image);
            }
            
            $path = $request->file('preview_image')->store('card-templates/previews', 'public');
            $data['preview_image'] = $path;
        }

        $cardTemplate->update($data);

        return redirect()->route('card-templates.index')
            ->with('success', 'Card template updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CardTemplate $cardTemplate)
    {
        // Delete preview image if exists
        if ($cardTemplate->preview_image) {
            Storage::disk('public')->delete($cardTemplate->preview_image);
        }

        $cardTemplate->delete();

        return redirect()->route('card-templates.index')
            ->with('success', 'Card template deleted successfully!');
    }

    /**
     * Preview template with sample data
     */
    public function preview(CardTemplate $cardTemplate)
    {
        $sampleData = $this->getSampleData();
        $renderedContent = $cardTemplate->render($sampleData);
        
        return response()->json([
            'html' => $renderedContent,
            'template' => $cardTemplate
        ]);
    }

    /**
     * Toggle template active status
     */
    public function toggleStatus(CardTemplate $cardTemplate)
    {
        $cardTemplate->update([
            'is_active' => !$cardTemplate->is_active,
            'updated_by' => Auth::id()
        ]);

        return redirect()->back()
            ->with('success', 'Template status updated successfully!');
    }

    /**
     * Get templates by type (API endpoint)
     */
    public function getByType($type)
    {
        $templates = CardTemplate::active()->ofType($type)->get();
        
        return response()->json($templates);
    }

    /**
     * Get default template content
     */
    private function getDefaultTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ event_name }} - {{ attendee_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .card {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .attendee-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .qr-section {
            text-align: center;
            padding: 20px;
            border-top: 1px solid #dee2e6;
        }
        .qr-code {
            width: 150px;
            height: 150px;
            margin: 0 auto;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>{{ event_name }}</h1>
            <p>{{ event_date }} at {{ event_time }}</p>
        </div>
        
        <div class="content">
            <div class="attendee-info">
                <h3>Attendee Information</h3>
                <p><strong>Name:</strong> {{ attendee_name }}</p>
                <p><strong>Email:</strong> {{ attendee_email }}</p>
                @if(attendee_phone)
                <p><strong>Phone:</strong> {{ attendee_phone }}</p>
                @endif
                @if(attendee_company)
                <p><strong>Company:</strong> {{ attendee_company }}</p>
                @endif
                @if(attendee_position)
                <p><strong>Position:</strong> {{ attendee_position }}</p>
                @endif
            </div>
            
            <div class="event-details">
                <h3>Event Details</h3>
                <p><strong>Venue:</strong> {{ event_venue }}</p>
                <p><strong>Ticket ID:</strong> {{ ticket_id }}</p>
                <p><strong>Status:</strong> {{ attendee_status }}</p>
            </div>
        </div>
        
        <div class="qr-section">
            <h4>Entry Pass</h4>
            <div class="qr-code">
                <!-- QR Code will be generated here -->
                <div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                    <span style="color: #666;">QR Code</span>
                </div>
            </div>
            <p style="margin-top: 10px; color: #666; font-size: 14px;">
                Scan this QR code at the venue
            </p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Get sample data for template preview
     */
    private function getSampleData(): array
    {
        return [
            'event_name' => 'Sample Event 2025',
            'event_date' => 'January 15, 2025',
            'event_time' => '2:00 PM',
            'event_venue' => 'Sample Conference Center',
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+255 712 345 678',
            'attendee_company' => 'Sample Company Ltd',
            'attendee_position' => 'Senior Manager',
            'attendee_status' => 'Registered',
            'ticket_id' => 'TKT-2025-001',
            'qr_token' => 'sample-qr-token-123'
        ];
    }
}
