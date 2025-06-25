<x-filament-panels::page>
    <div class="flex-row items-center justify-center">
        <h1 class="text-2xl font-bold mb-4">{{ __('filament-panels::pages/auth/edit-profile.heading') }}</h1>
        <p class="text-gray-600 mb-6">{{ __('filament-panels::pages/auth/edit-profile.subheading') }}</p>
        <x-filament-panels::form id="form" wire:submit="updateProfile">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
