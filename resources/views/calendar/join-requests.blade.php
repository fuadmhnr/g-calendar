<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Permintaan Bergabung') }}
            </h2>
            <a href="{{ route('calendar.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Kembali ke Kalender
            </a>
        </div>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <!-- Info box about approval process -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Informasi:</strong> Ketika Anda menyetujui permintaan, sistem akan menggunakan kredensial OAuth Anda untuk menambahkan attendee ke Google Calendar dan mengirim undangan email otomatis.
                        </p>
                    </div>
                </div>
            </div>

            @if(count($eventsData) > 0)
                @foreach($eventsData as $eventData)
                    <div class="mb-8 bg-gray-50 rounded-lg p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">{{ $eventData['event']->name }}</h3>
                            <p class="text-gray-600">{{ $eventData['event']->description }}</p>
                            <p class="text-sm text-gray-500">
                                {{ $eventData['event']->startDateTime ? $eventData['event']->startDateTime->format('F j, Y \a\t g:i A') : 'N/A' }}
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white rounded-lg shadow">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Pesan
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Tanggal Permintaan
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($eventData['requests'] as $request)
                                        <tr class="border-b border-gray-200">
                                            <td class="px-6 py-4 whitespace-no-wrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $request->email }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-600">
                                                    {{ $request->message ?: 'Tidak ada pesan' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-no-wrap">
                                                <div class="text-sm text-gray-600">
                                                    {{ $request->created_at->format('M d, Y H:i') }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-no-wrap text-right text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <form action="{{ route('calendar.approve-join-request', $request) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" 
                                                            class="inline-flex items-center px-3 py-1 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                                            onclick="return confirm('Setujui permintaan bergabung dari {{ $request->email }}?')">
                                                            Setujui
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('calendar.reject-join-request', $request) }}" method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit"
                                                            class="inline-flex items-center px-3 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                                            onclick="return confirm('Tolak permintaan bergabung dari {{ $request->email }}?')">
                                                            Tolak
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="text-center py-8">
                    <div class="text-gray-500 mb-4">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-600 mb-2">Tidak ada permintaan bergabung yang menunggu</h3>
                    <p class="text-gray-500">Semua permintaan bergabung telah diproses atau belum ada permintaan baru.</p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout> 