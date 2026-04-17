<div>
<x-form-section submit="save">
    <x-slot name="title">
        {{ __('Notification Preferences') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Configure email notifications for late shipments and delivery reports.') }}
    </x-slot>

    <x-slot name="form">
        <!-- Late Shipment Notifications -->
        <div class="col-span-6">
            <label class="flex items-center">
                <x-checkbox wire:model="lateShipmentNotificationsEnabled" />
                <span class="ms-2 text-sm text-gray-600">
                    {{ __('Enable late shipment alerts (packages past their expected delivery date)') }}
                </span>
            </label>
        </div>

        <div class="col-span-6 sm:col-span-4" x-show="$wire.lateShipmentNotificationsEnabled" x-transition>
            <x-label for="lateShipmentNotificationsFrequency" value="{{ __('Alert Frequency') }}" />
            <select wire:model="lateShipmentNotificationsFrequency" id="lateShipmentNotificationsFrequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach($frequencies as $freq)
                    <option value="{{ $freq->value }}">{{ $freq->label() }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-sm text-gray-500">
                {{ __('Weekly alerts are sent on Mondays. Monthly alerts are sent on the 1st.') }}
            </p>
        </div>

        <!-- Late Shipment Report -->
        <div class="col-span-6 mt-2">
            <label class="flex items-center">
                <x-checkbox wire:model="lateShipmentReportEnabled" />
                <span class="ms-2 text-sm text-gray-600">
                    {{ __('Enable late delivery reports (UPS and FedEx only)') }}
                </span>
            </label>
            <p class="ms-7 mt-1 text-sm text-gray-500">
                {{ __('Reports include shipments delivered after their expected date. UPS late deliveries may be eligible for refund claims.') }}
            </p>
        </div>

        <div class="col-span-6 sm:col-span-4" x-show="$wire.lateShipmentReportEnabled" x-transition>
            <x-label for="lateShipmentReportFrequency" value="{{ __('Report Frequency') }}" />
            <select wire:model="lateShipmentReportFrequency" id="lateShipmentReportFrequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach($frequencies as $freq)
                    <option value="{{ $freq->value }}">{{ $freq->label() }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-sm text-gray-500">
                {{ __('Weekly reports are sent on Mondays. Monthly reports are sent on the 1st.') }}
            </p>
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            {{ __('Saved.') }}
        </x-action-message>

        <x-button>
            {{ __('Save') }}
        </x-button>
    </x-slot>
</x-form-section>

<x-section-border />

<div class="mt-10 sm:mt-0">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <x-section-title>
            <x-slot name="title">{{ __('API Access') }}</x-slot>
            <x-slot name="description">{{ __('Use your API token to submit tracking numbers programmatically.') }}</x-slot>
        </x-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 bg-white sm:p-6 shadow sm:rounded-md">
                <div class="space-y-4">
                    @if($apiToken)
                        <div>
                            <x-label value="{{ __('Your API Token') }}" />
                            <div class="mt-1 flex items-center gap-3">
                                <code class="block w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-mono text-gray-700">{{ $apiToken }}</code>
                            </div>
                        </div>

                        <div>
                            <x-label value="{{ __('API Endpoint') }}" />
                            <code class="mt-1 block rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-mono text-gray-700 break-all">POST {{ url('/api/v1/tracking/' . $apiToken) }}</code>
                        </div>

                        <div>
                            <x-label value="{{ __('Example Request') }}" />
                            <pre class="mt-1 rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-xs font-mono text-gray-700 overflow-x-auto">curl -X POST {{ url('/api/v1/tracking/' . $apiToken) }} \
  -H "Content-Type: application/json" \
  -d '{"tracking_numbers":[{"tracking_number":"1Z12345E0205271688"}]}'</pre>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-danger-button wire:click="generateApiToken" wire:confirm="{{ __('Are you sure? This will invalidate your current API token.') }}">
                                {{ __('Regenerate Token') }}
                            </x-danger-button>
                        </div>
                    @else
                        <p class="text-sm text-gray-600">
                            {{ __('Generate an API token to start submitting tracking numbers via the API. Carrier is automatically detected from the tracking number format.') }}
                        </p>

                        <x-button wire:click="generateApiToken">
                            {{ __('Generate API Token') }}
                        </x-button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>
