<div class="space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">

        <div class="flex items-center gap-3">

            <div
                class="flex h-9 w-9 items-center justify-center
                       rounded-lg bg-primary-50 text-primary-600
                       dark:bg-primary-950/30 dark:text-primary-400"
            >
                <x-heroicon-o-paper-clip class="h-5 w-5" />
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    Comprobantes
                </h3>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $this->attachments->count() }}
                    {{ $this->attachments->count() === 1 ? 'archivo adjunto' : 'archivos adjuntos' }}
                </p>
            </div>

        </div>


        {{-- Adjuntar --}}
        <label
            for="attachment-upload"
            class="inline-flex cursor-pointer items-center gap-2
                   rounded-lg bg-primary-600 px-3 py-2
                   text-xs font-semibold text-white
                   shadow-sm transition hover:bg-primary-500"
        >
            <x-heroicon-o-plus class="h-4 w-4" />
            Adjuntar
        </label>

        <input
            id="attachment-upload"
            type="file"
            wire:model="file"
            accept=".pdf,.jpg,.jpeg,.png,.webp"
            class="hidden"
        >

    </div>


    {{-- Archivo seleccionado --}}
    @if ($file)

        <div
            class="rounded-lg border border-primary-200
                   bg-primary-50/60 p-3
                   dark:border-primary-800
                   dark:bg-primary-950/20"
        >

            <div class="flex items-center gap-3">

                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center
                           rounded-lg bg-white text-primary-600 shadow-sm
                           dark:bg-gray-800"
                >
                    <x-heroicon-o-document-plus class="h-5 w-5" />
                </div>

                <div class="min-w-0 flex-1">

                    <p
                        class="truncate text-xs font-semibold
                               text-gray-900 dark:text-white"
                    >
                        {{ $file->getClientOriginalName() }}
                    </p>

                    <p class="mt-0.5 text-[11px] text-gray-500">
                        {{ number_format($file->getSize() / 1024, 1) }} KB
                    </p>

                </div>

                <button
                    type="button"
                    wire:click="{{ $editingAttachmentId ? 'replace' : 'upload' }}"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 rounded-lg
                           bg-primary-600 px-3 py-2
                           text-xs font-semibold text-white
                           hover:bg-primary-500
                           disabled:opacity-50"
                >

                    <x-heroicon-o-arrow-up-tray
                        wire:loading.remove
                        class="h-4 w-4"
                    />

                    <x-heroicon-o-arrow-path
                        wire:loading
                        class="h-4 w-4 animate-spin"
                    />

                    <span wire:loading.remove>
                        {{ $editingAttachmentId ? 'Reemplazar' : 'Subir' }}
                    </span>

                    <span wire:loading>
                        Procesando...
                    </span>

                </button>

            </div>

        </div>

    @endif


    {{-- Error --}}
    @error('file')

        <div
            class="flex items-center gap-2 rounded-lg
                   border border-danger-200 bg-danger-50
                   px-3 py-2 text-xs text-danger-700
                   dark:border-danger-800
                   dark:bg-danger-950/20
                   dark:text-danger-400"
        >
            <x-heroicon-o-exclamation-circle class="h-4 w-4 shrink-0" />
            {{ $message }}
        </div>

    @enderror


    {{-- Archivos --}}
    @if ($this->attachments->isNotEmpty())

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">

            @foreach ($this->attachments as $attachment)

                <div
                    wire:key="attachment-{{ $attachment->id }}"
                    class="overflow-hidden rounded-xl
                           border border-gray-200 bg-white
                           shadow-sm transition-shadow
                           hover:shadow-md
                           dark:border-gray-700 dark:bg-gray-800"
                >

                    {{-- Preview --}}
                    <a
                        href="{{ $attachment->url }}"
                        target="_blank"
                        class="block"
                    >

                        @if ($attachment->is_image)

                            <div
                                class="flex h-[50px] items-center justify-center
                                       overflow-hidden bg-gray-50
                                       dark:bg-gray-900"
                            >

                                <img
                                    src="{{ $attachment->url }}"
                                    alt="{{ $attachment->original_name }}"
                                    class="h-full w-full object-contain"
                                >

                            </div>

                        @else

                            <div
                                class="flex h-[50px] items-center justify-center
                                       gap-2 bg-gray-50
                                       dark:bg-gray-900"
                            >

                                <x-heroicon-o-document-text
                                    class="h-5 w-5 text-gray-400"
                                />

                                <span
                                    class="text-[10px] font-semibold uppercase
                                           tracking-wide text-gray-400"
                                >
                                    {{ $attachment->extension ?: 'FILE' }}
                                </span>

                            </div>

                        @endif

                    </a>


                    {{-- Información --}}
                    <div class="p-3">

                        <div class="flex items-center gap-3">

                            {{-- Icono --}}
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center
                                       rounded-lg bg-gray-100
                                       dark:bg-gray-700"
                            >

                                @if ($attachment->is_image)

                                    <x-heroicon-o-photo
                                        class="h-4 w-4 text-gray-500
                                               dark:text-gray-300"
                                    />

                                @else

                                    <x-heroicon-o-document
                                        class="h-4 w-4 text-gray-500
                                               dark:text-gray-300"
                                    />

                                @endif

                            </div>


                            {{-- Nombre y tamaño --}}
                            <div class="min-w-0 flex-1">

                                <p
                                    class="truncate text-xs font-semibold
                                           text-gray-900 dark:text-white"
                                    title="{{ $attachment->original_name }}"
                                >
                                    {{ $attachment->original_name }}
                                </p>

                                <p class="mt-0.5 text-[11px] text-gray-400">
                                    {{ strtoupper($attachment->extension ?: 'FILE') }}
                                    ·
                                    {{ $attachment->formatted_size }}
                                </p>

                            </div>

                        </div>


                        {{-- Acciones compactas --}}
                        <div
                            class="mt-2 flex items-center justify-end gap-1
                                   border-t border-gray-100 pt-2
                                   dark:border-gray-700"
                        >

                            {{-- Ver --}}
                            <a
                                href="{{ $attachment->url }}"
                                target="_blank"
                                title="Ver"
                                class="inline-flex h-7 w-7 items-center justify-center
                                       rounded-md text-gray-500
                                       transition hover:bg-gray-100
                                       hover:text-gray-700
                                       dark:hover:bg-gray-700"
                            >
                                <x-heroicon-o-eye class="h-4 w-4" />
                            </a>


                            {{-- Descargar --}}
                            <a
                                href="{{ $attachment->url }}"
                                download
                                title="Descargar"
                                class="inline-flex h-7 w-7 items-center justify-center
                                       rounded-md text-primary-600
                                       transition hover:bg-primary-50
                                       dark:hover:bg-primary-950/30"
                            >
                                <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                            </a>


                            {{-- Reemplazar --}}
                            <button
                                type="button"
                                wire:click="startReplace({{ $attachment->id }})"
                                title="Reemplazar"
                                class="inline-flex h-7 w-7 items-center justify-center
                                       rounded-md text-gray-500
                                       transition hover:bg-gray-100
                                       hover:text-gray-700
                                       dark:hover:bg-gray-700"
                            >
                                <x-heroicon-o-arrow-path class="h-4 w-4" />
                            </button>


                            {{-- Eliminar --}}
                            <button
                                type="button"
                                wire:click="delete({{ $attachment->id }})"
                                wire:confirm="¿Estás seguro de eliminar este comprobante?"
                                title="Eliminar"
                                class="inline-flex h-7 w-7 items-center justify-center
                                       rounded-md text-danger-500
                                       transition hover:bg-danger-50
                                       dark:hover:bg-danger-950/30"
                            >
                                <x-heroicon-o-trash class="h-4 w-4" />
                            </button>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    @else

        {{-- Estado vacío --}}
        <div
            class="rounded-xl border border-dashed
                   border-gray-300 px-6 py-8 text-center
                   dark:border-gray-700"
        >

            <div
                class="mx-auto flex h-10 w-10 items-center justify-center
                       rounded-lg bg-gray-100
                       dark:bg-gray-800"
            >
                <x-heroicon-o-paper-clip
                    class="h-5 w-5 text-gray-400"
                />
            </div>

            <p class="mt-3 text-sm font-semibold
                      text-gray-900 dark:text-white"
            >
                No hay comprobantes
            </p>

            <p class="mt-1 text-xs text-gray-500">
                Adjunta una factura, boucher o comprobante.
            </p>

        </div>

    @endif

</div>
