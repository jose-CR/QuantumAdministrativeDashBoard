<?php

namespace App\Filament\Admin\Resources\Credits\PaymentHistories;

use App\Filament\Admin\Resources\Credits\PaymentHistories\Pages\CreatePaymentHistories;
use App\Filament\Admin\Resources\Credits\PaymentHistories\Pages\EditPaymentHistories;
use App\Filament\Admin\Resources\Credits\PaymentHistories\Pages\ListPaymentHistories;
use App\Filament\Admin\Resources\Credits\PaymentHistories\Schemas\PaymentHistoriesForm;
use App\Filament\Admin\Resources\Credits\PaymentHistories\Tables\PaymentHistoriesTable;
use App\Models\PaymentHistory;
use App\Support\Filament\HasTranslatedLabels;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PaymentHistoriesResource extends Resource
{
    use HasTranslatedLabels;

    protected static function getTranslationKey(): string
    {
        return 'models.credits.payment_histories';
    }

    protected static ?string $model = PaymentHistory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $recordTitleAttribute = 'user_id';

    protected static ?string $slug = 'creditos/history';

    protected static ?string $modelLabel = null;

    public static function form(Schema $schema): Schema
    {
        return PaymentHistoriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentHistoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationGroup(): string {
        return __('models.others.credits');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentHistories::route('/'),
           'create' => CreatePaymentHistories::route('/create'),
            'edit' => EditPaymentHistories::route('/{record}/edit'),
        ];
    }
}
