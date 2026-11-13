<?php

namespace App\Filament\Admin\Resources\Administration\Inflows\Schemas;

use App\Models\Article;
use App\Models\Attachment;
use App\Models\Credit;
use App\Models\Customer;
use App\Models\Inflow;
use App\Models\Installment;
use App\Support\Salespeople;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class InflowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos generales')
                    ->description(__('ui.inflow.section_main'))
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label(__('resources.flow.inflow.cod_invoice'))
                            ->required(),

                        Select::make('customer_id')
                            ->label(__('resources.flow.inflow.client'))
                            ->relationship('customer', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('date')
                            ->label(__('resources.flow.inflow.date'))
                            ->required(),

                        // Lista de vendedores ya usados, con opción de crear uno nuevo
                        // desde el mismo select.
                        Select::make('salesperson')
                            ->label(__('resources.flow.inflow.salesperson'))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => Salespeople::search($search))
                            ->getOptionLabelUsing(fn (?string $value): ?string => $value)
                            ->native(false)
                            ->required()
                            ->helperText(__('ui.inflow.salesperson_help'))
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(__('ui.inflow.new_salesperson'))
                                    ->required()
                                    ->minLength(2)
                                    ->maxLength(255),
                            ])
                            ->createOptionModalHeading(__('ui.inflow.new_salesperson'))
                            ->createOptionUsing(fn (array $data): ?string => Salespeople::normalize($data['name'])),

                        Textarea::make('description')
                            ->label(__('resources.flow.inflow.description'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('ui.inflow.section_payment'))
                    ->description(__('ui.inflow.section_payment_help'))
                    ->schema([
                        TextInput::make('amount')
                            ->label(__('resources.flow.inflow.amount'))
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->live()
                            // Por defecto se cobra entero: si todavía no se tocó lo
                            // cobrado, se copia el monto.
                            ->afterStateUpdated(function ($state, callable $set, Get $get): void {
                                if ((float) ($get('amount_paid') ?? 0) <= 0) {
                                    $set('amount_paid', $state);
                                }
                            }),

                        /*
                         * Lo cobrado manda: el estado (pendiente, parcial o pagado)
                         * se calcula solo al guardar.
                         */
                        TextInput::make('amount_paid')
                            ->label(__('resources.flow.inflow.amount_paid'))
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->helperText(__('ui.inflow.amount_paid_help'))
                            ->required(),

                        Select::make('payment_method')
                            ->label(__('resources.flow.inflow.payment_method'))
                            ->options([
                                'cash' => __('resources.credits.clients.pay_installment.payment_methods.cash'),
                                'card' => __('resources.credits.clients.pay_installment.payment_methods.card'),
                                'transfer' => __('resources.credits.clients.pay_installment.payment_methods.transfer'),
                                'cheque' => __('resources.credits.clients.pay_installment.payment_methods.cheque'),
                                'quedan' => __('resources.credits.clients.pay_installment.payment_methods.quedan'),
                            ])
                            ->required()
                            ->live(),

                        TextInput::make('transfer_number')
                            ->label(__('resources.flow.inflow.transfer_number'))
                            ->visible(fn (Get $get): bool => $get('payment_method') !== 'cash'),

                        Select::make('bank_id')
                            ->label(__('resources.clients.fields.bank'))
                            ->relationship('bank', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => $get('payment_method') !== 'cash'),

                        DatePicker::make('transfer_date')
                            ->label(__('resources.flow.inflow.transfer_date'))
                            ->visible(fn (Get $get): bool => $get('payment_method') !== 'cash'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('ui.inflow.items'))
                    ->description(__('ui.inflow.items_help'))
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->hiddenLabel()
                            ->schema([
                                Select::make('article_id')
                                    ->label(__('ui.inflow.item'))
                                    ->searchable()
                                    ->required()
                                    ->options(fn (): array => Article::query()
                                        ->where('tracking', Article::TRACKING_NONE)
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (Article $articulo): array => [
                                            $articulo->id => "{$articulo->full_name} (stock: {$articulo->stock})",
                                        ])
                                        ->all())
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set): void {
                                        if ($articulo = Article::find($state)) {
                                            $set('price', (float) $articulo->cash_price);
                                        }
                                    }),

                                TextInput::make('quantity')
                                    ->label(__('ui.inflow.quantity'))
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required(),

                                TextInput::make('price')
                                    ->label(__('ui.inflow.unit_price'))
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel(__('ui.inflow.add_item'))
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),

                Section::make(__('ui.inflow.section_docs'))
                    ->description(__('ui.inflow.section_docs_help'))
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('resources.flow.inflow.notes'))
                            ->columnSpanFull(),

                        // Reusar lo que el cliente ya tiene cargado (factura de una
                        // cuota, foto del crédito, suelto) sin volver a subirlo.
                        Select::make('attachment_picks')
                            ->label(__('ui.inflow.pick_attachment'))
                            ->multiple()
                            ->searchable()
                            ->options(fn (Get $get): array => static::customerAttachmentOptions($get('customer_id')))
                            ->helperText(__('ui.inflow.pick_attachment_help'))
                            ->visible(fn (Get $get): bool => filled($get('customer_id')))
                            ->columnSpanFull(),

                        FileUpload::make('attachment_uploads')
                            ->label(__('resources.flow.attachment'))
                            ->multiple()
                            ->disk('attachments')
                            ->storeFileNamesIn('file_names')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->maxFiles(10)
                            ->columnSpanFull()
                            ->afterStateHydrated(
                                function (
                                    FileUpload $component,
                                    ?Inflow $record
                                ): void {
                                    if (! $record) {
                                        return;
                                    }

                                    $component->state(
                                        $record->attachments
                                            ->pluck('path')
                                            ->toArray()
                                    );
                                }
                            ),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Adjuntos que ya cuelgan del cliente (suelto, de un crédito o de una cuota),
     * para poder reusarlos en la entrada sin volver a subir el archivo.
     *
     * @return array<int, string>
     */
    protected static function customerAttachmentOptions(mixed $customerId): array
    {
        if (blank($customerId)) {
            return [];
        }

        $credits = Credit::query()->where('customer_id', $customerId)->select('id');
        $installments = Installment::query()->whereIn('credit_id', $credits)->select('id');

        return Attachment::query()
            ->where(fn ($query) => $query
                ->where(fn ($sub) => $sub->where('attachable_type', Customer::class)->where('attachable_id', $customerId))
                ->orWhere(fn ($sub) => $sub->where('attachable_type', Credit::class)->whereIn('attachable_id', $credits))
                ->orWhere(fn ($sub) => $sub->where('attachable_type', Installment::class)->whereIn('attachable_id', $installments)))
            ->with('attachable')
            ->orderByDesc('id')
            ->get()
            ->mapWithKeys(fn (Attachment $attachment): array => [
                $attachment->id => ($attachment->original_name ?? basename($attachment->path))
                    . ' · ' . static::attachmentContext($attachment),
            ])
            ->all();
    }

    protected static function attachmentContext(Attachment $attachment): string
    {
        return match ($attachment->attachable_type) {
            Credit::class => __('ui.attachments.context_credit', ['id' => $attachment->attachable_id]),
            Installment::class => __('ui.attachments.context_installment', [
                'number' => $attachment->attachable?->number ?? '?',
                'credit' => $attachment->attachable?->credit_id ?? '?',
            ]),
            default => __('ui.attachments.context_loose'),
        };
    }
}