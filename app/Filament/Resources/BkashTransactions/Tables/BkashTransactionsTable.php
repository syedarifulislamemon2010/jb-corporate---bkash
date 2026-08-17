<?php

namespace App\Filament\Resources\BkashTransactions\Tables;

use App\Models\BkashTransaction;
use App\Services\NotificationService;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BkashTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->defaultPaginationPageOption(50)
            ->paginated([10, 20, 50, 100, 200])
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER);
            })
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

                TextColumn::make('reference_id')
                    ->label('Ref No')
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

                TextColumn::make('debit_account_title')
                    ->label('Bank Account Name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('debit_account_no')
                    ->label('Bank Account No')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('amount')
                    ->label('Amount (BDT)')
                    ->formatStateUsing(fn ($state) => BkashTransaction::formatBdtAmount((float)$state))
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('debit_routing')
                    ->label('Routing Number')
                    ->searchable()
                    ->alignRight()
                    ->toggleable(),

                TextColumn::make('credit_routing')
                    ->label('Bank Name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('credit_bank')
                    ->label('Branch Name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('credit_account_no')
                    ->label('Debit Account')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('txn_id')
                    ->label('Txn ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
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
            ])
            ->toolbarActions([
                BulkAction::make('check_selected')
                    ->label('Check Selected Transactions')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $checkerName = Auth::user()->name ?? 'Checker User';
                        $firstRecord = $records->first();
                        $fileName = $firstRecord->file_name ?? 'bKash_File.xlsx';
                        $totalTrn = $records->count();
                        $totalAmount = (float)$records->sum('amount');

                        $records->each(function ($record) use ($checkerName) {
                            $record->update([
                                'status_id'  => BkashTransaction::STATUS_CHECKED,
                                'checked_by' => $checkerName,
                                'checked_at' => Carbon::now(),
                            ]);
                        });

                        NotificationService::dispatchStage2($fileName, $totalTrn, $totalAmount, $checkerName);
                    }),
            ]);
    }
}