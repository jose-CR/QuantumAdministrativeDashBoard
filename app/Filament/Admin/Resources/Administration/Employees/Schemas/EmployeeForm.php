<?php

namespace App\Filament\Admin\Resources\Administration\Employees\Schemas;

use App\Models\Employee;
use App\Support\JobTitles;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('resources.employees.sections.identity'))
                    ->schema([
                        TextInput::make('code')
                            ->label(__('resources.employees.fields.code'))
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->helperText(__('resources.employees.fields.code_help')),

                        TextInput::make('name')
                            ->label(__('resources.employees.fields.name'))
                            ->required()
                            ->maxLength(255),

                        Select::make('document_type')
                            ->label(__('resources.employees.fields.document_type'))
                            ->options(collect(Employee::DOCUMENT_TYPES)
                                ->mapWithKeys(fn (string $type): array => [
                                    $type => __('resources.employees.document_types.' . strtolower(str_replace(' ', '_', $type))),
                                ])
                                ->all()),

                        TextInput::make('document_number')
                            ->label(__('resources.employees.fields.document_number'))
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),

                        TextInput::make('salary_amount')
                            ->label(__('resources.employees.fields.salary_amount'))
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->minValue(0)
                            ->helperText(__('resources.employees.fields.salary_amount_help')),

                        // Puestos del catálogo más los que ya se usaron, con la opción
                        // de crear uno nuevo sin tocar código.
                        Select::make('job_title')
                            ->label(__('resources.employees.fields.job_title'))
                            ->searchable()
                            ->native(false)
                            ->getSearchResultsUsing(fn (string $search): array => JobTitles::search($search))
                            ->getOptionLabelUsing(fn (?string $value): ?string => filled($value) ? JobTitles::label($value) : null)
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(__('ui.employees.new_job_title'))
                                    ->required()
                                    ->minLength(2)
                                    ->maxLength(30),
                            ])
                            ->createOptionModalHeading(__('ui.employees.new_job_title'))
                            ->createOptionUsing(fn (array $data): ?string => JobTitles::normalize($data['name'])),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('resources.employees.sections.contact'))
                    ->schema([
                        TextInput::make('phone')
                            ->label(__('resources.employees.fields.phone'))
                            ->tel()
                            ->maxLength(25),

                        TextInput::make('email')
                            ->label(__('resources.employees.fields.email'))
                            ->email()
                            ->maxLength(255),

                        TextInput::make('address')
                            ->label(__('resources.employees.fields.address'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('resources.employees.sections.employment'))
                    ->schema([
                        DatePicker::make('hire_date')
                            ->label(__('resources.employees.fields.hire_date')),

                        // Solo tiene sentido cuando la persona está de baja.
                        DatePicker::make('termination_date')
                            ->label(__('resources.employees.fields.termination_date'))
                            ->visible(fn (Get $get): bool => $get('status') === Employee::STATUS_INACTIVE),

                        Select::make('status')
                            ->label(__('resources.employees.fields.status'))
                            ->options([
                                Employee::STATUS_ACTIVE => __('resources.employees.statuses.active'),
                                Employee::STATUS_INACTIVE => __('resources.employees.statuses.inactive'),
                            ])
                            ->default(Employee::STATUS_ACTIVE)
                            ->required()
                            ->live(),

                        // El login es opcional: la mayoría de los empleados no entra al panel.
                        Select::make('user_id')
                            ->label(__('resources.employees.fields.user'))
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText(__('resources.employees.fields.user_help')),

                        Textarea::make('notes')
                            ->label(__('resources.employees.fields.notes'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
