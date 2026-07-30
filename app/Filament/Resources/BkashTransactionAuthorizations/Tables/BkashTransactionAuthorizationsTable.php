<?php

namespace App\Filament\Resources\BkashTransactionAuthorizations\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BkashTransactionAuthorizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('txn_id')
                    ->label('Txn ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reference_id')
                    ->label('Ref No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('transaction_type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('debit_account_no')
                    ->label('Debit Account')
                    ->searchable(),

                TextColumn::make('credit_account_title')
                    ->label('Beneficiary Name')
                    ->searchable(),

                TextColumn::make('credit_account_no')
                    ->label('Beneficiary Acc')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount (BDT)')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('credit_bank')
                    ->label('Bank & Branch')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('credit_routing')
                    ->label('Routing No')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status_id')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('approved_by_1')
                    ->label('1st Auth By')
                    ->searchable(),

                TextColumn::make('approved_at_1')
                    ->label('1st Auth At')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->timezone('Asia/Dhaka')->format('d M Y, h:i A') : '-')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([
                BulkAction::make('authorize_first_level')
                    ->label('Authorize Selected (1st Approval)')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each(function ($record) {
                            $record->update([
                                'status_id'     => 1002, // 1st Authorized Status
                                'approved_by_1' => Auth::user()->name ?? 'SYSTEM',
                                'approved_at_1' => Carbon::now('Asia/Dhaka'),
                            ]);
                        });

                        // ✉️ Trigger Notification Event Here (For Authorizer 1 Approval)
                    }),
            ]);
    }
}