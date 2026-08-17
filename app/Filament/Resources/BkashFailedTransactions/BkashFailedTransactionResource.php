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

    protected static ?string $navigationIconColor = 'danger';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginated([10, 20, 50, 100, 200])
            ->columns([
                // 1. Continuous Serial Number
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

                // 2. Failed At (Moved forward for operational relevance)
                TextColumn::make('created_at')
                    ->label('Failed At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                // 3. File Name
                TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),

                // 4. Row No
                TextColumn::make('row_number')
                    ->label('Row No')
                    ->alignRight()
                    ->sortable(),

                // 5. Reason for Failure / Dormant — Native danger token
                TextColumn::make('reject_reason')
                    ->label('Reason for Failure / Dormant')
                    ->wrap()
                    ->badge()
                    ->color('danger')
                    ->searchable(),

                // 6. Channel
                TextColumn::make('transaction_type')
                    ->label('Channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A2A'   => 'success',
                        'BEFTN' => 'warning',
                        'RTGS'  => 'danger',
                        default => 'gray',
                    }),

                // 7. Ref No
                TextColumn::make('reference_id')
                    ->label('Ref No')
                    ->searchable()
                    ->sortable(),

                // 8. Debit Account
                TextColumn::make('debit_account_no')
                    ->label('Debit Account')
                    ->searchable(),

                // 9. Beneficiary Account
                TextColumn::make('credit_account_no')
                    ->label('Beneficiary Account')
                    ->searchable(),

                // 10. Amount (BDT)
                TextColumn::make('amount')
                    ->label('Amount (BDT)')
                    ->formatStateUsing(fn ($state) => BkashTransaction::formatBdtAmount((float)$state))
                    ->alignRight()
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
