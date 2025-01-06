<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tracking Details') }}
            </h2>
            <a href="{{ route('tracking.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Back to List') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <x-section-title>
                            <x-slot name="title">Shipment Information</x-slot>
                            <x-slot name="description">Detailed tracking information for this shipment.</x-slot>
                        </x-section-title>
                        
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600 mr-4">
                                Last Updated: {{ $tracker->updated_at->format('M j, Y g:ia') }}
                            </span>
                            <form method="POST" action="{{ route('tracking.update', $tracker) }}">
                                @csrf
                                <x-button>
                                    {{ __('Update') }}
                                </x-button>
                            </form>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="px-4 py-5">
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Tracking Number</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $tracker->tracking_number }}</dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Carrier</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $tracker->carrier }}</dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Current Status</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $tracker->status ?? 'N/A' }}</dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ $tracker->status_time ? \Carbon\Carbon::parse($tracker->status_time)->format('M j, Y g:ia') : 'N/A' }}
                                    </dd>
                                </div>
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Current Location</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $tracker->location ?? 'N/A' }}</dd>
                                </div>
                                @if($tracker->delivery_date)
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">Scheduled Delivery</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($tracker->delivery_date)->format('M j, Y') }}
                                    </dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    @if($tracker->trackerData && isset($tracker->trackerData->data['trackResponse']['shipment'][0]['package'][0]['activity']))
                        <x-section-border />

                        <x-section-title>
                            <x-slot name="title">Tracking History</x-slot>
                            <x-slot name="description">Complete history of tracking events.</x-slot>
                        </x-section-title>

                        <div class="mt-5 md:mt-0 md:col-span-2">
                            <div class="px-4 py-5">
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
                                        @foreach($tracker->trackerData->data['trackResponse']['shipment'][0]['package'][0]['activity'] as $activity)
                                            @php
                                                $formattedDate = isset($activity['date']) ? \Carbon\Carbon::createFromFormat('Ymd', $activity['date'])->format('F j, Y') : 'N/A';
                                                $formattedTime = isset($activity['time']) ? \Carbon\Carbon::createFromFormat('His', $activity['time'])->format('h:i A') : 'N/A';
                                            @endphp
                                            <tr>
                                                <td class="border px-4 py-2">{{ $activity['status']['description'] ?? 'N/A' }}</td>
                                                <td class="border px-4 py-2">
                                                    {{ isset($activity['location']) ? implode(', ', array_filter([
                                                        $activity['location']['address']['city'] ?? null,
                                                        $activity['location']['address']['stateProvince'] ?? null,
                                                        $activity['location']['address']['countryCode'] ?? null
                                                    ])) : 'N/A' }}
                                                </td>
                                                <td class="border px-4 py-2">{{ $formattedDate }}</td>
                                                <td class="border px-4 py-2">{{ $formattedTime }}</td>
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
    </div>
</x-app-layout>
