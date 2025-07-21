<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Google Calendar Events') }}
            </h2>
            <a href="{{ route('calendar.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Create New Event
            </a>
        </div>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            @if(count($events) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Event
                                </th>
                                <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Start
                                </th>
                                <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    End
                                </th>
                                <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Details
                                </th>
                                <th class="px-6 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($events as $event)
                                <tr>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm leading-5 font-medium text-gray-900">{{ $event->name }}</div>
                                                <div class="text-sm leading-5 text-gray-500">{{ $event->description }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                        <div class="text-sm leading-5 text-gray-900">{{ $event->startDateTime ? $event->startDateTime->format('M d, Y') : 'N/A' }}</div>
                                        <div class="text-sm leading-5 text-gray-500">{{ $event->startDateTime ? $event->startDateTime->format('h:i A') : 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                        <div class="text-sm leading-5 text-gray-900">{{ $event->endDateTime ? $event->endDateTime->format('M d, Y') : 'N/A' }}</div>
                                        <div class="text-sm leading-5 text-gray-500">{{ $event->endDateTime ? $event->endDateTime->format('h:i A') : 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                                        @if(isset($event->googleEvent) && $event->googleEvent->getAttendees())
                                            <div class="mb-2">
                                                <span class="text-xs font-semibold text-gray-600">Attendees:</span>
                                                <div class="text-xs text-gray-500">
                                                    @foreach($event->googleEvent->getAttendees() as $attendee)
                                                        <div>{{ $attendee->getEmail() }}</div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        
                                        @if(isset($event->googleEvent) && $event->googleEvent->getHangoutLink())
                                            <div>
                                                <span class="text-xs font-semibold text-gray-600">Meet:</span>
                                                <a href="{{ $event->googleEvent->getHangoutLink() }}" target="_blank" class="text-xs text-blue-500 hover:underline">Join video call</a>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap text-right border-b border-gray-200 text-sm leading-5 font-medium">
                                        <div class="flex space-x-2">
                                            <a href="{{ route('calendar.edit', $event->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</a>
                                            <form action="{{ route('calendar.destroy', $event->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500 mb-4">No events found in your calendar.</p>
                    <a href="{{ route('calendar.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Create Your First Event
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>