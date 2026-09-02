<?php

namespace App\Filament\Resources\BkashTransactions\Tables;

use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\BkashFailedTransaction;
use App\Services\NotificationService;
use App\Services\ExcelExportService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;
use Filament\Tables\Grouping\Group;

class BkashTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->defaultPaginationPageOption(50)
            ->paginated([10, 20, 50, 100, 200])
            ->emptyStateHeading('All Caught Up!')
            ->emptyStateDescription('No files are currently pending Checker verification.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER);
            })
            ->groups([
                Group::make('file_name')
                    ->label('Batch File')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(function (BkashTransaction $record): string {
                        $fileName = (string) ($record->file_name ?? 'Batch_File.xlsx');
                        $batch = BkashTransactionBatch::where('file_name', $fileName)->first();
                        $channel = $record->transaction_type ?? ($batch ? $batch->transaction_type : 'A2A');
                        $totalTrn = $batch ? $batch->total_data : BkashTransaction::where('file_name', $fileName)->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->count();
                        $totalAmount = $batch ? (float)$batch->total_amount : (float)BkashTransaction::where('file_name', $fileName)->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->sum('amount');
                        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);
                        return "{$fileName} · {$channel} · {$totalTrn} Trns · BDT {$formattedAmount}";
                    })
                    ->getDescriptionFromRecordUsing(function (BkashTransaction $record): HtmlString {
                        $fileName = $record->file_name ?? 'Batch_File.xlsx';
                        $batch = BkashTransactionBatch::where('file_name', $fileName)->first();

                        $channel = $record->transaction_type ?? ($batch ? $batch->transaction_type : 'A2A');
                        $totalTrn = $batch ? $batch->total_data : BkashTransaction::where('file_name', $fileName)->count();
                        $totalAmount = $batch ? (float)$batch->total_amount : (float)BkashTransaction::where('file_name', $fileName)->sum('amount');
                        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

                        $successTrn = BkashTransaction::where('file_name', $fileName)
                            ->whereIn('status_id', [
                                BkashTransaction::STATUS_CBS_SUCCESS,
                                BkashTransaction::STATUS_CBS_RESPONSE_SUCCESS,
                            ])->count();

                        $failedTrn = BkashTransaction::where('file_name', $fileName)
                            ->whereIn('status_id', [
                                BkashTransaction::STATUS_REJECTED,
                                BkashTransaction::STATUS_CBS_RESPONSE_FAILED,
                            ])->count() + BkashFailedTransaction::where('file_name', $fileName)->count();

                        static $fileIndexMap = [];
                        if (!isset($fileIndexMap[$fileName])) {
                            $fileIndexMap[$fileName] = count($fileIndexMap) + 1;
                        }
                        $index = $fileIndexMap[$fileName];

                        $downloadUrl = route('admin.bkash.download-batch', ['file' => $fileName]);

                        return new HtmlString(
                            view('filament.resources.bkash-transactions.file-group-header', [
                                'index'           => $index,
                                'fileName'        => $fileName,
                                'channel'         => $channel,
                                'totalTrn'        => $totalTrn,
                                'successTrn'      => $successTrn,
                                'failedTrn'       => $failedTrn,
                                'formattedAmount' => $formattedAmount,
                                'downloadUrl'     => $downloadUrl,
                            ])->render()
                        );
                    }),
            ])
            ->defaultGroup('file_name')
            ->collapsedGroupsByDefault()
            ->selectGroupsOnly()
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

                TextColumn::make('source_account_no')
                    ->label('Source Account (TCSA/Ops)')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('debit_account_title')
                    ->label('Beneficiary Name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('beneficiary_account_no')
                    ->label('Beneficiary Account')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('amount')
                    ->label('Amount (BDT)')
                    ->formatStateUsing(fn ($state) => BkashTransaction::formatBdtAmount((float)$state))
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('credit_routing')
                    ->label('Routing Number')
                    ->searchable()
                    ->alignRight()
                    ->toggleable(),

                TextColumn::make('credit_bank')
                    ->label('Bank Name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('txn_id')
                    ->label('Txn ID')
                    ->searchable()
                    ->sortable(),
            ])
            ->actions([
                Action::make('download_file')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->tooltip('Download source batch file')
                    ->url(fn (BkashTransaction $record): string => route('admin.bkash.download-batch', ['file' => $record->file_name ?? '']))
                    ->openUrlInNewTab()
                    ->visible(false), // Hidden from transaction rows as per user specification: download is only on the file header
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
                    ->label('Check Selected Batch Files')
                    ->icon('heroicon-o-check-circle')
                    ->tooltip('Verify and forward selected files to 1st Authorizer')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Checker Verification')
                    ->modalDescription(function (Collection $records) {
                        $files = $records->pluck('file_name')->unique()->count();
                        return "You are about to verify all transactions across {$files} selected batch file(s) ({$records->count()} transactions) and forward them to 1st Authorizer.";
                    })
                    ->modalSubmitActionLabel('Yes, Verify Now')
                    ->action(function (Collection $records) {
                        $currentUser = Auth::user();
                        $checkerName = $currentUser->name ?? 'Checker User';
                        $checkerId   = $currentUser->id ?? null;
                        $firstRecord = $records->first();
                        $fileName = $firstRecord->file_name ?? 'bKash_File.xlsx';

                        foreach ($records as $record) {
                            $record->update([
                                'status_id'     => BkashTransaction::STATUS_CHECKED,
                                'checked_by'    => $checkerName,
                                'checked_by_id' => $checkerId,
                                'checked_at'    => Carbon::now(),
                            ]);
                        }

                        $fileNames = $records->pluck('file_name')->unique();
                        foreach ($fileNames as $fn) {
                            BkashTransactionBatch::where('file_name', $fn)
                                ->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)
                                ->update(['status_id' => BkashTransaction::STATUS_CHECKED]);
                        }

                        $firstRecord->refresh();
                        NotificationService::dispatchWorkflowNotification(
                            stage: 2,
                            transaction: $firstRecord,
                            actorName: $checkerName,
                            actorId: $checkerId
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Checker Verification Completed')
                            ->body("Successfully verified {$records->count()} transaction(s) across " . $fileNames->count() . " batch file(s).")
                            ->success()
                            ->send();
                    }),

                BulkAction::make('download_source_file')
                    ->label('Download Source Batch')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->tooltip('Download original batch Excel file for selected records')
                    ->color('gray')
                    ->action(function (Collection $records) {
                        $fileName = $records->first()?->file_name;
                        if ($fileName) {
                            return redirect()->route('admin.bkash.download-batch', ['file' => $fileName]);
                        }
                    }),
            ]);
    }
}