<?php

namespace App\Filament\Resources\BkashFailedTransactions;

use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class BkashFailedTransactionResource extends Resource
{
    protected static ?string $model = BkashFailedTransaction::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Audits & Reports';

    protected static ?string $navigationLabel = 'Failed Transaction Report';

    protected static ?string $pluralModelLabel = 'Failed Transaction Reports';

    protected static ?int $navigationSort = 4;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('row_number')
                    ->label('Row No')
                    ->sortable(),

                TextColumn::make('transaction_type')
                    ->label('Channel')
                    ->badge(),

                TextColumn::make('reference_id')
                    ->label('Ref No')
                    ->searchable(),

                TextColumn::make('debit_account_no')
                    ->label('Debit Account')
                    ->searchable(),

                TextColumn::make('credit_account_no')
                    ->label('Beneficiary Account')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount (BDT)')
                    ->formatStateUsing(fn ($state) => BkashTransaction::formatBdtAmount((float)$state))
                    ->sortable(),

                TextColumn::make('reject_reason')
                    ->label('Reason for Failure / Dormant')
                    ->wrap()
                    ->color('danger')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Failed At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->options([
                        'A2A'   => 'Account to Account',
                        'BEFTN' => 'BEFTN',
                        'RTGS'  => 'RTGS',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBkashFailedTransactions::route('/'),
        ];
    }
}
