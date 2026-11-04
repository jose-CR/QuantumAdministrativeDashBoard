{{-- Selector de aplicaciones (estilo Odoo): lleva al lanzador desde cualquier pantalla. --}}
<x-filament::icon-button
    :href="\App\Filament\Admin\pages\Apps::getUrl()"
    tag="a"
    color="gray"
    :icon="\Filament\Support\Icons\Heroicon::OutlinedSquares2x2"
    icon-size="lg"
    :label="__('models.others.apps')"
    :tooltip="__('models.others.apps')"
    class="q-app-switcher"
/>
