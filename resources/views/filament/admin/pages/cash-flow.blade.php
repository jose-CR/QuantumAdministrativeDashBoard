<x-filament-panels::page>
    <div x-data="{ activeTab: 'salida' }">
        <x-filament::tabs>
            <x-filament::tabs.item alpine-active="activeTab === 'salida'" x-on:click="activeTab = 'salida'">
                Salida
            </x-filament::tabs.item>
            <x-filament::tabs.item alpine-active="activeTab === 'entrada'" x-on:click="activeTab = 'entrada'">
                Entrada
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div x-show="activeTab === 'salida'" class="mt-4">
            <livewire:outflows-table />
        </div>
        <div x-show="activeTab === 'entrada'" class="mt-4">
            <livewire:inflows-table />
        </div>
        <div class="mt-6">
            <button
                type="button"
                class="px-4 py-2 bg-primary-600 text-white rounded-lg"
            >
                Descargar
            </button>
        </div>
    </div>
</x-filament-panels::page>