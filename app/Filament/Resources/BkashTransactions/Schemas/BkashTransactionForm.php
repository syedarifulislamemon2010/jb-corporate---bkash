<?php

namespace App\Filament\Resources\BkashTransactions\Schemas;

use App\Models\Bank;
use App\Models\Branch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BkashTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Details')
                    ->schema([
                        Select::make('transaction_type')
                            ->label('Transaction Type')
                            ->placeholder('Select Transaction Type')
                            ->options([
                                'BEFTN' => 'BEFTN',
                                'RTGS'  => 'RTGS',
                                'A2A'   => 'Account to Account (A2A)',
                            ])
                            ->required()
                            ->live(),

                        TextInput::make('reference_id')
                            ->label(fn (Get $get) => match ($get('transaction_type')) {
                                'A2A'   => 'Ref',
                                'RTGS'  => 'Ref / Ref No',
                                default => 'Ref / Ref No',
                            })
                            ->required()
                            ->maxLength(255),

                        DatePicker::make('create_date')
                            ->label('Date / Execution Date')
                            ->default(now())
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Account & Banking Details')
                    ->schema([
                        TextInput::make('debit_account_title')
                            ->label(fn (Get $get) => match ($get('transaction_type')) {
                                'A2A'   => 'Bank_Account_Name',
                                default => 'A/C Name / Bank_Account_Name / Bene. Name',
                            })
                            ->required()
                            ->maxLength(255),

                        TextInput::make('debit_account_no')
                            ->label(fn (Get $get) => match ($get('transaction_type')) {
                                'A2A'   => 'Bank_Account_No',
                                default => 'Account No / Beneficiary A/C No / Bank Account Number',
                            })
                            ->required()
                            ->maxLength(100),

                        TextInput::make('debit_routing')
                            ->label('Routing Code / RoutingNumber / Bene. Routing No')
                            ->visible(fn (Get $get) => in_array($get('transaction_type'), ['BEFTN', 'RTGS']))
                            ->maxLength(20),

                        Select::make('credit_routing')
                            ->label('Bank Name / Bene. Bank Name')
                            ->placeholder('Select Bank')
                            ->options(Bank::where('bankcode', '!=', 135)->where('status', 1)->orderBy('bankname')->pluck('bankname', 'bankcode'))
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('credit_bank', null))
                            ->visible(fn (Get $get) => in_array($get('transaction_type'), ['BEFTN', 'RTGS'])),

                        Select::make('credit_bank')
                            ->label('Branch Name / Bene. Branch Name')
                            ->placeholder('Select Branch')
                            ->searchable()
                            ->optionsLimit(5000)
                            ->options(function (Get $get) {
                                $bankCode = $get('credit_routing');
                                if (! $bankCode) {
                                    return [];
                                }

                                return Branch::where('bankid', (string) $bankCode)
                                    ->where('status', 1)
                                    ->orderBy('branchname')
                                    ->pluck('branchname', 'routingno')
                                    ->toArray();
                            })
                            ->disabled(fn (Get $get) => ! $get('credit_routing'))
                            ->visible(fn (Get $get) => in_array($get('transaction_type'), ['BEFTN', 'RTGS'])),

                        TextInput::make('credit_account_no')
                            ->label('Debit Account')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('amount')
                            ->label(fn (Get $get) => match ($get('transaction_type')) {
                                'A2A'   => 'Amount',
                                default => 'Amount / Amount(BDT) / Amount in Taka',
                            })
                            ->required()
                            ->numeric()
                            ->rules(function (Get $get) {
                                if ($get('transaction_type') === 'RTGS') {
                                    return ['numeric', 'min:100000'];
                                }
                                return ['numeric', 'gt:0'];
                            })
                            ->validationMessages([
                                'min' => 'RTGS এর জন্য ন্যূনতম ১,০০,০০০ (এক লক্ষ) টাকা হতে হবে।',
                            ]),
                    ])
                    ->columns(2),
            ]);
    }
}