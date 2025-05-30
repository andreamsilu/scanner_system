@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">{{ $event->name }}</h1>
            <p class="text-gray-600">{{ $event->date->format('F j, Y g:i A') }} at {{ $event->location }}</p>
        </div>
        <div class="flex space-x-4">
            <button onclick="openSendSmsModal()" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Send SMS
            </button>
        </div>
    </div>

    <!-- SMS Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        @foreach($smsStats as $stat)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                {{ ucfirst(str_replace('_', ' ', $stat->type)) }}
            </h3>
            <p class="text-3xl font-bold text-blue-600">{{ $stat->count }}</p>
        </div>
        @endforeach
    </div>

    <!-- Recent SMS Logs -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Recent SMS Activity</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($recentLogs as $log)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ ucfirst(str_replace('_', ' ', $log->type)) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->to }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $log->status === 'delivered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $log->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->created_at->format('M d, Y g:i A') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Attendees List -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Attendees</h2>
            <div class="relative">
                <input type="text" 
                       id="searchAttendees" 
                       placeholder="Search attendees..." 
                       class="w-64 px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($attendees as $attendee)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" 
                                   name="attendee_ids[]" 
                                   value="{{ $attendee->id }}"
                                   class="attendee-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $attendee->name }}</div>
                            <div class="text-sm text-gray-500">{{ $attendee->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $attendee->phone ?? 'No phone number' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($attendee->tickets->isNotEmpty())
                                <div class="text-sm text-gray-900">{{ $attendee->tickets->first()->ticket_number }}</div>
                                <div class="text-sm text-gray-500">{{ $attendee->tickets->first()->type }}</div>
                            @else
                                <span class="text-sm text-red-600">No ticket</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(!$attendee->phone)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    No Phone
                                </span>
                            @elseif($attendee->tickets->isEmpty())
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    No Ticket
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Ready
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $attendees->links() }}
        </div>
    </div>
</div>

<!-- Send SMS Modal -->
<div id="sendSmsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Send SMS to Selected Attendees</h3>
            <form id="sendSmsForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Template</label>
                    <select name="template_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Message</label>
                    <textarea name="message" 
                              rows="4" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              maxlength="160"></textarea>
                    <p class="mt-1 text-sm text-gray-500">Characters remaining: <span id="charCount">160</span></p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" 
                            onclick="closeSendSmsModal()"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Send SMS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Modal functions
function openSendSmsModal() {
    const selectedCount = document.querySelectorAll('.attendee-checkbox:checked').length;
    if (selectedCount === 0) {
        alert('Please select at least one attendee');
        return;
    }
    document.getElementById('sendSmsModal').classList.remove('hidden');
}

function closeSendSmsModal() {
    document.getElementById('sendSmsModal').classList.add('hidden');
}

// Select all functionality
document.getElementById('selectAll').addEventListener('change', function(e) {
    document.querySelectorAll('.attendee-checkbox').forEach(checkbox => {
        checkbox.checked = e.target.checked;
    });
});

// Character count
document.querySelector('textarea[name="message"]').addEventListener('input', function(e) {
    const remaining = 160 - e.target.value.length;
    document.getElementById('charCount').textContent = remaining;
});

// Search functionality
document.getElementById('searchAttendees').addEventListener('input', function(e) {
    const search = e.target.value;
    window.location.href = `{{ route('sms.management.event', $event) }}?search=${encodeURIComponent(search)}`;
});

// Send SMS form submission
document.getElementById('sendSmsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const selectedAttendees = Array.from(document.querySelectorAll('.attendee-checkbox:checked'))
        .map(checkbox => checkbox.value);
    
    const formData = new FormData(this);
    formData.append('attendee_ids', JSON.stringify(selectedAttendees));
    
    try {
        const response = await fetch(`{{ route('sms.management.send-bulk', $event) }}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                attendee_ids: selectedAttendees,
                template_id: formData.get('template_id'),
                message: formData.get('message')
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            closeSendSmsModal();
            window.location.reload();
        } else {
            alert('Failed to send SMS: ' + result.message);
        }
    } catch (error) {
        alert('An error occurred while sending SMS');
        console.error(error);
    }
});
</script>
@endpush
@endsection 