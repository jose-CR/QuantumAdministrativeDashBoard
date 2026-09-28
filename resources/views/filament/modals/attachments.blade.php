<div class="grid grid-cols-3 gap-3">
    @forelse ($attachments as $attachment)
        <a href="{{ $attachment->url }}" target="_blank">
            @if ($attachment->is_image)
                <img src="{{ $attachment->url }}" class="w-full h-32 object-cover rounded" />
            @else
                <div class="w-full h-32 rounded bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <x-heroicon-o-document class="h-8 w-8 text-gray-500" />
                </div>
            @endif
        </a>
    @empty
        <p class="text-sm text-gray-400">Sin adjuntos</p>
    @endforelse
</div>