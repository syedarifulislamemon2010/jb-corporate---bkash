<?php

namespace App\Filament\Resources\BkashBatches;

use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;

class BkashBatchResource extends Resource
{
    protected static ?string $model = BkashTransactionBatch::class;

    protected static ?string $recordTitleAttribute = 'file_name';

    protected static array $globallySearchableAttributes = [
        'file_name',
        'created_by',
        'sha256',
    ];

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Channel'  => $record->transaction_type,
            'Total Trn'=> $record->total_data,
            'Amount'   => 'BDT ' . BkashTransaction::formatBdtAmount((float) $record->total_amount),
            'Created By' => $record->created_by,
        ];
    }

    protected static \UnitEnum|string|null $navigationGroup = 'Audits & Reports';

    protected static ?string $navigationLabel = 'Batch File History';

    protected static ?string $pluralModelLabel = 'Batch File History';

    protected static ?int $navigationSort = 3;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-archive-box';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(50)
            ->paginated([10, 20, 50, 100, 200])
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

                TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('transaction_type')
                    ->label('Channel')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A2A'   => 'success',
                        'BEFTN' => 'warning',
                        'RTGS'  => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('total_data')
                    ->label('Total Transactions')
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total Amount (BDT)')
                    ->formatStateUsing(fn ($state) => BkashTransaction::formatBdtAmount((float) ($state ?? 0)))
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('status_id')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1000 => 'Pending Checker',
                        1001 => 'Checked',
                        1002 => '1st Authorized',
                        1003 => 'Final Authorized',
                        1004 => 'CBS Settled',
                        9000 => 'Rejected',
                        default => 'Unknown',
                    })
                    ->color(fn (int $state): string => match ($state) {
                        1000 => 'warning',
                        1001 => 'info',
                        1002, 1003 => 'primary',
                        1004 => 'success',
                        9000 => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('sha256')
                    ->label('SHA-256')
                    ->limit(12)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_by')
                    ->label('Created By')
                    ->searchable(),

                TextColumn::make('create_date')
                    ->label('Ingested At')
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
            'index' => Pages\ListBkashBatches::route('/'),
        ];
    }
}
