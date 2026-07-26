<?php

namespace App\Filament\Resources\BkashTransactions\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BkashTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->defaultPaginationPageOption(50)
            ->paginated([20, 50, 100])
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('status_id', 1);
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->sortable(),
                SelectColumn::make('transaction_type')
                    ->options([
                        '01' => 'Cash In',
                        '02' => 'Fund Transfer',
                        '03' => 'Merchant Payment',
                    ])
                    ->label('Transaction Type')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->sortable(),
                TextColumn::make('debit_account_no')
                    ->label('Debit Account'),
                TextColumn::make('credit_account_no')
                    ->label('Credit Account'),
                TextColumn::make('txn_id')
                    ->label('TXN ID'),
                SelectColumn::make('status_id')
                    ->options([
                        '1' => 'Pending',
                        '2' => 'Approved',
                        '3' => 'Confirmed',
                        '4' => 'Admin Approved',
                        '5' => 'CBS Success',
                        '0' => 'Rejected',
                    ])
                    ->label('Status'),
                TextColumn::make('created_by')
                    ->label('Created By'),
                TextColumn::make('create_date')
                    ->label('Create Date'),
                TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->multiple()
                    ->options([
                        '01' => 'Cash In',
                        '02' => 'Fund Transfer',
                        '03' => 'Merchant Payment',
                    ]),
                SelectFilter::make('status_id')
                    ->multiple()
                    ->options([
                        '1' => 'Pending',
                        '2' => 'Approved',
                        '3' => 'Confirmed',
                        '4' => 'Admin Approved',
                        '5' => 'CBS Success',
                        '0' => 'Rejected',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    BulkAction::make('authorize')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status_id' => 2])),
                ]),
            ]);
    }
}