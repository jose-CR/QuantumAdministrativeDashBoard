<?php

namespace App\Services\Attachments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AttachmentPathResolver
{
    public function directory(Model $record): string
    {
        $base = Str::snake(class_basename($record));
        $segments = $this->segments($record);

        if ($segments !== []) {
            return implode('/', [$base, ...$segments]);
        }

        // Modelo sin niveles definidos: formato {id}-{nombre}
        return $base . '/' . $record->getKey() . '-' . $this->fallbackName($record);
    }

    /** @return string[] */
    protected function segments(Model $record): array
    {
        $values = method_exists($record, 'attachmentFolders')
            ? $record->attachmentFolders()
            : $this->fromConfig($record);

        return collect($values)
            ->map(fn ($value) => $this->slugify($value))
            ->filter()
            ->values()
            ->all();
    }

    protected function fromConfig(Model $record): array
    {
        $levels = config('attachments.folders', [])[$record::class] ?? [];

        return array_map(
            fn ($level) => $this->firstFilled($record, (array) $level),
            $levels,
        );
    }

    protected function fallbackName(Model $record): string
    {
        $attributes = config('attachments.fallback_attributes', ['name', 'title', 'description', 'code']);

        return $this->slugify($this->firstFilled($record, $attributes)) ?: 'record';
    }

    protected function firstFilled(Model $record, array $attributes): ?string
    {
        foreach ($attributes as $attribute) {
            $value = data_get($record, $attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    protected function slugify(?string $value): string
    {
        return rtrim(Str::limit(Str::slug((string) $value), 60, ''), '-');
    }
}