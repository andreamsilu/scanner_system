<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TicketBrowser extends Component
{
    use WithPagination;

    #[Locked]
    public Event $event;

    public string $search = '';
    public string $status = '';
    public string $type = '';
    public string $sort = 'asc';

    public function boot(): void
    {
        // Livewire updates must recheck access after account or role changes.
        abort_unless(auth()->user()?->is_active && auth()->user()->isAdmin(), 403);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'type', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'type', 'sort');
        $this->resetPage();
    }

    public function render()
    {
        $query = Ticket::query()->where('event_id', $this->event->id)
            ->with('attendee')->withMax('scanLogs', 'created_at');
        $search = mb_substr(trim($this->search), 0, 200);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->where('ticket_number', $search)
                    ->orWhereHas('attendee', fn (Builder $attendee) => $attendee
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%'));
            });
        }

        if (in_array($this->status, ['active', 'used', 'cancelled'], true)) {
            $query->where('status', $this->status);
        } elseif ($this->status === 'partial') {
            $query->where('status', 'active')->where('entries_used', '>', 0);
        }

        if (in_array($this->type, ['single', 'double', 'vip', 'regular'], true)) {
            $query->where('type', $this->type);
        }

        $direction = $this->sort === 'desc' ? 'desc' : 'asc';
        $tickets = $query->orderByRaw('CAST(ticket_number AS DECIMAL(20, 0)) '.$direction)->paginate(15);
        $summary = Ticket::where('event_id', $this->event->id)->selectRaw(
            "COUNT(*) as total, COALESCE(SUM(entries_used), 0) as admissions, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active, SUM(CASE WHEN type = 'double' THEN 1 ELSE 0 END) as doubles"
        )->first();

        return view('livewire.ticket-browser', compact('tickets', 'summary'));
    }
}
