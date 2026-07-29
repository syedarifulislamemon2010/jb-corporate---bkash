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

                TextColumn::make('approved_by')
                    ->label('Authorized By')
                    ->searchable(),

                TextColumn::make('approved_at')
                    ->label('Authorized At')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->timezone('Asia/Dhaka')->format('d M Y, h:i A') : '-')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([
                BulkAction::make('authorize_selected')
                    ->label('Authorize Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $records->each(function ($record) {
                            $record->update([
                                'status_id'   => 1002,
                                'approved_by' => Auth::user()->name ?? 'SYSTEM',
                                'approved_at' => Carbon::now('Asia/Dhaka'),
                            ]);
                        });
                    }),
            ]);
    }
}