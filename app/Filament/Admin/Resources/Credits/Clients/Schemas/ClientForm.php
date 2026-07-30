<?php

namespace App\Filament\Admin\Resources\Credits\Clients\Schemas;

use App\Models\ArticleUnit;
use App\Models\Credit;
use App\Utils\Filament\FilamentSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('resources.clients.sections.client'))
                    ->tabs([
                        Tab::make(__('resources.clients.sections.personal_information'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('full_name')
                                            ->label(__('resources.clients.fields.full_name'))
                                            ->required(),

                                        TextInput::make('identity_document')
                                            ->label(__('resources.clients.fields.identity_document'))
                                            ->placeholder(__('resources.clients.fields.identity_document_placeholder'))
                                            ->required()
                                            ->unique(ignoreRecord: true),

                                        DatePicker::make('birth_date')
                                            ->label(__('resources.clients.fields.birth_date'))
                                            ->default(now()->subYears(30)),

                                        Select::make('gender')
                                            ->label(__('resources.clients.fields.gender'))
                                            ->options([
                                                'male' => __('resources.clients.genders.male'),
                                                'female' => __('resources.clients.genders.female'),
                                            ])
                                            ->required(),

                                        Select::make('marital_status')
                                            ->label(__('resources.clients.fields.marital_status'))
                                            ->options([
                                                'single' => __('resources.clients.marital_statuses.single'),
                                                'married' => __('resources.clients.marital_statuses.married'),
                                                'divorced' => __('resources.clients.marital_statuses.divorced'),
                                                'widowed' => __('resources.clients.marital_statuses.widowed'),
                                            ]),

                                        TextInput::make('nationality')
                                            ->label(__('resources.clients.fields.nationality')),
                                    ])
                            ]),

                        Tab::make(__('resources.clients.sections.contact'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('phone_primary')
                                            ->label(__('resources.clients.fields.phone_primary'))
                                            ->tel()
                                            ->required(),

                                        TextInput::make('phone_secondary')
                                            ->label(__('resources.clients.fields.phone_secondary'))
                                            ->tel(),

                                        TextInput::make('email')
                                            ->label(__('resources.clients.fields.email'))
                                            ->email(),

                                        TextInput::make('address')
                                            ->label(__('resources.clients.fields.address'))
                                            ->columnSpanFull()
                                            ->required(),
                                    ]),
                            ]),

                        Tab::make(__('resources.clients.sections.references'))
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->schema([
                                Repeater::make('references')
                                    ->relationship()
                                    ->label(__('resources.clients.sections.references'))
                                    ->addActionLabel(__('resources.clients.actions.add_reference'))
                                    ->cloneable()
                                    ->itemLabel(
                                        fn (array $state): ?string =>
                                            $state['full_name']
                                                ?? __('resources.clients.messages.new_reference')
                                    )
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('full_name')
                                                    ->label(__('resources.clients.fields.full_name'))
                                                    ->required(),

                                                Select::make('reference_type')
                                                    ->label(__('resources.clients.fields.reference_type'))
                                                    ->options([
                                                        'family' => __('resources.clients.reference_types.family'),
                                                        'friend' => __('resources.clients.reference_types.friend'),
                                                    ])
                                                    ->required(),

                                                TextInput::make('relationship')
                                                    ->label(__('resources.clients.fields.relationship')),

                                                TextInput::make('phone')
                                                    ->label(__('resources.clients.fields.phone'))
                                                    ->tel()
                                                    ->required(),

                                                TextInput::make('occupation')
                                                    ->label(__('resources.clients.fields.occupation')),

                                                TextInput::make('address')
                                                    ->label(__('resources.clients.fields.address'))
                                                    ->columnSpanFull(),
                                            ])
                                    ])
                            ]),

                        Tab::make(__('resources.clients.sections.credit'))
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('article_unit_id')
                                            ->label(__('resources.clients.fields.article'))
                                            ->options(
                                                FilamentSelect::options(
                                                    ArticleUnit::class,
                                                    [
                                                        'display_name',
                                                    ]
                                                )
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $articleUnit = ArticleUnit::with('article')->find($state);

                                                $set(
                                                    'initial_amount',
                                                    $articleUnit?->article?->cash_price
                                                );
                                            })
                                            ->required(),

                                        TextInput::make('initial_amount')
                                            ->label(__('resources.clients.fields.initial_amount'))
                                            ->readOnly()
                                            ->prefix('$')
                                            ->required(),

                                        TextInput::make('down_payment')
                                            ->label(__('resources.clients.fields.down_payment'))
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(),

                                        TextInput::make('installments')
                                            ->label(__('resources.clients.fields.installments'))
                                            ->numeric()
                                            ->required(),

                                        TextInput::make('installment_amount')
                                            ->label(__('resources.clients.fields.installment_amount'))
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(),

                                        Select::make('periodicity')
                                            ->label(__('resources.clients.fields.periodicity'))
                                            ->options([
                                                'weekly' => __('resources.clients.periodicities.weekly'),
                                                'biweekly' => __('resources.clients.periodicities.biweekly'),
                                                'monthly' => __('resources.clients.periodicities.monthly'),
                                            ])
                                            ->required(),

                                        DatePicker::make('start_date')
                                            ->label(__('resources.clients.fields.start_date'))
                                            ->required(),

                                        TextInput::make('payment_day')
                                            ->label(__('resources.clients.fields.payment_day'))
                                            ->numeric()
                                            ->required(),

                                        Select::make('status')
                                            ->label(__('resources.clients.fields.status'))
                                            ->options([
                                                'pending' => __('resources.clients.statuses.pending'),
                                                'active' => __('resources.clients.statuses.active'),
                                                'paid' => __('resources.clients.statuses.paid'),
                                                'cancelled' => __('resources.clients.statuses.cancelled'),
                                                'completed' => __('resources.clients.statuses.completed'),
                                            ])
                                            ->default('active')
                                            ->required(),

                                        Select::make('refinanced_from_id')
                                            ->label(__('resources.clients.fields.refinanced_from'))
                                            ->options(
                                                Credit::query()
                                                    ->pluck('id', 'id')
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
