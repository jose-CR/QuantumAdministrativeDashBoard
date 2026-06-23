<?php

namespace App\Filament\Admin\Resources\Credits\Installments;

use App\Filament\Admin\Resources\Credits\Installments\Pages\CreateInstallments;
use App\Filament\Admin\Resources\Credits\Installments\Pages\EditInstallments;
use App\Filament\Admin\Resources\Credits\Installments\Pages\ListInstallments;
use App\Filament\Admin\Resources\Credits\Installments\Schemas\InstallmentsForm;
use App\Filament\Admin\Resources\Credits\Installments\Tables\InstallmentsTable;
use App\Models\Installment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InstallmentsResource extends Resource
{
    protected static ?string $model = Installment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Creditos';

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?string $slug = 'creditos/installments';

    public static function form(Schema $schema): Schema
    {
        return InstallmentsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstallmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstallments::route('/'),
            //'create' => CreateInstallments::route('/create'),
            'edit' => EditInstallments::route('/{record}/edit'),
        ];
    }
}
