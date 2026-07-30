<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Schemas;

use App\Models\Client;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClientInfolist
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('resources.clients.sections.client'))
                    ->schema([
                        TextEntry::make('full_name')
                            ->label(__('resources.clients.fields.full_name')),

                        TextEntry::make('phone_primary') 
                                ->label(__('resources.clients.fields.phones'))
                                ->formatStateUsing(function ($state, $record){ 
                                    return $state . '/' . $record->phone_secondary; 
                                }),
                                
                        TextEntry::make('identity_document')
                                ->label(__('resources.clients.fields.identity_document')),
                            
                        TextEntry::make('email')
                                ->label(__('resources.clients.fields.email')),
                            
                        TextEntry::make('address')
                                ->label(__('resources.clients.fields.address')), 
                    ])
                    ->columns(4),

                Section::make(__('resources.clients.sections.financed_article'))
                    ->schema([
                        TextEntry::make('latestCredit.articleUnit.display_name')
                            ->label(__('resources.clients.fields.vehicle')),

                        TextEntry::make('latestCredit.articleUnit.article.cash_price')
                            ->label(__('resources.clients.fields.vehicle')),
                        
                        TextEntry::make('latestCredit.down_payment')
                            ->label(__('resources.clients.fields.down_payment')),
                    ])
                    ->columns(3),

                Section::make(__('resources.clients.sections.credit_summary'))
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
                            ->color('danger'),

                        TextEntry::make('latestCredit.installment_amount')
                            ->label(__('resources.clients.fields.installment_amount'))
                            ->money('USD'),
                    ])
                    ->columns(2),

                Section::make(__('resources.clients.sections.credit_status'))
                    ->schema([
                        TextEntry::make('remaining_installments')
                            ->label(__('resources.clients.fields.remaining_installments'))
                            ->state(function (Client $record): string {
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
                            ->state(function (Client $record): string {
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
                    ->description(__('resources.clients.sections.latest_payments_description'))
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->state(function (Client $record) {
                                return $record->latestCredit?->paymentHistories()
                                    ->latest('payment_date')
                                    ->take(12)
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
                                    ->label(__('resources.clients.fields_payment_method')),

                                TextEntry::make('receipt_number')
                                    ->label(__('resources.clients.fields.receipt_number')),
                            ])
                            ->columns(4),
                    ]),
            ])
        ;
    }

}