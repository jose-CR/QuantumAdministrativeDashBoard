<x-filament-panels::page>

    <div x-data="{ activeTab: 'salida' }">

        <x-filament::tabs>

            <x-filament::tabs.item
                alpine-active="activeTab === 'salida'"
                x-on:click="activeTab = 'salida'"
            >
                Salida
            </x-filament::tabs.item>

            <x-filament::tabs.item
                alpine-active="activeTab === 'entrada'"
                x-on:click="activeTab = 'entrada'"
            >
                Entrada
            </x-filament::tabs.item>

        </x-filament::tabs>


        <div x-show="activeTab === 'salida'" class="mt-4">
            @livewire('outflows-table-widget')
        </div>

        <div x-show="activeTab === 'entrada'" class="mt-4">
            @livewire('inflows-table-widget')
        </div>

</x-filament-panels::page>
