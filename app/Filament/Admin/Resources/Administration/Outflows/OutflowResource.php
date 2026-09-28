<?php

namespace App\Filament\Admin\Resources\Administration\Outflows;

use App\Filament\Admin\Resources\Administration\Outflows\Pages\CreateOutflow;
use App\Filament\Admin\Resources\Administration\Outflows\Pages\EditOutflow;
use App\Filament\Admin\Resources\Administration\Outflows\Pages\ListOutflows;
use App\Filament\Admin\Resources\Administration\Outflows\Schemas\OutflowForm;
use App\Filament\Admin\Resources\Administration\Outflows\Tables\OutflowsTable;
use App\Models\Outflow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OutflowResource extends Resource
{
    protected static ?string $model = Outflow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'invoice_code';

    public static function form(Schema $schema): Schema
    {
        return OutflowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutflowsTable::configure($table);
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
            'index' => ListOutflows::route('/'),
            'create' => CreateOutflow::route('/create'),
            'edit' => EditOutflow::route('/{record}/edit'),
        ];
    }
}
