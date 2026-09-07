<?php

namespace App\Filament\Resources\BkashTransactionAuthorizations\Pages;

use App\Filament\Resources\BkashTransactionAuthorizations\BkashTransactionAuthorizationResource;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Services\NotificationService;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Livewire\Attributes\Url;

class ListBkashTransactionAuthorizations extends ListRecords
{
    protected static string $resource = BkashTransactionAuthorizationResource::class;

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
            'actionLabel'      => 'Authorize Selected Batch Files',
            'actionMethod'     => 'authorizeSelectedBatches',
            'emptyHeading'     => 'Nothing to Authorize',
            'emptyDescription' => 'No files are currently pending 1st Authorization.',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $currentUser = Auth::user();
            $this->selectedBatches = $this->getBatches()
                ->filter(fn ($batch) => $batch->canUserAuthorize($currentUser))
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->values()
                ->toArray();
        } else {
            $this->selectedBatches = [];
        }
    }

    public function updatedSelectedBatches(): void
    {
        $currentUser = Auth::user();
        $selectableBatches = $this->getBatches()->filter(fn ($b) => $b->canUserAuthorize($currentUser));
        $selectableIds = $selectableBatches->pluck('id')->map(fn ($id) => (string) $id)->toArray();

        // Enforce segregation of duties: drop any unselectable batch from selection
        $this->selectedBatches = array_values(array_intersect($this->selectedBatches, $selectableIds));

        $this->selectAll = !empty($selectableIds) && count(array_intersect($selectableIds, $this->selectedBatches)) === count($selectableIds);
    }

    public function getBatches(): Collection
    {
        // Automatically revert any failed batch files back to Checker
        BkashTransactionBatch::revertFailedBatchesToChecker();

        $query = BkashTransactionBatch::query()
            ->where('status_id', BkashTransaction::STATUS_CHECKED);

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

        // Exclude failed batches from authorization queue
        $failedBatchIds = \App\Models\BkashFailedTransaction::whereNotNull('batch_id')->pluck('batch_id')->unique()->all();
        $failedFileNames = \App\Models\BkashFailedTransaction::whereNotNull('file_name')->pluck('file_name')->unique()->all();

        if (!empty($failedBatchIds)) {
            $query->whereNotIn('id', $failedBatchIds);
        }
        if (!empty($failedFileNames)) {
            $query->whereNotIn('file_name', $failedFileNames);
        }

        $batches = $query->orderBy('created_at', 'desc')->get();
        $batches = $batches->filter(fn ($b) => !$b->hasFailedTransactions())->values();

        $fileNames = BkashTransaction::where('status_id', BkashTransaction::STATUS_CHECKED)
            ->pluck('file_name')
            ->unique()
            ->filter();

        foreach ($fileNames as $fn) {
            if (in_array($fn, $failedFileNames)) {
                continue;
            }

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
                        'status_id'        => BkashTransaction::STATUS_CHECKED,
                        'created_at'       => Carbon::now(),
                    ]
                );
                if (!$newBatch->hasFailedTransactions()) {
                    $batches->push($newBatch);
                }
            }
        }

        return $batches;
    }

    public function authorizeSelectedBatches(): void
    {
        if (empty($this->selectedBatches)) {
            \Filament\Notifications\Notification::make()
                ->title('No Batch Selected')
                ->body('Please select at least one batch file to authorize.')
                ->warning()
                ->send();
            return;
        }

        $currentUser = Auth::user();
        $currentUserId = $currentUser->id ?? null;
        $currentUserName = $currentUser->name ?? '1st Authorizer';
        $now = Carbon::now();

        $batches = BkashTransactionBatch::whereIn('id', $this->selectedBatches)->get();
        $totalAuthorized = 0;

        foreach ($batches as $batch) {
            // Guard: Failed transaction files can NEVER be authorized. Automatically revert to checker!
            if ($batch->hasFailedTransactions()) {
                BkashTransactionBatch::revertFailedBatchesToChecker($batch->id, $batch->file_name);
                \Filament\Notifications\Notification::make()
                    ->title('Authorization Blocked (Failed Transactions)')
                    ->body("File '{$batch->file_name}' contains failed transactions and cannot be authorized. It has been automatically returned to Checker - Verify Files.")
                    ->danger()
                    ->persistent()
                    ->send();
                continue;
            }
            $txns = BkashTransaction::where(function (Builder $q) use ($batch) {
                $q->where('batch_id', $batch->id)
                  ->orWhere('file_name', $batch->file_name);
            })->where('status_id', BkashTransaction::STATUS_CHECKED)->get();

            // Segregation of duties: 1st Authorizer != Checker (Strictly enforced: no user can self-authorize)
            if (!$batch->canUserAuthorize($currentUser, $txns)) {
                \Filament\Notifications\Notification::make()
                    ->title('Authorization Blocked (Segregation of Duties)')
                    ->body("File '{$batch->file_name}' was verified/checked by you. 1st authorization must come from a different user.")
                    ->danger()
                    ->persistent()
                    ->send();
                continue;
            }

            foreach ($txns as $txn) {
                $txn->update([
                    'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
                    'approved_by_1'    => $currentUserName,
                    'approved_by_1_id' => $currentUserId,
                    'approved_at_1'    => $now,
                ]);
                $totalAuthorized++;
            }

            $batch->update(['status_id' => BkashTransaction::STATUS_AUTH_1_APPROVED]);

            if ($txns->isNotEmpty()) {
                NotificationService::dispatchStage3(
                    $batch->file_name,
                    $txns->count(),
                    (float)$txns->sum('amount'),
                    $currentUserName,
                    $currentUser
                );
            }
        }

        $this->selectedBatches = [];
        $this->selectAll = false;

        if ($totalAuthorized > 0) {
            \Filament\Notifications\Notification::make()
                ->title('1st Authorization Completed')
                ->body("Successfully authorized {$totalAuthorized} transaction(s). Forwarded to 2nd Authorizer.")
                ->success()
                ->send();
        }
    }
}