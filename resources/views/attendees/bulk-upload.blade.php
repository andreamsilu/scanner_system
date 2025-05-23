@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Bulk Upload Attendees for {{ $event->name }}</h1>

            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-2">Instructions</h2>
                <div class="bg-gray-50 p-4 rounded-md">
                    <p class="mb-2">Please prepare a CSV file with the following columns:</p>
                    <ul class="list-disc list-inside mb-4">
                        <li><strong>name</strong> (required) - Full name of the attendee</li>
                        <li><strong>email</strong> (required) - Email address</li>
                        <li><strong>phone</strong> (optional) - Phone number</li>
                        <li><strong>position</strong> (optional) - Job position or title</li>
                    </ul>
                    <p class="text-sm text-gray-600">The first row should contain the column headers exactly as shown above.</p>
                </div>
            </div>

            <form action="{{ route('events.attendees.bulk-upload.store', $event) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-2">CSV File</label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required
                        class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-md file:border-0
                            file:text-sm file:font-semibold
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100">
                    @error('csv_file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-end space-x-4">
                    <a href="{{ route('events.attendees.index', $event) }}" 
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" 
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Upload and Process
                    </button>
                </div>
            </form>

            <div class="mt-8 p-4 bg-yellow-50 rounded-md">
                <h3 class="text-sm font-medium text-yellow-800">Important Notes:</h3>
                <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                    <li>Each attendee will automatically receive a unique token and QR code</li>
                    <li>The process may take some time depending on the number of records</li>
                    <li>You will be notified when the upload is complete</li>
                    <li>Maximum file size is 10MB</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection 