<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Track Your Package') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Single Tracking Number Form -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <section>
                            <header>
                                <h2 class="text-lg font-medium text-gray-900">
                                    {{ __('Track Single Package') }}
                                </h2>

                                <p class="mt-1 text-sm text-gray-600">
                                    {{ __('Enter a tracking number to track a single package.') }}
                                </p>
                            </header>

                            <x-validation-errors class="mb-4" />

                            <form method="POST" action="{{ route('tracking.track') }}" class="mt-6 space-y-6">
                    @csrf
                    <div class="p-6">
                        <div class="mb-4">
                            <x-label for="tracking_number" value="{{ __('Tracking Number') }}" />
                            <x-input id="tracking_number" name="tracking_number" class="block mt-1 w-full" type="text" required autofocus />
                        </div>

                        <div class="mb-4">
                            <x-label for="carrier" value="{{ __('Carrier') }}" />
                            <x-select name="carrier" required autofocus>
                                <option value="">Select an option</option>
                                @foreach($carriers as $carrier)
                                <option value="{{ $carrier->value }}">{{ $carrier->name }}</option>
                                @endforeach
                            </x-select>
                        </div>

                        <div class="mb-4">
                            <x-label for="reference_id" value="{{ __('Reference ID') }}" />
                            <x-input id="reference_id" name="reference_id" class="block mt-1 w-full" type="text" />
                        </div>

                        <div class="mb-4">
                            <x-label for="reference_name" value="{{ __('Reference Name') }}" />
                            <x-input id="reference_name" name="reference_name" class="block mt-1 w-full" type="text" />
                        </div>

                        <div class="mb-4">
                            <x-label for="recipient_name" value="{{ __('Recipient Name') }}" />
                            <x-input id="recipient_name" name="recipient_name" class="block mt-1 w-full" type="text" />
                        </div>

                        <div class="mb-4">
                            <x-label for="recipient_email" value="{{ __('Recipient Email') }}" />
                            <x-input id="recipient_email" name="recipient_email" class="block mt-1 w-full" type="text" />
                        </div>
                    </div>
                                <div class="flex items-center gap-4">
                                    <x-button>
                                        {{ __('Track Package') }}
                                    </x-button>
                                </div>
                            </form>
                        </section>
                    </div>
                </div>
            </div>

            <!-- CSV Upload Form -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <section>
                            <header>
                                <div class="flex justify-between items-center">
                                    <h2 class="text-lg font-medium text-gray-900">
                                        {{ __('Bulk Import Tracking Numbers') }}
                                    </h2>
                                    <a href="{{ route('tracking.template') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                                        {{ __('Download Template') }}
                                    </a>
                                </div>

                                <p class="mt-1 text-sm text-gray-600">
                                    {{ __('Upload a CSV file containing up to 500 tracking numbers.') }}
                                </p>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ __('Required columns: tracking_number') }}<br>
                                    {{ __('Optional columns: reference_id, reference_name, reference_data, recipient_name, recipient_email') }}
                                </p>
                            </header>

                            <form method="POST" action="{{ route('tracking.import') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
                                @csrf

                                <div>
                                    <x-label for="csv_file" value="{{ __('CSV File') }}" />
                                    <input type="file"
                                           id="csv_file"
                                           name="csv_file"
                                           accept=".csv"
                                           class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none" />
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ __('Maximum 500 records per upload') }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-4">
                                    <x-button>
                                        {{ __('Upload CSV') }}
                                    </x-button>
                                </div>
                            </form>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
