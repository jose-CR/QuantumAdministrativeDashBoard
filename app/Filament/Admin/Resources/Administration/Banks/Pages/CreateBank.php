<?php

namespace App\Filament\Admin\Resources\Administration\Banks\Pages;

use App\Filament\Admin\Resources\Administration\Banks\BankResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBank extends CreateRecord
{
    protected static string $resource = BankResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
