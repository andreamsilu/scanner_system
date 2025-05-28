<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col space-y-2">
            <!-- Breadcrumbs -->
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('scanner.dashboard') }}" class="text-gray-700 hover:text-indigo-600">
                            <i class="bi bi-house-door mr-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="bi bi-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-gray-500">Manual Token Validation</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Header Content -->
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ __('Manual Token Validation') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Enter attendee tokens manually to validate their tickets
                    </p>
                </div>
                @if(session('selected_event_id'))
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600">
                            Selected Event: {{ $event->name }}
                        </span>
                        <form action="{{ route('scanner.events.deselect', $event) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                Deselect Event
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Validate Token</h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Enter the attendee's token to validate their ticket
                                </p>
                            </div>
                            @if(!session('selected_event_id'))
                                <a href="{{ route('scanner.events.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    <i class="bi bi-calendar-event mr-2"></i>
                                    Select Event
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Token Validation Form -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form id="validateForm" class="space-y-4">
                            <div>
                                <label for="token" class="block text-sm font-medium text-gray-700">Token</label>
                                <div class="mt-1">
                                    <input type="text" name="token" id="token" 
                                           class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                                           placeholder="Enter attendee token"
                                           required>
                                </div>
                            </div>
                            <div>
                                <button type="submit" 
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="bi bi-check-circle mr-2"></i>
                                    Validate Token
                                </button>
                            </div>
                        </form>
                        <div id="validationResult" class="mt-4 hidden">
                            <!-- Validation result will be shown here -->
                        </div>
                    </div>
                </div>

                <!-- Recent Validations -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Recent Validations</h3>
                            <a href="{{ route('scanner.history') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                                View All History
                            </a>
                        </div>
                        <div class="space-y-4">
                            @forelse($recent_validations as $validation)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $validation['attendee'] }}</p>
                                        <p class="text-xs text-gray-400">{{ $validation['time'] }}</p>
                                        <p class="text-xs text-gray-500">Scanner: {{ $validation['scanner'] }}</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        @if($validation['status_class'] === 'success') bg-green-100 text-green-800
                                        @elseif($validation['status_class'] === 'warning') bg-yellow-100 text-yellow-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ ucfirst($validation['status']) }}
                                    </span>
                                </div>
                            @empty
                                <div class="text-center py-8 bg-gray-50 rounded-lg">
                                    <i class="bi bi-key-fill text-4xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-500">No recent validations</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('validateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const token = document.getElementById('token').value;
            const resultDiv = document.getElementById('validationResult');
            
            // Show loading state
            resultDiv.classList.remove('hidden');
            resultDiv.innerHTML = `
                <div class="rounded-md bg-blue-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-arrow-repeat animate-spin text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Validating...</h3>
                        </div>
                    </div>
                </div>
            `;

            // Send validation request
            fetch('{{ route("scanner.validate.token") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ token: token })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="rounded-md bg-green-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-check-circle text-green-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-green-800">Validation Successful</h3>
                                    <div class="mt-2 text-sm text-green-700">
                                        <p>Attendee: ${data.attendee.name}</p>
                                        <p>Email: ${data.attendee.email}</p>
                                        <p>Validated at: ${new Date(data.attendee.checked_in_at).toLocaleString()}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Play success sound
                    const audio = new Audio('/sounds/success.mp3');
                    audio.play().catch(() => {}); // Ignore if sound fails to play
                } else {
                    resultDiv.innerHTML = `
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-x-circle text-red-400"></i>
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
                    
                    // Play error sound
                    const audio = new Audio('/sounds/error.mp3');
                    audio.play().catch(() => {}); // Ignore if sound fails to play
                }

                // Clear the input
                document.getElementById('token').value = '';
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.innerHTML = `
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="bi bi-x-circle text-red-400"></i>
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
            });
        });
    </script>
    @endpush
</x-app-layout> 