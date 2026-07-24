<?php

namespace App\Filament\Admin\Resources\Administration\Banks;

use App\Filament\Admin\Resources\Administration\Banks\Pages\CreateBank;
use App\Filament\Admin\Resources\Administration\Banks\Pages\EditBank;
use App\Filament\Admin\Resources\Administration\Banks\Pages\ListBanks;
use App\Filament\Admin\Resources\Administration\Banks\Schemas\BankForm;
use App\Filament\Admin\Resources\Administration\Banks\Tables\BanksTable;
use App\Models\Bank;
use App\Support\Filament\HasTranslatedLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BankResource extends Resource
{
    use HasTranslatedLabels;

    protected static function getTranslationKey(): string
    {
        return 'models.banks';
    }

    protected static ?string $model = Bank::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $modelLabel = null;

    public static function form(Schema $schema): Schema
    {
        return BankForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BanksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationGroup(): string {
        return __('models.others.administration');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBanks::route('/'),
            'create' => CreateBank::route('/create'),
            'edit' => EditBank::route('/{record}/edit'),
        ];
    }
}
