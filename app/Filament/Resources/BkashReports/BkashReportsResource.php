<?php

namespace App\Filament\Resources\BkashReports;

use App\Models\BkashTransaction;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class BkashReportsResource extends Resource
{
    protected static ?string $model = BkashTransaction::class;

    protected static \UnitEnum|string|null $navigationGroup = 'bKash Management';

    protected static ?string $navigationLabel = 'Transaction Process & EFT Reports';

    protected static ?string $pluralModelLabel = 'Transaction Process & EFT Reports';

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('txn_id')
                    ->label('Txn ID / Ref')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reference_id')
                    ->label('Reference No')
                    ->searchable(),

                TextColumn::make('transaction_type')
                    ->label('Channel')
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
                    ->formatStateUsing(fn ($state) => BkashTransaction::formatBdtAmount((float)$state))
                    ->sortable(),

                TextColumn::make('status_id')
                    ->label('Settlement Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ((int)$state) {
                        1000 => 'Pending Checker',
                        1001 => 'Checked',
                        1002 => '1st Authorized',
                        1003 => 'Final Authorized',
                        1004 => 'CBS / BACH Settled',
                        9000 => 'Rejected',
                        default => 'Processing',
                    })
                    ->color(fn ($state) => match ((int)$state) {
                        1004 => 'success',
                        1003 => 'primary',
                        9000 => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('updated_at')
                    ->label('Settled Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->label('Channel')
                    ->options([
                        'A2A'   => 'Account to Account',
                        'BEFTN' => 'BEFTN',
                        'RTGS'  => 'RTGS',
                    ]),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from_date')->label('From Date'),
                        DatePicker::make('to_date')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from_date'], fn (Builder $q, $date) => $q->whereDate('updated_at', '>=', $date))
                            ->when($data['to_date'], fn (Builder $q, $date) => $q->whereDate('updated_at', '<=', $date));
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBkashReports::route('/'),
        ];
    }
}
