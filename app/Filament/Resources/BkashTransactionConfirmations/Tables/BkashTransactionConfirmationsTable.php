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
                TextColumn::make('confirmed_by')
                    ->label('Confirmed By')
                    ->searchable(),
                TextColumn::make('confirmed_at')
                    ->label('Confirmed At')
                    ->dateTime('d M Y, h:i A')
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
                                'confirmed_at' => Carbon::now(),
                            ]);
                        });
                    }),
            ]);
    }
}