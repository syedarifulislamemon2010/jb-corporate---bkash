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

use Filament\Tables\Grouping\Group;
use Illuminate\Support\HtmlString;

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
                    ->getTitleFromRecordUsing(function (BkashTransaction $record): HtmlString {
                        $fileName = $record->file_name ?? 'Batch_File.xlsx';
                        $batch = \App\Models\BkashTransactionBatch::where('file_name', $fileName)->first();
                        $totalTrn = $batch ? $batch->total_data : \App\Models\BkashTransaction::where('file_name', $fileName)->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->count();
                        $totalAmount = $batch ? (float)$batch->total_amount : (float)\App\Models\BkashTransaction::where('file_name', $fileName)->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->sum('amount');
                        $formattedAmount = \App\Models\BkashTransaction::formatBdtAmount($totalAmount);
                        $channel = $record->transaction_type ?? ($batch ? $batch->transaction_type : 'A2A');
                        $ext = strtoupper(pathinfo($fileName, PATHINFO_EXTENSION) ?: 'XLSX');
                        $channelColor = match ($channel) {
                            'RTGS'  => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                            'BEFTN' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                            'A2A'   => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700',
                        };

                        $downloadUrl = route('admin.bkash.download-batch', ['file' => $fileName]);

                        return new HtmlString("
                            <div class=\"flex items-center justify-between w-full flex-wrap gap-2 py-0.5\">
                                <div class=\"flex items-center gap-2.5 flex-wrap\">
                                    <span class=\"font-mono font-bold text-sm text-primary-600 dark:text-primary-400 tracking-tight\">{$fileName}</span>
                                    <span class=\"px-2 py-0.5 text-xs font-semibold rounded bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300 border border-sky-200 dark:border-sky-800\">{$ext}</span>
                                    <span class=\"px-2 py-0.5 text-xs font-semibold rounded border {$channelColor}\">{$channel}</span>
                                    <span class=\"px-2.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700\">{$totalTrn} Trns</span>
                                    <span class=\"px-2.5 py-0.5 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800\">BDT {$formattedAmount}</span>
                                </div>
                                <div class=\"flex items-center gap-2\" onclick=\"event.stopPropagation()\">
                                    <a href=\"{$downloadUrl}\" target=\"_blank\" class=\"inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-700 bg-white dark:bg-gray-800 dark:text-gray-200 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition-colors\">
                                        <svg class=\"w-3.5 h-3.5 text-primary-600\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4\"/></svg>
                                        Download (Excel)
                                    </a>
                                </div>
                            </div>
                        ");
                    }),
            ])
            ->defaultGroup('file_name')
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
                BulkAction::make('export_selected_excel')
                    ->label('Export Selected (Excel)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->tooltip('Export selected transactions to Excel')
                    ->color('info')
                    ->action(function (Collection $records) {
                        $fileName = 'Transaction_Process_Report_' . now()->format('Ymd_His') . '.xlsx';
                        return ExcelExportService::exportCheckerReportXlsx($records, $fileName);
                    }),

                BulkAction::make('check_selected')
                    ->label('Check Selected Transactions')
                    ->icon('heroicon-o-check-circle')
                    ->tooltip('Verify and forward selected transactions to 1st Authorizer')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Checker Verification')
                    ->modalDescription(function (Collection $records) {
                        return "You are about to verify {$records->count()} transaction(s) and forward them to the 1st Authorizer queue.";
                    })
                    ->modalSubmitActionLabel('Yes, Verify Now')
                    ->action(function (Collection $records) {
                        $currentUser = Auth::user();
                        $checkerName = $currentUser->name ?? 'Checker User';
                        $checkerId   = $currentUser->id ?? null;
                        $firstRecord = $records->first();
                        $fileName = $firstRecord->file_name ?? 'bKash_File.xlsx';
                        $totalTrn = $records->count();
                        $totalAmount = (float)$records->sum('amount');

                        $records->each(function ($record) use ($checkerName, $checkerId) {
                            $record->update([
                                'status_id'     => BkashTransaction::STATUS_CHECKED,
                                'checked_by'    => $checkerName,
                                'checked_by_id' => $checkerId,
                                'checked_at'    => Carbon::now(),
                            ]);
                        });

                        // Refresh parent batch status
                        $batchIds = $records->pluck('batch_id')->filter()->unique();
                        \App\Models\BkashTransactionBatch::whereIn('id', $batchIds)->each(fn ($batch) => $batch->refreshStatusFromTransactions());

                        \Filament\Notifications\Notification::make()
                            ->title('Transactions Checked')
                            ->body("Successfully checked {$totalTrn} transactions. Forwarded for 1st Authorization.")
                            ->success()
                            ->send();

                        NotificationService::dispatchStage2($fileName, $totalTrn, $totalAmount, $checkerName, $currentUser);
                    }),
            ]);
    }
}