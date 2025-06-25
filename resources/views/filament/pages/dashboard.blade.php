<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filter Form -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            {{ $this->form }}
        </div>

        <!-- Widgets Grid -->
        <div class="space-y-6">
            @foreach ($this->getVisibleHeaderWidgets() as $widget)
                @livewire($widget, ['dateFrom' => $dateFrom, 'dateTo' => $dateTo], key($widget))
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
