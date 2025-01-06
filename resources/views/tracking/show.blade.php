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
                    <x-section-title>
                        <x-slot name="title">Shipment Information</x-slot>
                        <x-slot name="description">Detailed tracking information for this shipment.</x-slot>
                    </x-section-title>

                    <div class="mt-5 md:mt-0 md:col-span-2">
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
                                <div class="flow-root">
                                    <ul role="list" class="-mb-8">
                                        @foreach($tracker->trackerData->data['trackResponse']['shipment'][0]['package'][0]['activity'] as $activity)
                                            <li>
                                                <div class="relative pb-8">
                                                    @if(!$loop->last)
                                                        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                                    @endif
                                                    <div class="relative flex space-x-3">
                                                        <div>
                                                            <span class="h-8 w-8 rounded-full bg-gray-400 flex items-center justify-center ring-8 ring-white">
                                                                <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="M10 2a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 100-12 6 6 0 000 12z" clip-rule="evenodd" />
                                                                </svg>
                                                            </span>
                                                        </div>
                                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                            <div>
                                                                <p class="text-sm text-gray-500">{{ $activity['status']['description'] ?? 'N/A' }}</p>
                                                                <p class="text-sm text-gray-500">
                                                                    {{ isset($activity['location']) ? implode(', ', array_filter([
                                                                        $activity['location']['address']['city'] ?? null,
                                                                        $activity['location']['address']['stateProvince'] ?? null,
                                                                        $activity['location']['address']['countryCode'] ?? null
                                                                    ])) : 'N/A' }}
                                                                </p>
                                                            </div>
                                                            <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                                                @if(isset($activity['date'], $activity['time']))
                                                                    {{ \Carbon\Carbon::createFromFormat('Ymd His', $activity['date'] . ' ' . $activity['time'])->format('M j, Y g:ia') }}
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
