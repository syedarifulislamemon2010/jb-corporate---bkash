<?php

namespace App\Filament\Resources\BkashTransactions\Schemas;

use App\Models\Bank;
use App\Models\Branch;
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
                            ->label('Transaction type')
                            ->placeholder('Select an Option')
                            ->options([
                                '01' => 'BEFTN',
                                '02' => 'RTGS',
                                '03' => 'JANATA BANK PLC.',
                            ]),
                        TextInput::make('reference_id')
                            ->required()
                            ->maxLength(60),
                    ]),
          
                Section::make('Account & Banking Details')
                    ->schema([
                        TextInput::make('debit_account_title')
                            ->maxLength(255),
                        TextInput::make('debit_account_no')
                            ->maxLength(60),
                        TextInput::make('amount')
                            ->required()
                            ->numeric(),
                        TextInput::make('debit_routing')
                            ->maxLength(20),
                        Select::make('credit_routing')
                            ->label('Creditor Bank')
                            ->placeholder('Select an option')
                            ->options(Bank::where('bankcode', '!=', 135)->where('status', 1)->orderby('bankname')->pluck('bankname', 'bankcode'))
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('credit_branch_routing', null);
                            })
                            ->required(),
                        Select::make('credit_branch_routing')
                            ->label('Creditor Bank Branch')
                            ->placeholder('Select an option')
                            ->options(function (Get $get) {
                                $bankCode = $get('credit_routing');

                                if (! $bankCode) {
                                    return [];
                                }

                                // BRANCHES টেবিলের সঠিক কলাম নেম (BANKID, BRANCHNAME, ROUTINGNO, STATUS)
                                return Branch::where('bankid', (string) $bankCode)
                                    ->where('status', 1)
                                    ->orderby('branchname')
                                    ->pluck('branchname', 'routingno')
                                    ->toArray();
                            })
                            ->disabled(fn (Get $get) => ! $get('credit_routing'))
                            ->searchable()
                            ->required(),
                        TextInput::make('credit_bank')
                            ->maxLength(10),
                        TextInput::make('credit_account_no')
                            ->maxLength(6),
                    ]),
            ]);
    }
}