<div class="max-w-sm mx-auto bg-white rounded-xl shadow-md overflow-hidden">
    <div class="p-6">
        <div class="text-center mb-4">
            <h2 class="text-2xl font-bold text-gray-900">Event Pass</h2>
        </div>
        
        <!-- Attendee Info -->
        <div class="mb-6">
            <p class="text-gray-600"><span class="font-semibold">Name:</span> {{ $attendee->name }}</p>
            <p class="text-gray-600"><span class="font-semibold">Email:</span> {{ $attendee->email }}</p>
            @if($attendee->phone)
                <p class="text-gray-600"><span class="font-semibold">Phone:</span> {{ $attendee->phone }}</p>
            @endif
        </div>

        <!-- QR Code -->
        <div class="flex justify-center mb-6">
            <div class="p-4 bg-white rounded-lg shadow-sm border">
                {!! $qrCode !!}
            </div>
        </div>

        <!-- Download Button -->
        <div class="text-center">
            <button wire:click="downloadCard" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Download Card
            </button>
        </div>

        <!-- ID and Token Info -->
        <div class="mt-4 text-center text-xs text-gray-500">
            <p>ID: {{ $attendee->id }}</p>
            <p>Token: {{ $attendee->qr_token }}</p>
        </div>
    </div>
</div> 