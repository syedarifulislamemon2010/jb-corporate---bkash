<?php

namespace App\Filament\Resources\BkashTransactionConfirmations\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BkashTransactionConfirmationsTable
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
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at_1')
                    ->label('1st Auth At')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->timezone('Asia/Dhaka')->format('d M Y, h:i A') : '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_by_2')
                    ->label('2nd Auth By')
                    ->searchable(),

                TextColumn::make('approved_at_2')
                    ->label('2nd Auth At')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->timezone('Asia/Dhaka')->format('d M Y, h:i A') : '-')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([
                BulkAction::make('authorize_final_level')
                    ->label('Final Authorize Selected')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each(function ($record) {
                            $record->update([
                                'status_id'     => 1003, // Final Authorized Status -> Sent to CBS
                                'approved_by_2' => Auth::user()->name ?? 'SYSTEM',
                                'approved_at_2' => Carbon::now('Asia/Dhaka'),
                                'confirmed_by'  => Auth::user()->name ?? 'SYSTEM',
                                'confirmed_at'  => Carbon::now('Asia/Dhaka'),
                            ]);
                        });

                        // ✉️ Trigger Final Approval Notification & CBS Debit/Credit Processing Here
                    }),
            ]);
    }
}