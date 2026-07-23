<?php

namespace App\Filament\Admin\Resources\Credits\Loans;

use App\Filament\Admin\Resources\Credits\Loans\Pages\CreateLoans;
use App\Filament\Admin\Resources\Credits\Loans\Pages\EditLoans;
use App\Filament\Admin\Resources\Credits\Loans\Pages\ListLoans;
use App\Filament\Admin\Resources\Credits\Loans\Schemas\LoansForm;
use App\Filament\Admin\Resources\Credits\Loans\Tables\LoansTable;
use App\Models\Credit;
use App\Support\Filament\HasTranslatedLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LoansResource extends Resource
{
    use HasTranslatedLabels;

    protected static function getTranslationKey(): string
    {
        return 'models.credits.loans';
    }

    protected static ?string $model = Credit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $recordTitleAttribute = 'client_id';

    protected static ?string $slug = 'creditos/loans';

    protected static ?string $modelLabel = null;

    public static function form(Schema $schema): Schema
    {
        return LoansForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoansTable::configure($table);
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
            'index' => ListLoans::route('/'),
            'create' => CreateLoans::route('/create'),
            'edit' => EditLoans::route('/{record}/edit'),
        ];
    }
}
