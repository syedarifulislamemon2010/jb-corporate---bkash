<?php

namespace App\Filament\Resources\BkashTransactionConfirmations\Tables;

use App\Jobs\ExecuteCbsSettlementJob;
use App\Models\BkashTransaction;
use App\Services\NotificationService;
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
            ->checkIfRecordIsSelectableUsing(function (BkashTransaction $record): bool {
                $currentUser = Auth::user();
                if (!$currentUser) {
                    return false;
                }

                // 3-Person Segregation of Duties: 2nd Authorizer cannot be the Checker or 1st Authorizer
                if ($currentUser->id && $record->checked_by_id && (int) $record->checked_by_id === (int) $currentUser->id) {
                    return false;
                }
                if ($currentUser->name && $record->checked_by && $record->checked_by === $currentUser->name) {
                    return false;
                }

                if ($currentUser->id && $record->approved_by_1_id && (int) $record->approved_by_1_id === (int) $currentUser->id) {
                    return false;
                }
                if ($currentUser->name && $record->approved_by_1 && $record->approved_by_1 === $currentUser->name) {
                    return false;
                }

                return true;
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

                TextColumn::make('checked_by')
                    ->label('Checked By')
                    ->default('System/Legacy')
                    ->searchable(),

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
                    ->label('Final Authorize (Instantly Settle)')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $currentUser = Auth::user();
                        $currentUserId = $currentUser->id ?? null;
                        $currentUserName = $currentUser->name ?? '2nd Authorizer';

                        // 3-Person Segregation of Duties Check: 2nd Authorizer != Checker AND 2nd Authorizer != 1st Authorizer
                        $ineligibleRecords = $records->filter(function ($record) use ($currentUserId, $currentUserName) {
                            $isChecker = ($currentUserId && $record->checked_by_id === $currentUserId) ||
                                         ($record->checked_by && $record->checked_by === $currentUserName);
                            $isAuth1   = ($currentUserId && $record->approved_by_1_id === $currentUserId) ||
                                         ($record->approved_by_1 && $record->approved_by_1 === $currentUserName);
                            return $isChecker || $isAuth1;
                        });

                        if ($ineligibleRecords->isNotEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Confirmation Blocked (Segregation of Duties)')
                                ->body('You checked or 1st-authorized this file; final confirmation must come from a third distinct user.')
                                ->danger()
                                ->persistent()
                                ->send();
                        }

                        $records = $records->diff($ineligibleRecords);

                        if ($records->isEmpty()) {
                            return;
                        }

                        $firstRecord = $records->first();
                        $fileName = $firstRecord->file_name ?? 'bKash_File.xlsx';
                        $totalTrn = $records->count();
                        $totalAmount = (float)$records->sum('amount');
                        $txnIds = $records->pluck('id')->toArray();

                        $records->each(function ($record) use ($currentUserName, $currentUserId) {
                            $record->update([
                                'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
                                'approved_by_2'    => $currentUserName,
                                'approved_by_2_id' => $currentUserId,
                                'approved_at_2'    => Carbon::now(),
                                'confirmed_by'     => $currentUserName,
                                'confirmed_at'     => Carbon::now(),
                            ]);
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Final Confirmation Completed')
                            ->body("Successfully authorized {$totalTrn} transactions. Dispatched for CBS settlement.")
                            ->success()
                            ->send();

                        // 1. Dispatch Stage 4 Notification (2nd Authorizer name)
                        NotificationService::dispatchStage4($fileName, $totalTrn, $totalAmount, $currentUserName, $currentUser);

                        // 2. Dispatch Automated Host-to-Host CBS Settlement Job
                        ExecuteCbsSettlementJob::dispatch($txnIds, $currentUserName);
                    }),
            ]);
    }
}