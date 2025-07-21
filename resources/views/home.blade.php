<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Google Calendar Integration') }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="text-center py-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Welcome to Google Calendar Integration</h1>
                <p class="text-gray-600 mb-8">Browse upcoming events and join the ones you're interested in.</p>
                
                <div class="flex justify-center space-x-4">
                    <a href="{{ route('guest.index') }}" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        View Events
                    </a>
                    @if(file_exists(storage_path('app/google-calendar/oauth-token.json')))
                    <a href="{{ route('calendar.index') }}" class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Admin Dashboard
                    </a>
                    @else
                    <a href="{{ route('google.redirect') }}" class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Admin Login
                    </a>
                    @endif
                </div>
            </div>
            
            <!-- Display upcoming events -->
            @php
                try {
                    $events = \Spatie\GoogleCalendar\Event::get();
                    // Process events to ensure date properties are not null
                    foreach ($events as $event) {
                        if (!$event->startDateTime) {
                            $event->startDateTime = \Carbon\Carbon::now();
                        }
                        if (!$event->endDateTime) {
                            $event->endDateTime = \Carbon\Carbon::now()->addHour();
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error fetching events: ' . $e->getMessage());
                    $events = collect([]);
                }
            @endphp
            
            <div class="mt-8 border-t border-gray-200 pt-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Upcoming Events</h2>
                
                @if(count($events) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($events->take(6) as $event)
                    <div class="bg-gray-50 p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $event->name }}</h3>
                        <p class="text-gray-600 mb-4 line-clamp-2">{{ $event->description }}</p>
                        <div class="text-sm text-gray-500 mb-4">
                            <div class="mb-1"><span class="font-medium">Start:</span> {{ $event->startDateTime->format('M d, Y g:i A') }}</div>
                            <div><span class="font-medium">End:</span> {{ $event->endDateTime->format('M d, Y g:i A') }}</div>
                        </div>
                        <a href="{{ route('guest.show', $event->id) }}" class="inline-block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors duration-200">
                            View Details
                        </a>
                    </div>
                    @endforeach
                </div>
                <div class="text-center mt-6">
                    <a href="{{ route('guest.index') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">View All Events →</a>
                </div>
                @else
                <div class="text-center py-8">
                    <p class="text-gray-500">No upcoming events found.</p>
                </div>
                @endif
            </div>

            <div class="mt-12 border-t border-gray-200 pt-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Features</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                        <div class="text-indigo-600 text-xl mb-2">📅 View Events</div>
                        <p class="text-gray-600">View all your Google Calendar events in one place with a clean and intuitive interface.</p>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                        <div class="text-indigo-600 text-xl mb-2">➕ Create Events</div>
                        <p class="text-gray-600">Easily create new events with detailed information and have them synced to your Google Calendar.</p>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                        <div class="text-indigo-600 text-xl mb-2">✏️ Edit & Delete</div>
                        <p class="text-gray-600">Update or remove existing events with just a few clicks, keeping your calendar up to date.</p>
                    </div>
                </div>
            </div>

            <div class="mt-12 border-t border-gray-200 pt-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Getting Started</h2>
                <ol class="list-decimal list-inside space-y-2 text-gray-600 pl-4">
                    <li>Click on "View Calendar" to see your existing events</li>
                    <li>Use "Create New Event" to add events to your calendar</li>
                    <li>Edit or delete events from the calendar view</li>
                </ol>
            </div>
        </div>
    </div>
</x-app-layout>