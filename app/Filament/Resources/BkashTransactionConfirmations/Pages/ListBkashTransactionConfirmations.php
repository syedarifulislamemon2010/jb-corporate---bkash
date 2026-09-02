<?php

namespace App\Filament\Resources\BkashTransactionConfirmations\Pages;

use App\Filament\Resources\BkashTransactionConfirmations\BkashTransactionConfirmationResource;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Jobs\ExecuteCbsSettlementJob;
use App\Services\NotificationService;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Livewire\Attributes\Url;

class ListBkashTransactionConfirmations extends ListRecords
{
    protected static string $resource = BkashTransactionConfirmationResource::class;

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
            'actionLabel'      => 'Approve & Settle Selected Batch Files',
            'actionMethod'     => 'confirmSelectedBatches',
            'emptyHeading'     => 'Nothing to Confirm',
            'emptyDescription' => 'No files are currently pending 2nd / Final Confirmation.',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
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
            ->where('status_id', BkashTransaction::STATUS_AUTH_1_APPROVED);

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

        $fileNames = BkashTransaction::where('status_id', BkashTransaction::STATUS_AUTH_1_APPROVED)
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
                        'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
                        'created_at'       => Carbon::now(),
                    ]
                );
                $batches->push($newBatch);
            }
        }

        return $batches;
    }

    public function confirmSelectedBatches(): void
    {
        if (empty($this->selectedBatches)) {
            \Filament\Notifications\Notification::make()
                ->title('No Batch Selected')
                ->body('Please select at least one batch file to confirm.')
                ->warning()
                ->send();
            return;
        }

        $currentUser = Auth::user();
        $currentUserId = $currentUser->id ?? null;
        $currentUserName = $currentUser->name ?? '2nd Authorizer';
        $now = Carbon::now();

        $batches = BkashTransactionBatch::whereIn('id', $this->selectedBatches)->get();
        $totalConfirmed = 0;
        $allTxnIds = [];

        foreach ($batches as $batch) {
            $txns = BkashTransaction::where(function (Builder $q) use ($batch) {
                $q->where('batch_id', $batch->id)
                  ->orWhere('file_name', $batch->file_name);
            })->where('status_id', BkashTransaction::STATUS_AUTH_1_APPROVED)->get();

            // 3-Person Segregation of Duties Check
            $ineligible = $txns->filter(function ($t) use ($currentUserId, $currentUserName) {
                $isChecker = ($currentUserId && $t->checked_by_id === $currentUserId) ||
                             ($t->checked_by && $t->checked_by === $currentUserName);
                $isAuth1   = ($currentUserId && $t->approved_by_1_id === $currentUserId) ||
                             ($t->approved_by_1 && $t->approved_by_1 === $currentUserName);
                return $isChecker || $isAuth1;
            });

            if ($ineligible->isNotEmpty() && !$currentUser->hasRole('super_admin')) {
                \Filament\Notifications\Notification::make()
                    ->title('Confirmation Blocked (Segregation of Duties)')
                    ->body("File '{$batch->file_name}' was checked or 1st-authorized by you. Final confirmation must come from a third distinct user.")
                    ->danger()
                    ->persistent()
                    ->send();
                continue;
            }

            foreach ($txns as $txn) {
                $txn->update([
                    'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
                    'approved_by_2'    => $currentUserName,
                    'approved_by_2_id' => $currentUserId,
                    'approved_at_2'    => $now,
                    'confirmed_by'     => $currentUserName,
                    'confirmed_at'     => $now,
                ]);
                $totalConfirmed++;
                $allTxnIds[] = $txn->id;
            }

            $batch->update(['status_id' => BkashTransaction::STATUS_FINAL_AUTHORIZED]);

            if ($txns->isNotEmpty()) {
                NotificationService::dispatchStage4(
                    $batch->file_name,
                    $txns->count(),
                    (float)$txns->sum('amount'),
                    $currentUserName,
                    $currentUser
                );
            }
        }

        if (!empty($allTxnIds)) {
            ExecuteCbsSettlementJob::dispatch($allTxnIds, $currentUserName);
        }

        $this->selectedBatches = [];
        $this->selectAll = false;

        if ($totalConfirmed > 0) {
            \Filament\Notifications\Notification::make()
                ->title('Final Confirmation Completed')
                ->body("Successfully confirmed {$totalConfirmed} transaction(s). Dispatched for CBS settlement.")
                ->success()
                ->send();
        }
    }
}