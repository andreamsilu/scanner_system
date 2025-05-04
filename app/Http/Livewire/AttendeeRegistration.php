<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Attendee;

class AttendeeRegistration extends Component
{
    public $name;
    public $email;
    public $phone;
    public $success = false;
    public $attendee;

    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email|unique:attendees,email',
        'phone' => 'nullable|string',
    ];

    public function submit()
    {
        $this->validate();

        $attendee = Attendee::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'qr_token' => (new Attendee)->generateQrToken(),
        ]);

        $this->attendee = $attendee;
        $this->success = true;
        
        $this->reset(['name', 'email', 'phone']);

        // Redirect to event card page
        return $this->redirect(route('event-card', $attendee), navigate: true);
    }

    public function render()
    {
        return view('livewire.attendee-registration');
    }
} 