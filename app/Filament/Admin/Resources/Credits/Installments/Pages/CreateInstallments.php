<?php

namespace App\Filament\Admin\Resources\Credits\Installments\Pages;

use App\Filament\Admin\Resources\Credits\Installments\InstallmentsResource;
use App\Services\InstallmentGeneratorService;
use Filament\Resources\Pages\CreateRecord;

class CreateInstallments extends CreateRecord
{
    protected static string $resource = InstallmentsResource::class;
}
