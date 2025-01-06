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
                        <x-label for="tracking_number" value="{{ __('Tracking Number') }}" />
                        <x-input id="tracking_number" name="tracking_number" class="block mt-1 w-full" type="text" required autofocus />
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
