<?php

namespace App\Filament\Admin\Resources\Credits\Installments;

use App\Filament\Admin\Resources\Credits\Installments\Pages\CreateInstallments;
use App\Filament\Admin\Resources\Credits\Installments\Pages\EditInstallments;
use App\Filament\Admin\Resources\Credits\Installments\Pages\ListInstallments;
use App\Filament\Admin\Resources\Credits\Installments\Schemas\InstallmentsForm;
use App\Filament\Admin\Resources\Credits\Installments\Tables\InstallmentsTable;
use App\Models\Installment;
use App\Support\Filament\HasTranslatedLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InstallmentsResource extends Resource
{
    use HasTranslatedLabels;

    protected static function getTranslationKey(): string
    {
        return 'models.credits.installment';
    }

    protected static ?string $model = Installment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $recordTitleAttribute = 'number';

    protected static ?string $slug = 'creditos/installments';

    protected static ?string $modelLabel = null;

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

    /*
    * funciones para la traduccion
    */
    public static function getNavigationGroup(): string
    {
        return __('models.others.credits');
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
