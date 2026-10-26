<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\WhatsAppInvitation;
use App\Services\WhatsAppService;
use App\Jobs\QueueWhatsAppInvitations;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class TicketBrowser extends Component
{
    use WithPagination;

    #[Locked]
    public Event $event;

    #[Locked]
    public ?int $selectedGuestId = null;

    public string $search = '';
    public string $status = '';
    public string $type = '';
    public string $sort = 'asc';
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public string $position = '';
    public bool $createTicket = true;
    public string $ticketType = 'single';
    public string $notice = '';
    public bool $whatsappConsent = false;

    public function boot(): void
    {
        abort_unless(auth()->user()?->is_active && auth()->user()->isAdmin(), 403);
    }

    public function mount(Event $event): void
    {
        $this->event = $event;
        if (request()->boolean('add')) {
            $this->newGuest();
        }
    }

    public function updated($property): void
    {
        if ($property === 'phone') {
            $this->whatsappConsent = false;
        }
        if (in_array($property, ['search', 'status', 'type', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'type', 'sort');
        $this->resetPage();
    }

    public function newGuest(): void
    {
        $this->resetValidation();
        $this->reset('selectedGuestId', 'name', 'phone', 'email', 'position', 'createTicket', 'ticketType', 'whatsappConsent');
        Flux::modal('guest-details')->show();
    }

    public function editGuest(int $id, bool $issueTicket = false): void
    {
        $guest = $this->event->attendees()->findOrFail($id);
        $this->resetValidation();
        $this->selectedGuestId = $guest->id;
        $this->name = $guest->name;
        $this->phone = $guest->phone ?? '';
        $this->email = $guest->email ?? '';
        $this->position = $guest->position ?? '';
        $this->createTicket = $issueTicket && ! $guest->ticket()->exists();
        $this->ticketType = 'single';
        $this->whatsappConsent = (bool) $guest->whatsapp_opted_in_at;
        Flux::modal('guest-details')->show();
    }

    public function saveGuest(): void
    {
        $this->name = trim($this->name);
        $this->phone = trim($this->phone);
        $this->email = strtolower(trim($this->email));
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('attendees', 'email')
                ->where('event_id', $this->event->id)->whereNull('deleted_at')->ignore($this->selectedGuestId)],
            'position' => 'nullable|string|max:255',
            'createTicket' => 'boolean',
            'ticketType' => 'required|in:single,double,regular,vip',
            'whatsappConsent' => 'boolean',
        ]);

        $wasNew = $this->selectedGuestId === null;
        $savedGuestId = DB::transaction(function () use ($validated) {
            $event = Event::whereKey($this->event->id)->lockForUpdate()->firstOrFail();
            if ($validated['email'] && $event->attendees()->whereRaw('LOWER(email) = ?', [$validated['email']])
                ->when($this->selectedGuestId, fn ($query) => $query->where('id', '!=', $this->selectedGuestId))->exists()) {
                throw ValidationException::withMessages(['email' => 'A guest with this email is already registered for this event.']);
            }
            if (! $this->selectedGuestId && $event->attendees()->count() >= $event->capacity) {
                throw ValidationException::withMessages(['name' => 'This event has reached its guest capacity.']);
            }
            $guest = $this->selectedGuestId
                ? $event->attendees()->lockForUpdate()->findOrFail($this->selectedGuestId)
                : $event->attendees()->make(['registration_status' => 'unchecked_in']);
            $guest->fill([
                'name' => trim($validated['name']), 'phone' => trim($validated['phone']),
                'email' => $validated['email'] ?: null, 'position' => $validated['position'] ?: null,
            ])->save();
            app(WhatsAppService::class)->setConsent($guest, $validated['whatsappConsent'], 'admin:'.auth()->id());

            if ($validated['createTicket'] && ! $guest->ticket()->exists()) {
                if ($guest->registration_status === 'cancelled') {
                    throw ValidationException::withMessages(['createTicket' => 'Cancelled guests cannot receive a new ticket.']);
                }
                Ticket::create([
                    'event_id' => $event->id, 'attendee_id' => $guest->id, 'type' => $validated['ticketType'],
                    'status' => 'active', 'token' => $guest->token,
                ]);
            }
            return $guest->id;
        }, 3);

        $this->selectedGuestId = $savedGuestId;
        $this->notice = $wasNew ? 'Guest added successfully.' : 'Guest details saved.';
        Flux::modal('guest-details')->close();
    }

    public function cancelGuest(int $id): void
    {
        DB::transaction(function () use ($id) {
            Event::whereKey($this->event->id)->lockForUpdate()->firstOrFail();
            $guest = $this->event->attendees()->lockForUpdate()->findOrFail($id);
            $guest->update(['registration_status' => 'cancelled']);
            $guest->tickets()->update(['status' => 'cancelled', 'cancelled_at' => now()]);
        }, 3);
        $this->notice = 'Guest registration and tickets cancelled. Admission history has been kept.';
        Flux::modal('guest-details')->close();
    }

    public function sendWhatsApp(int $guestId): void
    {
        $guest = $this->event->attendees()->with('ticket')->findOrFail($guestId);
        $service = app(WhatsAppService::class);
        if ($service->setupIssues()) {
            $this->addError('whatsapp', 'Finish the WhatsApp Business setup before sending.');
            return;
        }
        if (! $guest->ticket || ! $service->eligible($guest->ticket)) {
            $this->addError('whatsapp', 'Save the guest’s WhatsApp consent and check that their ticket and event are active.');
            return;
        }
        $queued = $service->queue($guest->ticket, auth()->user());
        $this->notice = $queued ? 'WhatsApp invitation queued.' : 'This invitation is already queued or sent. Check its status before sending again.';
    }

    public function sendBulkWhatsApp(): void
    {
        if (app(WhatsAppService::class)->setupIssues()) {
            $this->addError('whatsapp', 'Finish the WhatsApp Business setup before sending.');
            return;
        }
        QueueWhatsAppInvitations::dispatch($this->event->id, auth()->id())->afterCommit();
        $this->notice = 'Bulk WhatsApp invitations scheduled for this event. Only consenting guests with active tickets who have not been invited will be queued.';
    }

    public function render()
    {
        $query = $this->event->attendees()->with(['ticket' => fn ($query) => $query->with('whatsappInvitation')->withMax('scanLogs', 'created_at')]);
        $search = mb_substr(trim($this->search), 0, 200);
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhereHas('ticket', fn (Builder $ticket) => $ticket->where('ticket_number', $search));
            });
        }
        if ($this->status === 'no_ticket') {
            $query->whereDoesntHave('ticket');
        } elseif ($this->status === 'checked_in') {
            $query->where('registration_status', 'checked_in');
        } elseif ($this->status === 'cancelled') {
            $query->where(fn (Builder $query) => $query->where('registration_status', 'cancelled')
                ->orWhereHas('ticket', fn (Builder $ticket) => $ticket->where('status', 'cancelled')));
        } elseif (in_array($this->status, ['active', 'partial', 'used'], true)) {
            $query->where('registration_status', '!=', 'cancelled')->whereHas('ticket', function (Builder $ticket) {
                $ticket->where('status', $this->status === 'used' ? 'used' : 'active');
                if ($this->status === 'partial') {
                    $ticket->where('entries_used', '>', 0);
                }
            });
        }
        if (in_array($this->type, ['single', 'double', 'vip', 'regular'], true)) {
            $query->whereHas('ticket', fn (Builder $ticket) => $ticket->where('type', $this->type));
        }

        $number = Ticket::selectRaw('CAST(ticket_number AS DECIMAL(20, 0))')
            ->whereColumn('attendee_id', 'attendees.id')->where('event_id', $this->event->id)->orderBy('id')->limit(1);
        $guests = $query->withExists('ticket')->orderByDesc('ticket_exists')
            ->orderBy($number, $this->sort === 'desc' ? 'desc' : 'asc')->orderBy('attendees.id')->paginate(15);
        $summary = [
            'guests' => $this->event->attendees()->count(),
            'without_tickets' => $this->event->attendees()->whereDoesntHave('ticket')->count(),
            'tickets' => $this->event->tickets()->count(),
            'admissions' => $this->event->tickets()->sum('entries_used'),
        ];
        $selectedGuest = $this->selectedGuestId ? $this->event->attendees()->with('ticket.whatsappInvitation')->findOrFail($this->selectedGuestId) : null;
        $whatsappIssues = app(WhatsAppService::class)->setupIssues();
        $whatsappStats = WhatsAppInvitation::where('event_id', $this->event->id)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('livewire.ticket-browser', compact('guests', 'summary', 'selectedGuest', 'whatsappIssues', 'whatsappStats'));
    }
}
