<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Track Your Package') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <x-validation-errors class="mb-4" />

                <form method="POST" action="{{ route('tracking.track') }}">
                    @csrf
                    <div class="p-6">
                        <div class="mb-4">
                            <x-label for="tracking_number" value="{{ __('Tracking Number') }}" />
                            <x-input id="tracking_number" name="tracking_number" class="block mt-1 w-full" type="text" required autofocus />
                        </div>
                        
                        <div class="mb-4">
                            <x-label for="reference_id" value="{{ __('Reference ID') }}" />
                            <x-input id="reference_id" name="reference_id" class="block mt-1 w-full" type="text" />
                        </div>

                        <div class="mb-4">
                            <x-label for="reference_name" value="{{ __('Reference Name') }}" />
                            <x-input id="reference_name" name="reference_name" class="block mt-1 w-full" type="text" />
                        </div>
                    </div>
                    <div class="p-6">
                        <x-button>
                            {{ __('Track') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
