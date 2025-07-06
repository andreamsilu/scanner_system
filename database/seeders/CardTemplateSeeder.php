<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CardTemplate;
use App\Models\User;

class CardTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user or create one
        $adminUser = User::whereHas('roles', function($query) {
            $query->where('slug', 'admin');
        })->first() ?? User::first();

        $templates = [
            [
                'name' => 'Modern Invitation Card',
                'description' => 'A modern, elegant invitation card design with gradient header and clean layout',
                'type' => 'invitation',
                'is_active' => true,
                'content' => $this->getModernInvitationTemplate(),
                'created_by' => $adminUser?->id,
                'updated_by' => $adminUser?->id,
            ],
            [
                'name' => 'Classic Event Ticket',
                'description' => 'A classic ticket design with professional layout and QR code section',
                'type' => 'ticket',
                'is_active' => true,
                'content' => $this->getClassicTicketTemplate(),
                'created_by' => $adminUser?->id,
                'updated_by' => $adminUser?->id,
            ],
            [
                'name' => 'Sendoff Invitation',
                'description' => 'Traditional sendoff invitation design with Swahili text and elegant styling',
                'type' => 'invitation',
                'is_active' => true,
                'content' => $this->getSendoffInvitationTemplate(),
                'created_by' => $adminUser?->id,
                'updated_by' => $adminUser?->id,
            ],
            [
                'name' => 'Professional Badge',
                'description' => 'Professional name badge design for conferences and events',
                'type' => 'badge',
                'is_active' => true,
                'content' => $this->getProfessionalBadgeTemplate(),
                'created_by' => $adminUser?->id,
                'updated_by' => $adminUser?->id,
            ],
        ];

        foreach ($templates as $template) {
            CardTemplate::create($template);
        }
    }

    private function getModernInvitationTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ event_name }} - {{ attendee_name }}</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
            letter-spacing: 2px;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .invitation-text {
            text-align: center;
            font-size: 1.2rem;
            line-height: 1.8;
            color: #333;
            margin-bottom: 30px;
        }
        .guest-name {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .guest-name h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .event-details {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .event-details h4 {
            color: #667eea;
            margin-bottom: 15px;
        }
        .qr-section {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        .qr-code {
            width: 120px;
            height: 120px;
            margin: 0 auto 15px;
            background: white;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>{{ event_name }}</h1>
            <p>{{ event_date }} • {{ event_time }}</p>
        </div>
        
        <div class="content">
            <div class="invitation-text">
                <p>You are cordially invited to attend</p>
            </div>
            
            <div class="guest-name">
                <h3>{{ attendee_name }}</h3>
                @if(attendee_company)
                <p style="color: #666; margin: 0;">{{ attendee_company }}</p>
                @endif
            </div>
            
            <div class="event-details">
                <h4>Event Details</h4>
                <p><strong>Venue:</strong> {{ event_venue }}</p>
                <p><strong>Date:</strong> {{ event_date }}</p>
                <p><strong>Time:</strong> {{ event_time }}</p>
                <p><strong>Ticket ID:</strong> {{ ticket_id }}</p>
            </div>
        </div>
        
        <div class="qr-section">
            <h4>Entry Pass</h4>
            <div class="qr-code">
                <div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                    <span style="color: #666; font-size: 12px;">QR Code</span>
                </div>
            </div>
            <p style="margin: 0; color: #666; font-size: 14px;">
                Scan this QR code at the venue
            </p>
        </div>
    </div>
</body>
</html>';
    }

    private function getClassicTicketTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ event_name }} - Ticket</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }
        .ticket {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            border: 2px solid #333;
            border-radius: 10px;
            overflow: hidden;
        }
        .header {
            background: #333;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: bold;
        }
        .content {
            padding: 25px;
        }
        .attendee-info {
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .attendee-info h3 {
            color: #333;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .label {
            font-weight: bold;
            color: #555;
        }
        .value {
            color: #333;
        }
        .qr-section {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border-top: 1px solid #ddd;
        }
        .qr-code {
            width: 100px;
            height: 100px;
            margin: 0 auto 10px;
            background: white;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .footer {
            background: #333;
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>{{ event_name }}</h1>
            <p>{{ event_date }} • {{ event_time }}</p>
        </div>
        
        <div class="content">
            <div class="attendee-info">
                <h3>Attendee Information</h3>
                <div class="info-row">
                    <span class="label">Name:</span>
                    <span class="value">{{ attendee_name }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Email:</span>
                    <span class="value">{{ attendee_email }}</span>
                </div>
                @if(attendee_phone)
                <div class="info-row">
                    <span class="label">Phone:</span>
                    <span class="value">{{ attendee_phone }}</span>
                </div>
                @endif
                @if(attendee_company)
                <div class="info-row">
                    <span class="label">Company:</span>
                    <span class="value">{{ attendee_company }}</span>
                </div>
                @endif
            </div>
            
            <div class="event-details">
                <h3>Event Details</h3>
                <div class="info-row">
                    <span class="label">Venue:</span>
                    <span class="value">{{ event_venue }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Date:</span>
                    <span class="value">{{ event_date }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Time:</span>
                    <span class="value">{{ event_time }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Ticket ID:</span>
                    <span class="value">{{ ticket_id }}</span>
                </div>
            </div>
        </div>
        
        <div class="qr-section">
            <h4>Entry Pass</h4>
            <div class="qr-code">
                <span style="color: #666; font-size: 10px;">QR Code</span>
            </div>
            <p style="margin: 0; color: #666; font-size: 12px;">
                Scan at venue entrance
            </p>
        </div>
        
        <div class="footer">
            <p>Valid for one-time entry only</p>
        </div>
    </div>
</body>
</html>';
    }

    private function getSendoffInvitationTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sendoff Invitation - {{ attendee_name }}</title>
    <style>
        body {
            font-family: "Georgia", "Times New Roman", serif;
            margin: 0;
            padding: 20px;
            background: #f8f5f2;
        }
        .invitation {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border: 3px solid #bfa46d;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #bfa46d;
            font-size: 2.5rem;
            margin: 0;
            font-weight: bold;
        }
        .content {
            text-align: center;
            line-height: 1.8;
            color: #333;
        }
        .guest-name {
            margin: 30px 0;
            padding: 20px;
            border-bottom: 2px solid #bfa46d;
            min-height: 40px;
        }
        .guest-name h3 {
            color: #bfa46d;
            margin-bottom: 10px;
        }
        .event-details {
            margin: 25px 0;
        }
        .event-details strong {
            color: #bfa46d;
            font-size: 1.2rem;
        }
        .qr-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .qr-code {
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            background: white;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="invitation">
        <div class="header">
            <h1>MWALIKO WA SENDOFF</h1>
        </div>
        
        <div class="content">
            <p>
                Familia ya MR & MRS PIENZI CHAMBILA wa Kigogo, Dar es Salaam,<br>
                wanayo furaha kubwa kukualika / kuwaalika
            </p>
            <p>
                Ndg / Mhe / Dr / Prof / Mch / Bw / Bi / Bw & Bi
            </p>
            
            <div class="guest-name">
                <h3>Jina la Mwalikwa:</h3>
                <div style="font-size: 1.3rem; font-weight: bold; color: #333;">
                    {{ attendee_name }}
                </div>
            </div>
            
            <div class="event-details">
                <p>
                    Kwenye sherehe ya SENDOFF ya binti yao mpendwa<br>
                    <strong>ALICE PIENZI CHAMBILA</strong>
                </p>
                <p>
                    Itakayofanyika siku ya Alhamisi, tarehe 05-06-2025<br>
                    Kuanzia saa 12:00 jioni
                </p>
                <p>
                    Katika ukumbi wa GBF Garden Hall,<br>
                    uliopo Dar es Salaam – Tabata, Barakuda.
                </p>
                <p>
                    Karibu tusherehekee kwa pamoja tukio hili maalum la furaha na kumbukumbu ya pekee.
                </p>
            </div>
        </div>
        
        <div class="qr-section">
            <h4 style="color: #bfa46d;">Entry Pass</h4>
            <div class="qr-code">
                <div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                    <span style="color: #666;">QR Code</span>
                </div>
            </div>
            <p style="margin: 10px 0; color: #666; font-size: 14px;">
                Scan this QR code at the venue
            </p>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; display: inline-block;">
                <p style="margin: 0;">
                    <small style="color: #666;">Ticket ID</small><br>
                    <span style="font-weight: 500;">{{ ticket_id }}</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    private function getProfessionalBadgeTemplate(): string
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ attendee_name }} - Badge</title>
    <style>
        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            padding: 20px;
            background: #f0f0f0;
        }
        .badge {
            width: 300px;
            height: 400px;
            margin: 0 auto;
            background: white;
            border: 2px solid #333;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .photo-area {
            height: 120px;
            background: #ecf0f1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #ddd;
        }
        .content {
            padding: 20px;
            text-align: center;
        }
        .name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .title {
            font-size: 1rem;
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        .company {
            font-size: 1.1rem;
            color: #34495e;
            margin-bottom: 20px;
        }
        .qr-section {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #ddd;
        }
        .qr-code {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
            background: white;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .footer {
            background: #2c3e50;
            color: white;
            padding: 8px;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="badge">
        <div class="header">
            <h1>{{ event_name }}</h1>
            <p style="margin: 5px 0 0 0; font-size: 0.9rem;">{{ event_date }}</p>
        </div>
        
        <div class="photo-area">
            <span style="color: #95a5a6;">Photo</span>
        </div>
        
        <div class="content">
            <div class="name">{{ attendee_name }}</div>
            @if(attendee_position)
            <div class="title">{{ attendee_position }}</div>
            @endif
            @if(attendee_company)
            <div class="company">{{ attendee_company }}</div>
            @endif
        </div>
        
        <div class="qr-section">
            <div class="qr-code">
                <span style="color: #666; font-size: 8px;">QR</span>
            </div>
            <p style="margin: 0; color: #666; font-size: 10px;">
                {{ ticket_id }}
            </p>
        </div>
        
        <div class="footer">
            <p>Conference Badge</p>
        </div>
    </div>
</body>
</html>';
    }
}
