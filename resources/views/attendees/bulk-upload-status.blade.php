@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Bulk Upload Status</h1>
                <a href="{{ route('events.attendees.index', $bulkUpload->event) }}" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Back to Attendees
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gray-50 p-4 rounded-md">
                    <h2 class="text-lg font-semibold mb-4">Upload Details</h2>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Event</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $bulkUpload->event->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">File Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $bulkUpload->filename }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Uploaded By</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $bulkUpload->user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Upload Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $bulkUpload->created_at->format('M d, Y H:i:s') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-gray-50 p-4 rounded-md">
                    <h2 class="text-lg font-semibold mb-4">Processing Status</h2>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($bulkUpload->status === 'completed') bg-green-100 text-green-800
                                    @elseif($bulkUpload->status === 'failed') bg-red-100 text-red-800
                                    @elseif($bulkUpload->status === 'processing') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($bulkUpload->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Records</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $bulkUpload->total_records }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Processed</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $bulkUpload->processed_records }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Failed</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $bulkUpload->failed_records }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            @if($bulkUpload->error_message)
                <div class="mb-8">
                    <h2 class="text-lg font-semibold mb-2">Errors</h2>
                    <div class="bg-red-50 p-4 rounded-md">
                        <pre class="text-sm text-red-700 whitespace-pre-wrap">{{ $bulkUpload->error_message }}</pre>
                    </div>
                </div>
            @endif

            @if($bulkUpload->status === 'completed' && $bulkUpload->attendees->isNotEmpty())
                <div>
                    <h2 class="text-lg font-semibold mb-4">Uploaded Attendees</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($bulkUpload->attendees as $attendee)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $attendee->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $attendee->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $attendee->phone ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $attendee->position ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">{{ $attendee->token }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection 