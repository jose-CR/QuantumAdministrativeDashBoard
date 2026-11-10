<?php

namespace App\Filament\Admin\Resources\Client\Customers\Schemas;

use App\Livewire\CustomerAttachments;
use App\Models\Customer;
use App\Models\Installment;
use App\Support\ActividadesEconomicas;
use App\Support\CreditStatus;
use App\Support\ElSalvadorCatalogo;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

/**
 * Ficha del cliente: quién es, cuánto debe (CxC) y qué está pagando.
 *
 * Todo lo del crédito sale del crédito ACTIVO, no del último: un cliente puede
 * tener uno completado y uno vigente, y la ficha mostraba el más reciente.
 */
class CustomerInfoList
{
    private static function hasActiveCredit(Customer $record): bool
    {
        return $record->activeCredit !== null;
    }

    /**
     * Créditos que hoy se están cobrando.
     */
    private static function activeCredits(Customer $record)
    {
        return $record->credits()->where('status', 'active');
    }

    /**
     * Cuotas de los créditos activos.
     */
    private static function activeInstallments(Customer $record)
    {
        return Installment::query()
            ->whereIn('credit_id', self::activeCredits($record)->select('id'));
    }

    /**
     * Total por cobrar: el saldo pendiente de los créditos activos.
     */
    private static function receivable(Customer $record): float
    {
        return round((float) self::activeCredits($record)->sum('pending_balance'), 2);
    }

    /**
     * Lo que ya venció y sigue sin pagarse.
     */
    private static function overdue(Customer $record): float
    {
        return round((float) self::activeInstallments($record)
            ->whereIn('status', Installment::UNPAID_STATUSES)
            ->whereDate('due_date', '<', today())
            ->sum('remaining_balance'), 2);
    }

    private static function nextDue(Customer $record): ?string
    {
        return self::activeInstallments($record)
            ->whereIn('status', Installment::UNPAID_STATUSES)
            ->min('due_date');
    }

    private static function installmentsProgress(Customer $record): string
    {
        $total = self::activeInstallments($record)->count();

        $paid = self::activeInstallments($record)
            ->where('status', 'paid')
            ->count();

        return "{$paid} / {$total}";
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                /*
                 * QUIÉN ES
                 */
                Section::make(__('resources.clients.sections.client'))
                    ->icon(Heroicon::UserCircle)
                    ->description(fn (Customer $record): string => trim(
                        $record->document_type . ' ' . $record->document_number
                    ))
                    ->schema([
                        TextEntry::make('full_name')
                            ->hiddenLabel()
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->columnSpanFull(),

                        TextEntry::make('phone_primary')
                            ->label(__('resources.clients.fields.phones'))
                            ->icon(Heroicon::Phone)
                            ->state(fn (Customer $record): string => collect([
                                $record->phone_primary,
                                $record->phone_secondary,
                            ])->filter()->implode(' / ') ?: '—'),

                        TextEntry::make('email')
                            ->label(__('resources.clients.fields.email'))
                            ->icon(Heroicon::Envelope)
                            ->placeholder('—'),

                        TextEntry::make('location')
                            ->label(__('resources.clients.fields.enterprise.location'))
                            ->icon(Heroicon::MapPin)
                            ->state(fn (Customer $record): string => ElSalvadorCatalogo::locationLabel(
                                (string) $record->department,
                                (string) $record->municipality,
                                (string) $record->district,
                            )),

                        TextEntry::make('address')
                            ->label(__('resources.clients.fields.address'))
                            ->icon(Heroicon::MapPin)
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                /*
                 * CUENTAS POR COBRAR
                 *
                 * Lo que el taller tiene que cobrarle hoy, en una sola línea:
                 * total, vencido, próximo vencimiento y cuánto va pagado.
                 */
                Section::make(__('resources.clients.sections.account_receivable'))
                    ->icon(Heroicon::Banknotes)
                    ->description(__('resources.clients.sections.account_receivable_description'))
                    ->schema([
                        TextEntry::make('receivable')
                            ->label(__('resources.clients.fields.total_receivable'))
                            ->state(fn (Customer $record): float => self::receivable($record))
                            ->money('USD')
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->color(fn (Customer $record): string => self::receivable($record) > 0 ? 'danger' : 'gray'),

                        TextEntry::make('overdue')
                            ->label(__('resources.clients.fields.overdue_amount'))
                            ->state(fn (Customer $record): float => self::overdue($record))
                            ->money('USD')
                            ->weight(FontWeight::Bold)
                            ->icon(Heroicon::ExclamationTriangle)
                            ->color(fn (Customer $record): string => self::overdue($record) > 0 ? 'danger' : 'success'),

                        TextEntry::make('next_due')
                            ->label(__('resources.clients.fields.next_due_date'))
                            ->state(fn (Customer $record): ?string => self::nextDue($record))
                            ->date('d/m/Y')
                            ->icon(Heroicon::CalendarDays)
                            ->placeholder(__('resources.clients.fields.nothing_pending')),

                        TextEntry::make('installments_progress')
                            ->label(__('resources.clients.fields.installments_paid'))
                            ->state(fn (Customer $record): string => self::installmentsProgress($record))
                            ->icon(Heroicon::CheckCircle),

                        TextEntry::make('active_credits_count')
                            ->label(__('resources.clients.fields.active_credits'))
                            ->state(fn (Customer $record): int => self::activeCredits($record)->count())
                            ->badge()
                            ->color('gray'),
                    ])
                    ->columns(['default' => 1, 'md' => 3, 'xl' => 5])
                    ->columnSpanFull(),

                /*
                 * CRÉDITO ACTIVO
                 */
                Section::make(__('resources.clients.sections.active_credit'))
                    ->icon(Heroicon::CreditCard)
                    ->visible(fn (Customer $record): bool => self::hasActiveCredit($record))
                    ->schema([
                        RepeatableEntry::make('activeCredit.items')
                            ->label(__('resources.clients.fields.vehicle'))
                            ->schema([
                                TextEntry::make('display_name')
                                    ->label(__('resources.inventary.article.article'))
                                    ->weight(FontWeight::SemiBold),

                                TextEntry::make('total')
                                    ->label(__('resources.clients.fields.price'))
                                    ->money('USD')
                                    ->weight(FontWeight::Medium),
                            ])
                            ->columns(2)
                            ->contained(false)
                            ->columnSpanFull(),

                        TextEntry::make('activeCredit.installment_amount')
                            ->label(__('resources.clients.fields.installment_amount'))
                            ->money('USD'),

                        TextEntry::make('activeCredit.pending_balance')
                            ->label(__('resources.clients.fields.pending_balance'))
                            ->money('USD')
                            ->color('danger')
                            ->weight(FontWeight::Bold),

                        TextEntry::make('activeCredit.total_amount')
                            ->label(__('resources.clients.fields.total_amount'))
                            ->money('USD')
                            ->color('success'),

                        TextEntry::make('activeCredit.down_payment')
                            ->label(__('resources.clients.fields.down_payment'))
                            ->money('USD'),

                        TextEntry::make('activeCredit.start_date')
                            ->label(__('resources.clients.fields.start_date'))
                            ->date('d/m/Y'),

                        TextEntry::make('credit_progress')
                            ->label(__('resources.clients.fields.credit_progress'))
                            ->state(function (Customer $record): string {
                                $credit = $record->activeCredit;

                                if (! $credit || ! $credit->installments) {
                                    return '0%';
                                }

                                $paid = $credit->installments()
                                    ->where('status', 'paid')
                                    ->count();

                                return number_format(($paid / $credit->installments) * 100, 0) . '%';
                            }),

                        TextEntry::make('activeCredit.status')
                            ->label(__('resources.clients.fields.status.status'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => CreditStatus::label($state))
                            ->color(fn (?string $state): string => CreditStatus::color($state)),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),

                /*
                 * ÚLTIMOS PAGOS
                 */
                Section::make(__('resources.clients.sections.latest_payments'))
                    ->icon(Heroicon::CurrencyDollar)
                    ->description(__('resources.clients.sections.latest_payments_description'))
                    ->visible(fn (Customer $record): bool => self::hasActiveCredit($record))
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->state(fn (Customer $record) => $record->activeCredit
                                ?->paymentHistories()
                                ->latest('payment_date')
                                ->take(5)
                                ->get() ?? collect())
                            ->label(__('resources.clients.fields.recent_payments'))
                            ->contained(false)
                            ->schema([
                                TextEntry::make('payment_date')
                                    ->label(__('resources.clients.fields.payment_date'))
                                    ->date('d/m/Y'),

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
                                    ->label(__('resources.clients.fields.receipt_number'))
                                    ->placeholder('—'),
                            ])
                            ->columns(4),
                    ])
                    ->columnSpanFull(),

                /*
                 * DATOS DE EMPRESA (solo NIT)
                 */
                /*
                 * ADJUNTOS
                 *
                 * Tres tabs: lo que se subió a cada crédito, los comprobantes de
                 * cada cuota y las facturas o fotos sueltas del cliente.
                 */
                Section::make('Adjuntos')
                    ->icon(Heroicon::PaperClip)
                    ->description('Facturas y fotos: por crédito, por cuota o sueltas.')
                    ->schema([
                        Livewire::make(CustomerAttachments::class, fn (Customer $record): array => [
                            'record' => $record,
                        ]),
                    ])
                    ->columnSpanFull(),

                Section::make(__('resources.clients.sections.customer_data'))
                    ->icon(Heroicon::Briefcase)
                    ->visible(fn (Customer $record): bool => $record->document_type === 'NIT')
                    ->schema([
                        TextEntry::make('nrc')
                            ->label(__('resources.clients.fields.enterprise.nrc'))
                            ->placeholder('Sin NRC'),

                        TextEntry::make('economic_activity')
                            ->label(__('resources.clients.fields.enterprise.economic_activity'))
                            ->state(fn (Customer $record): string => ActividadesEconomicas::activityName(
                                $record->economic_activity
                            )),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
