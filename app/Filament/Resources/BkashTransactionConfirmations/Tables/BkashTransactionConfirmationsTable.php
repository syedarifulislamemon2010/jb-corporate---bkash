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
                TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('transaction_type')
                    ->label('Type')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('debit_account_title')
                    ->label('Debit Title')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('debit_account_no')
                    ->label('Debit Acc')
                    ->searchable(),

                TextColumn::make('credit_account_no')
                    ->label('Credit Acc')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('creditor_bank')
                    ->label('Creditor Bank')
                    ->searchable(),

                TextColumn::make('creditor_bank_branch')
                    ->label('Branch')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status_id')
                    ->label('Status ID')
                    ->badge()
                    ->sortable(),

                TextColumn::make('confirmed_by')
                    ->label('Confirmed By')
                    ->searchable(),

                TextColumn::make('confirmed_at')
                    ->label('Confirmed At')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->timezone('Asia/Dhaka')->format('d M Y, h:i A') : '-')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([
                BulkAction::make('confirm_selected')
                    ->label('Confirm Selected')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each(function ($record) {
                            $record->update([
                                'status_id'    => 1003,
                                'confirmed_by' => Auth::user()->name ?? 'SYSTEM',
                                'confirmed_at' => Carbon::now('Asia/Dhaka'),
                            ]);
                        });
                    }),
            ]);
    }
}