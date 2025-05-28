<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manual Token Validation') }}
            </h2>
            @if(session('selected_event_id'))
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        Selected Event: {{ $events->firstWhere('id', session('selected_event_id'))?->name }}
                    </span>
                    <form action="{{ route('scanner.deselect-event', session('selected_event_id')) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                            Deselect Event
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(!session('selected_event_id'))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Select an Event</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($events as $event)
                                <div class="border rounded-lg p-4 hover:bg-gray-50">
                                    <h4 class="font-medium text-gray-900">{{ $event->name }}</h4>
                                    <p class="text-sm text-gray-600">{{ $event->start_date->format('M d, Y') }}</p>
                                    <div class="mt-2">
                                        <a href="{{ route('scanner.select-event', $event) }}" 
                                           class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Select Event
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Manual Token Validation Form -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Validate Token</h3>
                                <form id="validateForm" class="space-y-4">
                                    @csrf
                                    <div>
                                        <label for="token" class="block text-sm font-medium text-gray-700">Token</label>
                                        <div class="mt-1">
                                            <input type="text" name="token" id="token" 
                                                   class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                                   placeholder="Enter token to validate"
                                                   required>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="submit" 
                                                class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            Validate Token
                                        </button>
                                    </div>
                                </form>
                                <div id="validationResult" class="mt-4 hidden">
                                    <!-- Result will be shown here -->
                                </div>
                            </div>

                            <!-- Recent Validations -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Validations</h3>
                                <div class="space-y-4">
                                    @forelse($recent_validations as $validation)
                                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $validation['attendee'] }}</p>
                                                <p class="text-sm text-gray-500">{{ $validation['email'] }}</p>
                                                <p class="text-xs text-gray-400">{{ $validation['time'] }}</p>
                                            </div>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                @if($validation['status_class'] === 'success') bg-green-100 text-green-800
                                                @elseif($validation['status_class'] === 'warning') bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ ucfirst($validation['status']) }}
                                            </span>
                                        </div>
                                    @empty
                                        <p class="text-sm text-gray-500">No recent validations</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('validateForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const resultDiv = document.getElementById('validationResult');
            const token = form.token.value;

            try {
                const response = await fetch('{{ route("scanner.validate.token") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ token })
                });

                const data = await response.json();
                
                resultDiv.classList.remove('hidden');
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="rounded-md bg-green-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800">Check-in Successful</h3>
                                    <div class="mt-2 text-sm text-green-700">
                                        <p>Attendee: ${data.attendee.name}</p>
                                        <p>Email: ${data.attendee.email}</p>
                                        <p>Checked in at: ${new Date(data.attendee.checked_in_at).toLocaleString()}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    form.reset();
                } else {
                    resultDiv.innerHTML = `
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Validation Failed</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>${data.message}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                resultDiv.innerHTML = `
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>An error occurred while validating the token. Please try again.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        });
    </script>
    @endpush
</x-app-layout> 