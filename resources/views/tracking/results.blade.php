<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tracking Results') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                @if (isset($trackingInfo['trackResponse']['shipment']))
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Package Details</h3>
                        <table class="table-auto w-full border-collapse border border-gray-200">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border px-4 py-2">Activity</th>
                                    <th class="border px-4 py-2">Location</th>
                                    <th class="border px-4 py-2">Date</th>
                                    <th class="border px-4 py-2">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($trackingInfo['trackResponse']['shipment'][0]['package'][0]['activity'] as $activity)
                                    @php
                                        $formattedDate = isset($activity['date']) ? \Illuminate\Support\Carbon::createFromFormat('Ymd', $activity['date'])->format('F j, Y') : 'N/A';
                                        $formattedTime = isset($activity['time']) ? \Illuminate\Support\Carbon::createFromFormat('His', $activity['time'])->format('h:i A') : 'N/A';
                                    @endphp
                                    <tr>
                                        <td class="border px-4 py-2">{{ $activity['status']['description'] ?? 'N/A' }}</td>
                                        <td class="border px-4 py-2">
                                            {{ $activity['location']['address']['city'] ?? 'N/A' }},
                                            {{ $activity['location']['address']['stateProvince'] ?? 'N/A' }},
                                            {{ $activity['location']['address']['countryCode'] ?? 'N/A' }}
                                        </td>
                                        <td class="border px-4 py-2">{{ $formattedDate }}</td>
                                        <td class="border px-4 py-2">{{ $formattedTime }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-6">
                        <p class="text-red-600">No tracking information found.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
