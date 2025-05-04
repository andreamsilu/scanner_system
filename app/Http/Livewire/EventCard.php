<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Attendee;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EventCard extends Component
{
    public Attendee $attendee;
    public $qrCode;

    public function mount(Attendee $attendee)
    {
        $this->attendee = $attendee;
        $this->generateQrCode();
    }

    public function generateQrCode()
    {
        $this->qrCode = QrCode::size(200)
            ->format('svg')
            ->generate($this->attendee->qr_token);
    }

    public function downloadCard()
    {
        // We'll implement PDF download functionality here
        $this->dispatch('download-card', attendeeId: $this->attendee->id);
    }

    public function render()
    {
        return view('livewire.event-card');
    }
} 