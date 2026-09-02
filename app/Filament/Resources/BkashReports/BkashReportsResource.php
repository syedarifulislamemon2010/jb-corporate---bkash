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

    protected static ?string $recordTitleAttribute = 'txn_id';

    public static function getGloballySearchableAttributes(): array
    {
        try {
            $table = (new static::$model)->getTable();
            $searchable = ['file_name'];

            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'txn_id')) {
                $searchable[] = 'txn_id';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'reference_id')) {
                $searchable[] = 'reference_id';
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn($table, 'bb_reference_number')) {
                $searchable[] = 'bb_reference_number';
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn($table, 'reference')) {
                $searchable[] = 'reference';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'beneficiary_account_no')) {
                $searchable[] = 'beneficiary_account_no';
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn($table, 'debit_account_no')) {
                $searchable[] = 'debit_account_no';
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'source_account_no')) {
                $searchable[] = 'source_account_no';
            } elseif (\Illuminate\Support\Facades\Schema::hasColumn($table, 'credit_account_no')) {
                $searchable[] = 'credit_account_no';
            }

            return $searchable;
        } catch (\Throwable $e) {
            return ['file_name'];
        }
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        $id = $record->txn_id ?: ($record->reference_id ?? ($record->bb_reference_number ?? ($record->reference ?? 'Record')));
        return "Report: {$id}";
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Txn ID'   => $record->txn_id ?? 'N/A',
            'Channel'  => $record->transaction_type,
            'Amount'   => 'BDT ' . \App\Models\BkashTransaction::formatBdtAmount((float) $record->amount),
            'Account'  => $record->beneficiary_account_no ?? 'N/A',
            'File'     => $record->file_name ?? 'N/A',
        ];
    }

    protected static \UnitEnum|string|null $navigationGroup = 'Audits & Reports';

    protected static ?string $navigationLabel = 'Transaction Process & EFT Reports';

    protected static ?string $pluralModelLabel = 'Transaction Process & EFT Reports';

    protected static ?int $navigationSort = 5;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-document-chart-bar';

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No Transaction Reports Found')
            ->emptyStateDescription('No transactions match the selected filter criteria.')
            ->emptyStateIcon('heroicon-o-document-chart-bar')
            ->striped()
            ->columns([
                // 1. Global Serial Number — Continuous across pagination
                TextColumn::make('index')
                    ->label('#')
                    ->state(function (TextColumn $component, $record, Table $table): string {
                        $paginator = $table->getRecords();
                        if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator || $paginator instanceof \Illuminate\Contracts\Pagination\Paginator) {
                            $offset = ($paginator->currentPage() - 1) * $paginator->perPage();
                            $index = array_search($record->getKey(), $paginator->pluck($record->getKeyName())->toArray(), true);
                            return (string) ($offset + ($index !== false ? $index + 1 : 1));
                        }
                        return '1';
                    })
                    ->alignCenter(),

                // 2. Date
                TextColumn::make('create_date')
                    ->label('Date')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                // 2b. Value Date — Calendar/Value Date visibility
                TextColumn::make('value_date')
                    ->label('Value Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                // 3. Txn ID
                TextColumn::make('txn_id')
                    ->label('Txn ID')
                    ->searchable()
                    ->sortable(),

                // 4. Channel badge — Solid-tint conventions
                TextColumn::make('transaction_type')
                    ->label('Channel')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'A2A'   => 'success',
                        'BEFTN' => 'purple',
                        'RTGS'  => 'warning',
                        default => 'gray',
                    }),

                // 5. Settlement Status — Positioned next to Channel for zero-scroll visibility
                TextColumn::make('status_id')
                    ->label('Settlement Status')
                    ->badge()
                    ->sortable()
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
                        1003 => 'success',
                        1002 => 'primary',
                        1001 => 'info',
                        9000 => 'danger',
                        default => 'warning',
                    }),

                // 6. Bank Account Name
                TextColumn::make('debit_account_title')
                    ->label('Bank Account Name')
                    ->searchable(),

                // 7. Beneficiary Account
                TextColumn::make('beneficiary_account_no')
                    ->label('Beneficiary Account')
                    ->searchable(),

                // 8. Bank & Branch Name — Displays first 3 digits of credit routing as per requirement
                TextColumn::make('credit_routing')
                    ->label('Bank & Branch Name')
                    ->formatStateUsing(fn ($state, BkashTransaction $record) => BkashTransaction::getCreditBank3($state ?: $record->debit_routing, $record->credit_bank))
                    ->searchable()
                    ->toggleable(),

                // 9. Routing Code — Right-aligned identifier
                TextColumn::make('debit_routing')
                    ->label('Routing Code')
                    ->searchable()
                    ->alignRight()
                    ->toggleable(),

                // 10. Amount (BDT) — Right-aligned with tabular figures
                TextColumn::make('amount')
                    ->label('Amount (BDT)')
                    ->formatStateUsing(fn ($state) => BkashTransaction::formatBdtAmount((float)$state))
                    ->alignRight()
                    ->sortable(),

                // 11. Source Account
                TextColumn::make('source_account_no')
                    ->label('Source Account (TCSA/Ops)')
                    ->searchable(),

                // 12. Ref No.
                TextColumn::make('reference_id')
                    ->label('Ref No.')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        try {
                            if (\Illuminate\Support\Facades\Schema::hasColumn('bkash_transactions', 'reference_id')) {
                                return $query->where('reference_id', 'like', "%{$search}%");
                            }
                            if (\Illuminate\Support\Facades\Schema::hasColumn('bkash_transactions', 'bb_reference_number')) {
                                return $query->where('bb_reference_number', 'like', "%{$search}%");
                            }
                            if (\Illuminate\Support\Facades\Schema::hasColumn('bkash_transactions', 'reference')) {
                                return $query->where('reference', 'like', "%{$search}%");
                            }
                        } catch (\Throwable $e) {
                        }
                        return $query;
                    })
                    ->sortable(),

                // 13. Settled Date (Toggleable hidden by default)
                TextColumn::make('updated_at')
                    ->label('Settled Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->paginated([10, 20, 50, 100, 200])
            ->defaultPaginationPageOption(10)
            ->filters([
                SelectFilter::make('transaction_type')
                    ->label('Channel')
                    ->options([
                        'A2A'   => 'Account to Account',
                        'BEFTN' => 'BEFTN',
                        'RTGS'  => 'RTGS',
                    ]),

                Filter::make('create_date_range')
                    ->label('Filter by Create Date')
                    ->form([
                        DatePicker::make('from_create_date')->label('From Create Date'),
                        DatePicker::make('to_create_date')->label('To Create Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from_create_date'], fn (Builder $q, $date) => $q->whereDate('create_date', '>=', $date))
                            ->when($data['to_create_date'], fn (Builder $q, $date) => $q->whereDate('create_date', '<=', $date));
                    }),

                Filter::make('value_date_range')
                    ->label('Filter by Value Date')
                    ->form([
                        DatePicker::make('from_value_date')->label('From Value Date'),
                        DatePicker::make('to_value_date')->label('To Value Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from_value_date'], fn (Builder $q, $date) => $q->whereDate('value_date', '>=', $date))
                            ->when($data['to_value_date'], fn (Builder $q, $date) => $q->whereDate('value_date', '<=', $date));
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
