<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="text-2xl font-bold mb-6">Dashboard Overview</h2>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-blue-100 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-800">Total Attendees</h3>
                        <p class="text-3xl font-bold text-blue-600">{{ $totalAttendees }}</p>
                    </div>
                    
                    <div class="bg-green-100 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-green-800">Scanned Attendees</h3>
                        <p class="text-3xl font-bold text-green-600">{{ $scannedAttendees }}</p>
                    </div>
                    
                    <div class="bg-yellow-100 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-yellow-800">Unscanned Attendees</h3>
                        <p class="text-3xl font-bold text-yellow-600">{{ $unscannedAttendees }}</p>
                    </div>
                </div>

                <!-- Recent Scans Table -->
                <div class="mt-8">
                    <h3 class="text-xl font-semibold mb-4">Recent Scans</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead>
                                <tr>
                                    <th class="py-2 px-4 border-b">Attendee</th>
                                    <th class="py-2 px-4 border-b">Scanned By</th>
                                    <th class="py-2 px-4 border-b">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentScans as $scan)
                                    <tr>
                                        <td class="py-2 px-4 border-b">{{ $scan->attendee->name }}</td>
                                        <td class="py-2 px-4 border-b">{{ $scan->scanned_by }}</td>
                                        <td class="py-2 px-4 border-b">{{ $scan->created_at->format('Y-m-d H:i:s') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 