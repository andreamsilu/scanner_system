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
                            <span class="text-gray-500">Available Events</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Header Content -->
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        @if(auth()->user()->hasRole('admin'))
                            {{ __('Available Events') }}
                        @else
                            {{ __('Your Assigned Events') }}
                        @endif
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        @if(auth()->user()->hasRole('admin'))
                            Select any event to start scanning attendees
                        @else
                            Select an event to start scanning attendees
                        @endif
                    </p>
                </div>
                <a href="{{ route('scanner.dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                    <i class="bi bi-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
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
                                <h3 class="text-lg font-medium text-gray-900">
                                    @if(auth()->user()->hasRole('admin'))
                                        All Available Events
                                    @else
                                        Your Assigned Events
                                    @endif
                                </h3>
                                <p class="mt-1 text-sm text-gray-600">
                                    Select an event to start scanning or managing attendees
                                </p>
                            </div>
                            <div class="flex space-x-3">
                                <a href="{{ route('scanner.scan.index') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                    <i class="bi bi-upc-scan mr-2"></i>
                                    Start Scanning
                                </a>
                                <a href="{{ route('scanner.validate.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                                    <i class="bi bi-key-fill mr-2"></i>
                                    Manual Validation
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($events as $event)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <span class="text-xl font-semibold text-indigo-600">
                                            {{ substr($event->name, 0, 1) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-lg font-medium text-gray-900">{{ $event->name }}</h4>
                                    <p class="text-sm text-gray-500">
                                        Assigned {{ $event->pivot->assigned_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="bi bi-calendar-event mr-2"></i>
                                    {{ $event->start_date->format('M d, Y') }} - {{ $event->end_date->format('M d, Y') }}
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <i class="bi bi-geo-alt mr-2"></i>
                                    {{ $event->location }}
                                </div>

                                <div class="grid grid-cols-3 gap-2 mt-4">
                                    <div class="bg-gray-50 rounded-lg p-3 text-center">
                                        <div class="text-sm text-gray-500">Total</div>
                                        <div class="text-lg font-semibold text-gray-900">{{ $event->scan_stats['total_attendees'] }}</div>
                                    </div>
                                    <div class="bg-green-50 rounded-lg p-3 text-center">
                                        <div class="text-sm text-green-500">Scanned</div>
                                        <div class="text-lg font-semibold text-green-700">{{ $event->scan_stats['scanned_count'] }}</div>
                                    </div>
                                    <div class="bg-yellow-50 rounded-lg p-3 text-center">
                                        <div class="text-sm text-yellow-500">Pending</div>
                                        <div class="text-lg font-semibold text-yellow-700">{{ $event->scan_stats['pending_count'] }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between mt-4 pt-4 border-t">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        @if($event->status === 'active') bg-green-100 text-green-800
                                        @elseif($event->status === 'completed') bg-blue-100 text-blue-800
                                        @elseif($event->status === 'cancelled') bg-red-100 text-red-800
                                        @else bg-yellow-100 text-yellow-800 @endif">
                                        {{ ucfirst($event->status) }}
                                    </span>
                                    <div class="flex space-x-2">
                                        <a href="{{ route('events.show', $event) }}" 
                                           class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                            <i class="bi bi-eye mr-1"></i>
                                            View
                                        </a>
                                        <a href="{{ route('scanner.events.select', ['event' => $event->id]) }}" 
                                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-indigo-600 hover:bg-indigo-700">
                                            <i class="bi bi-upc-scan mr-1"></i>
                                            Scan
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-8 text-center">
                                <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                    <i class="bi bi-calendar-x text-2xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">
                                    @if(auth()->user()->hasRole('admin'))
                                        No Active Events
                                    @else
                                        No Assigned Events
                                    @endif
                                </h3>
                                <p class="text-gray-500">
                                    @if(auth()->user()->hasRole('admin'))
                                        There are no active events available at the moment.
                                    @else
                                        You have not been assigned to any events yet. Please contact your administrator.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($events->hasPages())
                <div class="mt-6">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout> 