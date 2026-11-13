{{--
    Botón con confirmación en un modal del panel.

    Reemplaza al `wire:confirm` del navegador (que sale con el estilo del sistema y
    rompe la estética del panel). El botón que dispara el modal es el `trigger`; al
    confirmar se ejecuta la acción de Livewire y el modal se cierra.

    Uso:
        <x-confirm-button
            :action="'delete(' . $archivo->id . ')'"
            :label="__('ui.attachments.delete')"
            :heading="__('ui.confirm.delete_title')"
            :description="__('ui.confirm.delete_description')"
        />
--}}
@props([
    'action',
    'label' => null,
    'icon' => 'heroicon-m-trash',
    'color' => 'danger',
    'size' => 'xs',
    'heading' => null,
    'description' => null,
    'confirmLabel' => null,
])

@php
    $id = 'confirm-' . \Illuminate\Support\Str::random(10);
@endphp

<x-filament::modal
    :id="$id"
    :heading="$heading ?? __('ui.confirm.title')"
    :description="$description"
    :icon="$color === 'danger' ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-question-mark-circle'"
    :icon-color="$color"
    width="sm"
>
    <x-slot name="trigger">
        <x-filament::button :color="$color" :icon="$icon" :size="$size">
            {{ $label ?? __('ui.confirm.title') }}
        </x-filament::button>
    </x-slot>

    <div style="display:flex;justify-content:flex-end;gap:.75rem">
        <x-filament::button
            color="gray"
            size="sm"
            x-on:click="$dispatch('close-modal', { id: @js($id) })"
        >
            {{ __('ui.confirm.cancel') }}
        </x-filament::button>

        <x-filament::button
            :color="$color"
            size="sm"
            wire:click="{{ $action }}"
            x-on:click="$dispatch('close-modal', { id: @js($id) })"
        >
            {{ $confirmLabel ?? __('ui.confirm.accept') }}
        </x-filament::button>
    </div>
</x-filament::modal>
