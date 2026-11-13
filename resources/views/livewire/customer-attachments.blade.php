<div style="display:flex;flex-direction:column;gap:1.25rem">

    {{-- Los tres grupos: créditos, cuotas y sueltos --}}
    <x-filament::tabs>
        <x-filament::tabs.item
            wire:click="setTab('credits')"
            :active="$tab === 'credits'"
            icon="heroicon-m-credit-card"
            :badge="$this->counts['credits']"
        >
            Créditos
        </x-filament::tabs.item>

        <x-filament::tabs.item
            wire:click="setTab('installments')"
            :active="$tab === 'installments'"
            icon="heroicon-m-calendar-days"
            :badge="$this->counts['installments']"
        >
            Cuotas
        </x-filament::tabs.item>

        <x-filament::tabs.item
            wire:click="setTab('loose')"
            :active="$tab === 'loose'"
            icon="heroicon-m-document"
            :badge="$this->counts['loose']"
        >
            Sueltos
        </x-filament::tabs.item>
    </x-filament::tabs>

    @forelse ($this->grupos as $grupo)
        <div>
            <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.03em;opacity:.6;margin-bottom:.5rem">
                {{ $grupo['titulo'] }}
            </div>

            <div style="display:grid;gap:.75rem;grid-template-columns:repeat(auto-fill,minmax(250px,1fr))">
                @foreach ($grupo['archivos'] as $archivo)
                    <div style="display:flex;gap:.75rem;align-items:flex-start;border:1px solid rgba(128,128,128,.25);border-radius:.75rem;padding:.75rem">
                        <x-filament::icon
                            :icon="$archivo->is_image ? 'heroicon-o-photo' : 'heroicon-o-document-text'"
                            style="width:1.5rem;height:1.5rem;flex:none;opacity:.6"
                        />

                        <div style="min-width:0;flex:1">
                            <div style="font-weight:600;font-size:.875rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                 title="{{ $archivo->original_name ?? basename($archivo->path) }}">
                                {{ $archivo->original_name ?? basename($archivo->path) }}
                            </div>

                            <div style="font-size:.75rem;opacity:.6">
                                {{ $archivo->formatted_size }} · {{ $archivo->created_at?->format('d/m/Y') }}
                            </div>

                            <div style="display:flex;gap:.5rem;margin-top:.5rem">
                                <x-filament::button
                                    tag="a"
                                    :href="$archivo->url"
                                    target="_blank"
                                    size="xs"
                                    color="gray"
                                    icon="heroicon-m-arrow-down-tray"
                                >
                                    Abrir
                                </x-filament::button>

                                <x-confirm-button
                                    :action="'delete(' . $archivo->id . ')'"
                                    :label="__('ui.attachments.delete')"
                                    :heading="__('ui.confirm.delete_title')"
                                    :description="__('ui.confirm.delete_description')"
                                    :confirm-label="__('ui.confirm.accept')"
                                />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div style="border:1px dashed rgba(128,128,128,.35);border-radius:.75rem;padding:1.5rem;text-align:center;font-size:.875rem;opacity:.7">
            {{ match ($tab) {
                'credits' => 'Todavía no hay adjuntos en los créditos de este cliente.',
                'installments' => 'Todavía no hay comprobantes en las cuotas.',
                default => 'No hay facturas ni imágenes sueltas.',
            } }}
        </div>
    @endforelse


</div>
