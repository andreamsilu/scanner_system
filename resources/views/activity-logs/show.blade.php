<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Activity Log Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Activity Information</h3>
                            <dl class="grid grid-cols-1 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $activityLog->description }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">User</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $activityLog->causer?->name ?? 'System' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $activityLog->created_at->format('M d, Y H:i:s') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Log Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $activityLog->log_name ?? 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Details</h3>
                            <dl class="grid grid-cols-1 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Subject Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $activityLog->subject_type ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Subject ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $activityLog->subject_id ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Properties</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <pre class="bg-gray-50 p-4 rounded-lg overflow-x-auto">{{ json_encode($activityLog->properties, JSON_PRETTY_PRINT) }}</pre>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-6">
                        <a href="{{ route('activity-logs.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                            Back to Activity Logs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 