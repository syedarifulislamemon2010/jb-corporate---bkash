<?php

namespace App\Filament\Resources\BkashTransactionConfirmations\Tables;

use App\Models\BkashTransaction;
use App\Services\NotificationService;
use App\Jobs\ExecuteCbsSettlementJob;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BkashTransactionConfirmationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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

                TextColumn::make('txn_id')
                    ->label('Txn ID')
                    ->searchable()
                    ->sortable(),

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
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('approved_by_1')
                    ->label('1st Auth By')
                    ->searchable(),

                TextColumn::make('approved_at_1')
                    ->label('1st Auth At')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->timezone('Asia/Dhaka')->format('d M Y, h:i A') : '-')
                    ->sortable(),
            ])
            ->bulkActions([
                BulkAction::make('authorize_final_level')
                    ->label('Final Authorize Selected (Instantly Settle)')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $authorizerName2 = Auth::user()->name ?? 'Authorizer 2';
                        $firstRecord = $records->first();
                        $fileName = $firstRecord->file_name ?? 'bKash_File.xlsx';
                        $totalTrn = $records->count();
                        $totalAmount = (float)$records->sum('amount');
                        $txnIds = $records->pluck('id')->toArray();

                        $records->each(function ($record) use ($authorizerName2) {
                            $record->update([
                                'status_id'     => BkashTransaction::STATUS_FINAL_AUTHORIZED,
                                'approved_by_2' => $authorizerName2,
                                'approved_at_2' => Carbon::now(),
                            ]);
                        });

                        NotificationService::dispatchStage4($fileName, $totalTrn, $totalAmount, $authorizerName2);

                        ExecuteCbsSettlementJob::dispatchSync($txnIds);
                    }),
            ]);
    }
}