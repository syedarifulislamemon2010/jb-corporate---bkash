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
                TextColumn::make('credit_account_title')
                    ->label('Account Title')
                    ->searchable(),
                TextColumn::make('credit_account_no')
                    ->label('Account No')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status_id')
                    ->label('Status ID')
                    ->badge()
                    ->sortable(),
                TextColumn::make('approved_by')
                    ->label('Authorized By')
                    ->searchable(),
                TextColumn::make('approved_at')
                    ->label('Authorized At')
                    ->dateTime('d M Y, h:i A')
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
                                'approved_at' => Carbon::now(),
                            ]);
                        });
                    }),
            ]);
    }
}