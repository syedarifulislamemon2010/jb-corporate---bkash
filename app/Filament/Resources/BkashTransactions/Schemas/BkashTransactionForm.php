<?php

namespace App\Filament\Resources\BkashTransactions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BkashTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([            
                Section::make('Basic Details')
                    ->schema([
                        TextInput::make('transaction_type')
                            ->maxLength(2),
                        TextInput::make('reference_id')
                            ->required()
                            ->maxLength(60),
                        DateTimePicker::make('create_date'),
                        DateTimePicker::make('return_date'),
                    ])->columns(2),
          
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
                        TextInput::make('credit_routing')
                            ->required()
                            ->maxLength(10),
                        TextInput::make('credit_bank')
                            ->maxLength(10),
                        TextInput::make('credit_account_no')
                            ->maxLength(6),
                    ])->columns(2),

                Section::make('Status & Reason')
                    ->schema([
                        TextInput::make('txn_id')
                            ->maxLength(20),
                        TextInput::make('reject_reason')
                            ->maxLength(30),
                        TextInput::make('status_id')
                            ->maxLength(10),
                    ])->columns(3),

                Section::make('User Approvals')
                    ->schema([
                        TextInput::make('created_by')
                            ->maxLength(10),
                        TextInput::make('approved_by')
                            ->maxLength(10),
                        TextInput::make('confirmed_by')
                            ->maxLength(10),
                        TextInput::make('admin_approved')
                            ->maxLength(10),
                        DateTimePicker::make('approved_at'),
                        DateTimePicker::make('confirmed_at'),
                        DateTimePicker::make('admin_approved_at'),
                        DateTimePicker::make('cbs_success_at'),
                    ])->columns(2)->collapsible(),
            ]);
    }
}