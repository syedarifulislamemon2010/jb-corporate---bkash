<?php

namespace App\Filament\Resources\EftReturns;

use App\Models\BkashTransaction;
use App\Models\EftReturn;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;

class EftReturnResource extends Resource
{
    protected static ?string $model = EftReturn::class;

    protected static \UnitEnum|string|null $navigationGroup = 'Audits & Reports';

    protected static ?string $navigationLabel = 'EFT Return Report';

    protected static ?string $pluralModelLabel = 'EFT Return Reports';

    protected static ?int $navigationSort = 6;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-receipt-refund';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->paginated([10, 20, 50, 100, 200])
            ->emptyStateHeading('No EFT Returns Found')
            ->emptyStateDescription('No return records exist for the selected criteria.')
            ->emptyStateIcon('heroicon-o-receipt-refund')
            ->columns([
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

                TextColumn::make('txn_id')
                    ->label('Txn ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reference_id')
                    ->label('Reference No')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('original_file_name')
                    ->label('Original File')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('beneficiary_account')
                    ->label('Beneficiary Account')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Amount (BDT)')
                    ->formatStateUsing(fn ($state) => BkashTransaction::formatBdtAmount((float) $state))
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('return_code')
                    ->label('Return Code')
                    ->badge()
                    ->color('danger'),

                TextColumn::make('return_reason')
                    ->label('Return Reason')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('returned_at')
                    ->label('Returned At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from_date')->label('From Date'),
                        DatePicker::make('to_date')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from_date'], fn (Builder $q, $date) => $q->whereDate('returned_at', '>=', $date))
                            ->when($data['to_date'], fn (Builder $q, $date) => $q->whereDate('returned_at', '<=', $date));
                    }),
            ])
            ->defaultSort('returned_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEftReturns::route('/'),
        ];
    }
}
