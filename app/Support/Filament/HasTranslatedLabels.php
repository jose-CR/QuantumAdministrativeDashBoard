<?php

namespace App\Support\Filament;

trait HasTranslatedLabels
{
    abstract protected static function getTranslationKey(): string;

    public static function getModelLabel(): string
    {
        return __(static::getTranslationKey() . '.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::getTranslationKey() . '.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __(static::getTranslationKey() . '.navigation');
    }
}