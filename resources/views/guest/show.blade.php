<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Detail Event') }}
            </h2>
            <a href="{{ route('guest.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Kembali ke Event
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

            @if(session('info'))
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4" role="alert">
                    <p>{{ session('info') }}</p>
                </div>
            @endif

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $event->name }}</h1>
                <p class="text-gray-600 mb-4">{{ $event->description }}</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase mb-2">Waktu Mulai</h3>
                        <p class="text-gray-800">{{ $event->startDateTime ? $event->startDateTime->format('F j, Y') : 'N/A' }}</p>
                        <p class="text-gray-600">{{ $event->startDateTime ? $event->startDateTime->format('g:i A') : 'N/A' }}</p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase mb-2">Waktu Selesai</h3>
                        <p class="text-gray-800">{{ $event->endDateTime ? $event->endDateTime->format('F j, Y') : 'N/A' }}</p>
                        <p class="text-gray-600">{{ $event->endDateTime ? $event->endDateTime->format('g:i A') : 'N/A' }}</p>
                    </div>
                </div>

                @if(isset($event->googleEvent) && $event->googleEvent->getAttendees())
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase mb-2">Peserta</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            @foreach($event->googleEvent->getAttendees() as $attendee)
                                <div class="text-gray-800 mb-1">{{ $attendee->getEmail() }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(isset($event->googleEvent) && $event->googleEvent->getHangoutLink())
                    <div class="mb-6">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase mb-2">Google Meet</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <a href="{{ $event->googleEvent->getHangoutLink() }}" target="_blank" class="text-blue-600 hover:underline">Bergabung ke video call</a>
                        </div>
                    </div>
                @endif

                <div class="mt-8">
                    <form action="{{ route('guest.join', $event->id) }}" method="POST" class="max-w-md mx-auto">
                        @csrf
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Alamat Email Anda</label>
                            <input type="email" name="email" id="email" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                placeholder="Masukkan email Anda">
                        </div>
                        <div class="mb-4">
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Pesan (Opsional)</label>
                            <textarea name="message" id="message" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                                placeholder="Tambahkan pesan untuk admin (opsional)"></textarea>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Kirim Permintaan Bergabung
                            </button>
                            <p class="text-gray-500 mt-2 text-sm">Masukkan email untuk bergabung dengan event ini. Admin akan menambahkan Anda setelah persetujuan.</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>