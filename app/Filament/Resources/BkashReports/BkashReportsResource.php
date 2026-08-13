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

    protected static ?string $recordTitleAttribute = 'reference_id';

    protected static array $globallySearchableAttributes = [
        'reference_id',
        'txn_id',
        'debit_account_no',
        'debit_account_title',
        'credit_account_no',
        'file_name',
    ];

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Txn ID'   => $record->txn_id ?? 'N/A',
            'Channel'  => $record->transaction_type,
            'Amount'   => 'BDT ' . \App\Models\BkashTransaction::formatBdtAmount((float) $record->amount),
            'Account'  => $record->debit_account_no ?? 'N/A',
            'File'     => $record->file_name ?? 'N/A',
        ];
    }

    protected static \UnitEnum|string|null $navigationGroup = 'Audits & Reports';

    protected static ?string $navigationLabel = 'Transaction Process & EFT Reports';

    protected static ?string $pluralModelLabel = 'Transaction Process & EFT Reports';

    protected static ?int $navigationSort = 5;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Date — shown in A2A (4th img) and RTGS/BEFTN (5th img) reports
                TextColumn::make('create_date')
                    ->label('Date')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                // Ref No — all channels
                TextColumn::make('reference_id')
                    ->label('Ref No.')
                    ->searchable()
                    ->sortable(),

                // Channel badge
                TextColumn::make('transaction_type')
                    ->label('Channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A2A'   => 'success',
                        'BEFTN' => 'warning',
                        'RTGS'  => 'danger',
                        default => 'gray',
                    }),

                // Bank Account Name / A/C Name — all channels
                TextColumn::make('debit_account_title')
                    ->label('Bank Account Name')
                    ->searchable(),

                // Bank Account No / Beneficiary A/C No — all channels
                TextColumn::make('debit_account_no')
                    ->label('Bank Account No')
                    ->searchable(),

                // Bank & Branch Name — RTGS/BEFTN (credit_routing = Bank Name)
                TextColumn::make('credit_routing')
                    ->label('Bank & Branch Name')
                    ->searchable()
                    ->toggleable(),

                // Routing Code — RTGS/BEFTN
                TextColumn::make('debit_routing')
                    ->label('Routing Code')
                    ->searchable()
                    ->toggleable(),

                // Amount
                TextColumn::make('amount')
                    ->label('Amount (BDT)')
                    ->formatStateUsing(fn ($state) => BkashTransaction::formatBdtAmount((float)$state))
                    ->sortable(),

                // Debit Account — all channels
                TextColumn::make('credit_account_no')
                    ->label('Debit Account')
                    ->searchable(),

                // Txn ID — all channels
                TextColumn::make('txn_id')
                    ->label('Txn ID')
                    ->searchable()
                    ->sortable(),

                // Settlement Status
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                            ->when($data['from_date'], fn (Builder $q, $date) => $q->whereDate('create_date', '>=', $date))
                            ->when($data['to_date'], fn (Builder $q, $date) => $q->whereDate('create_date', '<=', $date));
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
