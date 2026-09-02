<?php

namespace App\Filament\Resources\BkashTransactions\Pages;

use App\Filament\Resources\BkashTransactions\BkashTransactionResource;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Services\ExcelExportService;
use App\Services\NotificationService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Livewire\Attributes\Url;

class ListBkashTransactions extends ListRecords
{
    protected static string $resource = BkashTransactionResource::class;

    protected string $view = 'filament.resources.bkash-transactions.pages.batch-pipeline-table';

    #[Url(as: 'channel')]
    public string $activeChannel = 'all';

    #[Url(as: 'q')]
    public string $searchQuery = '';

    public array $selectedBatches = [];

    public bool $selectAll = false;

    protected function getViewData(): array
    {
        return [
            'actionLabel'      => 'Check Selected Batch Files',
            'actionMethod'     => 'checkSelectedBatches',
            'emptyHeading'     => 'All Caught Up!',
            'emptyDescription' => 'No files are currently pending Checker verification.',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload_excel')
                ->label('Upload bKash Excel File')
                ->icon('heroicon-o-document-arrow-up')
                ->tooltip('Upload and ingest a new bKash settlement Excel file')
                ->color('primary')
                ->url(BkashTransactionResource::getUrl('upload')),

            Action::make('export_excel')
                ->label('Export Report (Excel)')
                ->icon('heroicon-o-arrow-down-tray')
                ->tooltip('Export all pending checker transactions to an Excel file')
                ->color('success')
                ->action(function () {
                    $transactions = BkashTransaction::where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)
                        ->orderBy('create_date', 'desc')
                        ->get();

                    $fileName = 'Transaction_Process_Report_' . now()->format('Ymd_His') . '.xlsx';

                    return ExcelExportService::exportCheckerReportXlsx($transactions, $fileName);
                }),
        ];
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedBatches = $this->getBatches()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selectedBatches = [];
        }
    }

    public function getBatches(): Collection
    {
        $query = BkashTransactionBatch::query()
            ->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER);

        if ($this->activeChannel === 'a2a') {
            $query->where('transaction_type', 'A2A');
        } elseif ($this->activeChannel === 'beftn') {
            $query->where('transaction_type', 'BEFTN');
        } elseif ($this->activeChannel === 'rtgs') {
            $query->where('transaction_type', 'RTGS');
        }

        if (filled($this->searchQuery)) {
            $query->where(function (Builder $q) {
                $q->where('file_name', 'like', "%{$this->searchQuery}%")
                  ->orWhere('transaction_type', 'like', "%{$this->searchQuery}%");
            });
        }

        $batches = $query->orderBy('created_at', 'desc')->get();

        $fileNames = BkashTransaction::where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)
            ->pluck('file_name')
            ->unique()
            ->filter();

        foreach ($fileNames as $fn) {
            if (!$batches->contains('file_name', $fn)) {
                $channel = BkashTransaction::where('file_name', $fn)->value('transaction_type') ?? 'A2A';
                if ($this->activeChannel !== 'all' && strtolower($channel) !== strtolower($this->activeChannel)) {
                    continue;
                }
                if (filled($this->searchQuery) && !str_contains(strtolower($fn), strtolower($this->searchQuery))) {
                    continue;
                }
                $newBatch = BkashTransactionBatch::firstOrCreate(
                    ['file_name' => $fn],
                    [
                        'id'               => (string) Str::uuid(),
                        'transaction_type' => $channel,
                        'total_data'       => BkashTransaction::where('file_name', $fn)->count(),
                        'total_amount'     => BkashTransaction::where('file_name', $fn)->sum('amount'),
                        'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
                        'created_at'       => Carbon::now(),
                    ]
                );
                $batches->push($newBatch);
            }
        }

        return $batches;
    }

    public function checkSelectedBatches(): void
    {
        if (empty($this->selectedBatches)) {
            \Filament\Notifications\Notification::make()
                ->title('No Batch Selected')
                ->body('Please select at least one batch file to verify.')
                ->warning()
                ->send();
            return;
        }

        $currentUser = Auth::user();
        $checkerName = $currentUser->name ?? 'Janata Checker';
        $checkerId   = $currentUser->id ?? null;
        $now = Carbon::now();

        $batches = BkashTransactionBatch::whereIn('id', $this->selectedBatches)->get();
        $totalVerified = 0;

        foreach ($batches as $batch) {
            $txns = BkashTransaction::where(function (Builder $q) use ($batch) {
                $q->where('batch_id', $batch->id)
                  ->orWhere('file_name', $batch->file_name);
            })->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)->get();

            foreach ($txns as $txn) {
                $txn->update([
                    'status_id'     => BkashTransaction::STATUS_CHECKED,
                    'checked_by'    => $checkerName,
                    'checked_by_id' => $checkerId,
                    'checked_at'    => $now,
                ]);
                $totalVerified++;
            }

            $batch->update(['status_id' => BkashTransaction::STATUS_CHECKED]);

            if ($txns->isNotEmpty()) {
                NotificationService::dispatchWorkflowNotification(
                    stage: 2,
                    transaction: $txns->first(),
                    actorName: $checkerName,
                    actorId: $checkerId
                );
            }
        }

        $this->selectedBatches = [];
        $this->selectAll = false;

        \Filament\Notifications\Notification::make()
            ->title('Checker Verification Completed')
            ->body("Successfully verified {$totalVerified} transaction(s) across {$batches->count()} batch file(s).")
            ->success()
            ->send();
    }
}