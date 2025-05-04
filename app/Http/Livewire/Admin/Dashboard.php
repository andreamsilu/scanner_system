<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use App\Models\Attendee;
use App\Models\ScanLog;

class Dashboard extends Component
{
    public $totalAttendees;
    public $scannedAttendees;
    public $unscannedAttendees;
    public $recentScans;

    public function mount()
    {
        $this->loadStatistics();
    }

    public function loadStatistics()
    {
        $this->totalAttendees = Attendee::count();
        $this->scannedAttendees = Attendee::where('scanned', true)->count();
        $this->unscannedAttendees = $this->totalAttendees - $this->scannedAttendees;
        $this->recentScans = ScanLog::with('attendee')
            ->latest()
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.dashboard')
            ->layout('layouts.app');
    }
} 