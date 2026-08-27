<?php

namespace App\Filament\Resources\BkashTransactionAuthorizations\Tables;

use App\Models\BkashTransaction;
use App\Services\NotificationService;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BkashTransactionAuthorizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->paginated([10, 20, 50, 100, 200])
            ->emptyStateHeading('Nothing to Authorize')
            ->emptyStateDescription('No transactions are currently pending 1st-level authorization.')
            ->emptyStateIcon('heroicon-o-key')
            ->checkIfRecordIsSelectableUsing(function (BkashTransaction $record): bool {
                $currentUser = Auth::user();
                if (!$currentUser) {
                    return false;
                }

                // Segregation of Duties: Disable selection if current user checked this transaction
                if ($currentUser->id && $record->checked_by_id && (int) $record->checked_by_id === (int) $currentUser->id) {
                    return false;
                }
                if ($currentUser->name && $record->checked_by && $record->checked_by === $currentUser->name) {
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

                TextColumn::make('credit_account_no')
                    ->label('Debit Account')
                    ->searchable(),

                TextColumn::make('debit_account_title')
                    ->label('Beneficiary Name')
                    ->searchable(),

                TextColumn::make('debit_account_no')
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

                TextColumn::make('checked_at')
                    ->label('Checked At')
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->timezone('Asia/Dhaka')->format('d M Y, h:i A') : '-')
                    ->sortable(),

                TextColumn::make('create_date')
                    ->label('File Date')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->bulkActions([
                BulkAction::make('authorize_selected')
                    ->label('Authorize Selected (1st Level)')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        $currentUser = Auth::user();
                        $currentUserId = $currentUser->id ?? null;
                        $currentUserName = $currentUser->name ?? '1st Authorizer';

                        if ($records->isEmpty()) {
                            return;
                        }

                        // Segregation of Duties Check: 1st Authorizer != Checker
                        $checkedBySameUser = $records->filter(function ($record) use ($currentUserId, $currentUserName) {
                            return ($currentUserId && $record->checked_by_id === $currentUserId) ||
                                   ($record->checked_by && $record->checked_by === $currentUserName);
                        });

                        if ($checkedBySameUser->isNotEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Authorization Blocked (Segregation of Duties)')
                                ->body('You checked this file; 1st authorization must come from a different user.')
                                ->danger()
                                ->persistent()
                                ->send();
                        }

                        $records = $records->diff($checkedBySameUser);

                        if ($records->isEmpty()) {
                            return;
                        }

                        $firstRecord = $records->first();
                        $fileName = $firstRecord->file_name ?? 'bKash_File.xlsx';
                        $totalTrn = $records->count();
                        $totalAmount = (float)$records->sum('amount');

                        $records->each(function ($record) use ($currentUserName, $currentUserId) {
                            $record->update([
                                'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
                                'approved_by_1'    => $currentUserName,
                                'approved_by_1_id' => $currentUserId,
                                'approved_at_1'    => Carbon::now(),
                            ]);
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Transactions 1st Authorized')
                            ->body("Successfully authorized {$totalTrn} transactions. Forwarded for 2nd / Final Authorization.")
                            ->success()
                            ->send();

                        NotificationService::dispatchStage3($fileName, $totalTrn, $totalAmount, $currentUserName, $currentUser);
                    }),
            ]);
    }
}