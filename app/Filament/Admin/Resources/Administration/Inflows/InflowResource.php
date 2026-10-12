<?php

namespace App\Filament\Admin\Resources\Administration\Inflows;

use App\Filament\Admin\Resources\Administration\Inflows\Pages\CreateInflow;
use App\Filament\Admin\Resources\Administration\Inflows\Pages\EditInflow;
use App\Filament\Admin\Resources\Administration\Inflows\Pages\ListInflows;
use App\Filament\Admin\Resources\Administration\Inflows\Schemas\InflowForm;
use App\Filament\Admin\Resources\Administration\Inflows\Tables\InflowsTable;
use App\Models\Inflow;
use App\Support\Filament\HasTranslatedLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InflowResource extends Resource
{
    use HasTranslatedLabels;

    protected static function getTranslationKey(): string
    {
        return 'models.flow.inflow';
    }

    protected static ?string $model = Inflow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUp;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    protected static ?string $modelLabel = null;

    public static function form(Schema $schema): Schema
    {
        return InflowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InflowsTable::configure($table);
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
            'index' => ListInflows::route('/'),
            'create' => CreateInflow::route('/create'),
            'edit' => EditInflow::route('/{record}/edit'),
        ];
    }
}
