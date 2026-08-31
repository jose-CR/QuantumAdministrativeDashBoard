<?php

namespace App\Filament\Admin\Resources\Client\Customers\Pages;

use App\Filament\Admin\Resources\Client\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
