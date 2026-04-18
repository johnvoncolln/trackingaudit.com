<div>
    {{-- Row 1: Summary Stat Cards --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('tracking.index') }}" class="block bg-white overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow duration-200">
            <div class="p-6">
                <div class="text-sm font-medium text-gray-500">Total Shipments</div>
                <div class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($totalShipments) }}</div>
                <div class="mt-1 text-xs text-gray-400">Past {{ $this->days }} days</div>
            </div>
        </a>

        <a href="{{ route('tracking.index', ['filter' => 'delivered']) }}" class="block bg-white overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow duration-200">
            <div class="p-6">
                <div class="text-sm font-medium text-gray-500">Delivered</div>
                <div class="mt-1 text-3xl font-semibold text-green-600">{{ number_format($deliveredCount) }}</div>
                <div class="mt-1 text-xs text-gray-400">Past {{ $this->days }} days</div>
            </div>
        </a>

        <a href="{{ route('tracking.index', ['filter' => 'en_route']) }}" class="block bg-white overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow duration-200">
            <div class="p-6">
                <div class="text-sm font-medium text-gray-500">En Route</div>
                <div class="mt-1 text-3xl font-semibold text-indigo-600">{{ number_format($enRouteCount) }}</div>
                <div class="mt-1 text-xs text-gray-400">Past {{ $this->days }} days</div>
            </div>
        </a>

        <a href="{{ route('tracking.index', ['filter' => 'late']) }}" class="block bg-white overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow duration-200">
            <div class="p-6">
                <div class="text-sm font-medium text-gray-500">Late</div>
                <div class="mt-1 text-3xl font-semibold {{ $lateCount > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($lateCount) }}</div>
                <div class="mt-1 text-xs text-gray-400">Past expected delivery</div>
            </div>
        </a>
    </div>

    {{-- Row 2: Additional Metrics --}}
    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
        <a href="{{ route('tracking.index', ['filter' => 'needs_attention']) }}" class="block bg-white overflow-hidden shadow-xl sm:rounded-lg hover:shadow-2xl transition-shadow duration-200">
            <div class="p-6">
                <div class="text-sm font-medium text-gray-500">Needs Attention</div>
                <div class="mt-1 text-3xl font-semibold {{ $needsAttentionCount > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ number_format($needsAttentionCount) }}</div>
                <div class="mt-1 text-xs text-gray-400">Failures, returns, errors</div>
            </div>
        </a>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
                <div class="text-sm font-medium text-gray-500">On-Time Delivery Rate</div>
                <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $onTimeRate !== null ? $onTimeRate . '%' : 'N/A' }}</div>
                <div class="mt-1 text-xs text-gray-400">Delivered on or before expected date</div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
                <div class="text-sm font-medium text-gray-500">Avg Time to Delivery</div>
                <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $avgDeliveryDays !== null ? $avgDeliveryDays . ' days' : 'N/A' }}</div>
                <div class="mt-1 text-xs text-gray-400">From shipment creation to delivery</div>
            </div>
        </div>
    </div>

    {{-- Row 3: Carrier Breakdown --}}
    <div class="mt-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Shipments by Carrier</h3>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            @foreach ($carriers as $carrier)
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ $carrier->value }}</div>
                        <div class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($carrierBreakdown[$carrier->value] ?? 0) }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Row 4: Recent Shipments --}}
    <div class="mt-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Shipments</h3>
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            @if ($recentShipments->isEmpty())
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No shipments yet</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by tracking a new package.</p>
                    <div class="mt-6">
                        <a href="{{ route('tracking.form') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Track Package
                        </a>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tracking Number</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Carrier</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($recentShipments as $shipment)
                                <tr wire:key="recent-{{ $shipment->id }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('tracking.show', $shipment) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $shipment->tracking_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $shipment->carrier }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ \App\Enums\TrackerStatus::tryFrom($shipment->status ?? '')?->label() ?? $shipment->status }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $shipment->status_time ? $shipment->status_time->format('M j, Y g:ia') : 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
