<?php

namespace App\Filament\Admin\Resources\Client\Customers\Schemas;

use App\Models\Customer;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class CustomerInfoList
{

    private static function hasActiveCredit(Customer $record): bool {
        return $record->activeCredit !== null;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('resources.clients.sections.client'))
                    ->description(fn (Customer $record) =>
                        $record->activeCredit()->exists()
                            ? 'Información general del cliente'
                            : 'Este cliente no tiene un crédito activo actualmente.'
                    )
                    ->icon(fn (Customer $record) =>
                        $record->activeCredit()->exists()
                            ? Heroicon::UserCircle
                            : Heroicon::InformationCircle
                    )
                    ->schema([
                        TextEntry::make('full_name')
                            ->label(__('resources.clients.fields.full_name'))
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->icon(Heroicon::UserCircle)
                            ->columnSpanFull(),

                        TextEntry::make('phone_primary')
                            ->label(__('resources.clients.fields.phones'))
                            ->formatStateUsing(function ($state, $record) {
                                return $state . ' / ' . $record->phone_secondary;
                            })
                            ->icon(Heroicon::Phone),

                        TextEntry::make('document_number')
                            ->label(__('resources.clients.fields.identity_document'))
                            ->icon(Heroicon::Identification),

                        TextEntry::make('email')
                            ->label(__('resources.clients.fields.email'))
                            ->icon(Heroicon::Envelope),

                        TextEntry::make('address')
                            ->label(__('resources.clients.fields.address'))
                            ->icon(Heroicon::MapPin)
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                Section::make(__('resources.clients.sections.financed_article'))
                    ->visible(fn (Customer $record) => self::hasActiveCredit($record))
                    ->schema([
                        RepeatableEntry::make('latestCredit.items')
                            ->label(__('resources.clients.fields.vehicle'))
                            ->schema([
                                TextEntry::make('articleUnit.article.full_name')
                                    ->label('articulo')
                                    ->weight(FontWeight::SemiBold),
                            ])
                            ->columns(1)
                            ->contained(false),

                        RepeatableEntry::make('latestCredit.items')
                            ->label(__('Precio de la unidad'))
                            ->schema([
                                TextEntry::make('price')
                                    ->label('Precio')
                                    ->money('USD'),
                            ])
                            ->columns(1)
                            ->contained(false),
                        
                        TextEntry::make('latestCredit.down_payment')
                            ->label(__('resources.clients.fields.down_payment')),
                    ])
                    ->columns(3),

                Section::make(__('resources.clients.sections.credit_summary'))
                    ->visible(fn (Customer $record) => self::hasActiveCredit($record))
                    ->schema([
                        TextEntry::make('latestCredit.start_date')
                            ->label(__('resources.clients.fields.start_date'))
                            ->date(),

                        TextEntry::make('latestCredit.total_amount')
                            ->label(__('resources.clients.fields.total_amount'))
                            ->money('USD')
                            ->color('success'),

                        TextEntry::make('latestCredit.pending_balance')
                            ->label(__('resources.clients.fields.pending_balance'))
                            ->money('USD')
                            ->color('danger')
                            ->live(),

                        TextEntry::make('latestCredit.installment_amount')
                            ->label(__('resources.clients.fields.installment_amount'))
                            ->money('USD'),
                    ])
                    ->columns(2),

                Section::make(__('resources.clients.sections.credit_status'))
                    ->visible(fn (Customer $record) => self::hasActiveCredit($record))
                    ->schema([
                        TextEntry::make('remaining_installments')
                            ->label(__('resources.clients.fields.remaining_installments'))
                            ->state(function (Customer $record): string {
                                $credit = $record->latestCredit;

                                if (! $credit) {
                                    return __('resources.clients.messages.no_credits');
                                }

                                $remaining = $credit->installments()
                                    ->where('status', '!=', 'paid')
                                    ->count();

                                return __('resources.clients.messages.remaining_installments_format', [
                                    'remaining' => $remaining,
                                    'total' => $credit->installments,
                                ]);
                            }),

                        TextEntry::make('credit_progress')
                            ->label(__('resources.clients.fields.credit_progress'))
                            ->state(function (Customer $record): string {
                                $credit = $record->latestCredit;

                                if (! $credit) {
                                    return '0%';
                                }

                                $paid = $credit->installments()
                                    ->where('status', 'paid')
                                    ->count();

                                $progress = $credit->installments > 0
                                    ? ($paid / $credit->installments) * 100
                                    : 0;

                                return number_format($progress, 0) . '%';
                            }),

                        TextEntry::make('latestCredit.status')
                            ->label(__('resources.clients.fields.status'))
                            ->badge()
                            ->color(fn (string $state) => match ($state) {
                                'active' => 'success',
                                'refinanced' => 'warning',
                                'closed' => 'gray',
                                default => 'primary',
                            }),
                    ])
                    ->columns(3),

                Section::make(__('resources.clients.sections.latest_payments'))
                    ->visible(fn (Customer $record) => self::hasActiveCredit($record))
                    ->description(__('resources.clients.sections.latest_payments_description'))
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->state(function (Customer $record) {
                                return $record->latestCredit?->paymentHistories()
                                    ->latest('payment_date')
                                    ->take(37)
                                    ->get();
                            })
                            ->label(__('resources.clients.fields.recent_payments'))
                            ->contained(false)
                            ->schema([
                                TextEntry::make('payment_date')
                                    ->label(__('resources.clients.fields.payment_date'))
                                    ->date(),

                                TextEntry::make('amount')
                                    ->label(__('resources.clients.fields.amount'))
                                    ->money('USD')
                                    ->color('success'),

                                TextEntry::make('payment_method')
                                    ->label(__('resources.clients.fields.payment_method'))
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'cash' => __('resources.payment_history.cash'),
                                        'card' => __('resources.payment_history.card'),
                                        'bank_transfer' => __('resources.payment_history.bank_transfer'),
                                        default => $state,
                                    }),

                                TextEntry::make('receipt_number')
                                    ->label(__('resources.clients.fields.receipt_number')),
                            ])
                            ->columns(4),
                    ]),
            ])
        ;
    }
}