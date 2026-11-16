<?php

namespace App\Filament\Admin\Resources\Administration\Employees\Pages;

use App\Filament\Admin\Resources\Administration\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
}
