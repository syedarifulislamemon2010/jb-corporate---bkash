<?php

namespace App\Jobs;

use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\BkashFailedTransaction;
use App\Services\BkashExcelParserService;
use App\Services\NotificationService;
use App\Services\SftpFileTransferService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessBkashFileJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        protected string $localFilePath,
        protected string $channelType,
        protected string $createdBy = 'SFTP_CRON'
    ) {}

    public function handle(): void
    {
        $fileName = basename($this->localFilePath);

        Log::info("ProcessBkashFileJob: Starting {$fileName} [{$this->channelType}]");

        if (!is_file($this->localFilePath)) {
            Log::error("ProcessBkashFileJob: File not found: {$this->localFilePath}");
            return;
        }

        // Duplicate file check
        if (BkashTransactionBatch::where('file_name', $fileName)->exists()) {
            Log::warning("ProcessBkashFileJob: Duplicate file skipped: {$fileName}");
            // Still archive the file
            (new SftpFileTransferService())->archiveLocalFile($this->localFilePath);
            return;
        }

        // Parse file
        $importRows = BkashExcelParserService::parseFile($this->localFilePath);

        if (empty($importRows)) {
            Log::warning("ProcessBkashFileJob: Empty file: {$fileName}");
            return;
        }

        $sha256 = hash_file('sha256', $this->localFilePath);

        $headerRow = array_values((array) ($importRows[0] ?? []));

        // Step 1: File-Level Validation - Single Debit Account Rule
        $fileLevelValidation = BkashExcelParserService::validateFileLevelDebitAccounts($importRows, $headerRow);

        if (!$fileLevelValidation['is_valid']) {
            $errorMsg = $fileLevelValidation['error_message'];
            Log::error("ProcessBkashFileJob: File {$fileName} rejected. {$errorMsg}");

            // Create batch marked as rejected
            $batch = BkashTransactionBatch::create([
                'file_name'        => $fileName,
                'transaction_type' => $this->channelType,
                'sha256'           => $sha256,
                'total_data'       => 0,
                'total_amount'     => 0.00,
                'status_id'        => BkashTransaction::STATUS_REJECTED,
                'created_by'       => $this->createdBy,
                'create_date'      => Carbon::now(),
            ]);

            // Create failed transaction record so checker/admin sees the file-level error on UI
            BkashFailedTransaction::create([
                'batch_id'          => $batch->id,
                'file_name'         => $fileName,
                'row_number'        => 0,
                'transaction_type'  => $this->channelType,
                'reference_id'      => 'FILE_LEVEL_ERROR',
                'debit_account_no'  => null,
                'credit_account_no' => implode(', ', $fileLevelValidation['debit_accounts']),
                'amount'            => 0.00,
                'failure_code'      => 'MULTI_DEBIT_ACC',
                'reject_reason'     => $errorMsg,
            ]);

            // Archive the processed file locally
            (new SftpFileTransferService())->archiveLocalFile($this->localFilePath);

            return;
        }

        // Create batch record for valid single debit account file
        $batch = BkashTransactionBatch::create([
            'file_name'        => $fileName,
            'transaction_type' => $this->channelType,
            'sha256'           => $sha256,
            'total_data'       => 0,
            'total_amount'     => 0.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
            'created_by'       => $this->createdBy,
            'create_date'      => Carbon::now(),
        ]);

        $validCount = 0;
        $totalAmount = 0.00;
        $detectedDebitAccount = null;

        // Pre-fetch existing txn_ids to avoid N+1 queries
        $allTxnIds = [];
        foreach ($importRows as $idx => $r) {
            if ($idx === 0) continue;
            $rowArr = array_values((array) $r);
            $m = BkashExcelParserService::mapRowData($headerRow, $rowArr, $this->channelType);
            if (!empty($m['txn_id'])) {
                $allTxnIds[] = $m['txn_id'];
            }
        }
        BkashExcelParserService::prefetchExistingTxnIds($allTxnIds);

        DB::transaction(function () use ($importRows, $fileName, $batch, &$validCount, &$totalAmount, $headerRow, &$detectedDebitAccount) {
            foreach ($importRows as $index => $row) {
                if ($index === 0 || empty(array_filter((array) $row))) {
                continue;
            }

            $rowArr = array_values((array) $row);
            $mapped = BkashExcelParserService::mapRowData($headerRow, $rowArr, $this->channelType);

            // Validate row
            $validation = BkashExcelParserService::validateRow(
                $mapped,
                $this->channelType,
                $detectedDebitAccount
            );

            $refId        = BkashExcelParserService::cleanString($mapped['reference_id'] ?? null, 255);
            $bbRef        = BkashExcelParserService::cleanString($mapped['bb_reference_number'] ?? null, 100);
            $accountName  = BkashExcelParserService::cleanString($mapped['debit_account_title'] ?? null, 150);
            $accountNo    = BkashExcelParserService::cleanString($mapped['debit_account_no'] ?? null, 100);
            $amount       = (float) ($mapped['amount'] ?? 0);
            $routingNo    = BkashExcelParserService::cleanString($mapped['credit_routing'] ?? $mapped['debit_routing'] ?? null, 20);
            $bankName     = BkashExcelParserService::cleanString($mapped['credit_bank'] ?? null, 255);
            $branchName   = BkashExcelParserService::cleanString($mapped['branch_name'] ?? null, 255);
            $debitAccount = BkashExcelParserService::cleanString($mapped['credit_account_no'] ?? null, 100);
            $txnId        = BkashExcelParserService::cleanString($mapped['txn_id'] ?? null, 100) ?: (string) Str::uuid();
            $createDate   = $mapped['create_date'] ?? null;

            if ($validation['is_valid']) {
                $validCount++;
                $totalAmount += $amount;

                $parsedDate = $createDate ? Carbon::parse($createDate) : Carbon::now();
                $valueDate  = \App\Helper\ValueDateHelper::resolve($parsedDate)->toDateString();

                BkashTransaction::create([
                    'batch_id'             => $batch->id,
                    'file_name'            => $fileName,
                    'row_sequence'         => $index,
                    'transaction_type'     => $this->channelType,
                    'reference_id'         => Str::limit($refId, 255, ''),
                    'bb_reference_number'  => $bbRef ? Str::limit($bbRef, 100, '') : null,
                    'txn_id'               => Str::limit($txnId, 100, ''),
                    'debit_account_title'  => $accountName ? Str::limit($accountName, 150, '') : null,
                    'debit_account_no'     => $accountNo ? Str::limit($accountNo, 100, '') : null,
                    'debit_routing'        => $routingNo ? Str::limit($routingNo, 20, '') : null,
                    'credit_account_no'    => $debitAccount ? Str::limit($debitAccount, 100, '') : null,
                    'credit_routing'       => $routingNo ? Str::limit($routingNo, 20, '') : null,
                    'credit_bank'          => $bankName ? Str::limit($bankName, 255, '') : null,
                    'amount'               => $amount,
                    'status_id'            => BkashTransaction::STATUS_PENDING_CHECKER,
                    'created_by'           => $this->createdBy,
                    'create_date'          => $parsedDate,
                    'value_date'           => $valueDate,
                ]);
            } else {
                BkashFailedTransaction::create([
                    'batch_id'          => $batch->id,
                    'file_name'         => $fileName,
                    'row_number'        => $index + 1,
                    'transaction_type'  => $this->channelType,
                    'reference_id'      => $refId ? Str::limit($refId, 100, '') : 'N/A',
                    'debit_account_no'  => $accountNo ? Str::limit($accountNo, 34, '') : null,
                    'credit_account_no' => $debitAccount ? Str::limit($debitAccount, 34, '') : null,
                    'amount'            => $amount,
                    'failure_code'      => $validation['failure_code'] ?? 'VALIDATION_FAILED',
                    'reject_reason'     => implode(', ', $validation['errors']),
                ]);
            }
        }
        });

        // Update batch totals
        $batch->update([
            'total_data'   => $validCount,
            'total_amount' => $totalAmount,
        ]);

        // Send notifications
        if ($validCount > 0) {
            $uploaderUser = is_numeric($this->createdBy)
                ? \App\Models\User::find((int) $this->createdBy)
                : \App\Models\User::where('name', $this->createdBy)->first();

            NotificationService::dispatchStage1($fileName, $validCount, $totalAmount, $uploaderUser);
        }

        // Archive the processed file locally
        $totalProcessedRows = max(0, count($importRows) - 1);
        $failedCount = max(0, $totalProcessedRows - $validCount);
        Log::info("ProcessBkashFileJob: Completed {$fileName} — {$validCount} valid, {$failedCount} failed.");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessBkashFileJob FAILED for {$this->localFilePath}: " . $exception->getMessage());
        
        // Mark batch as failed if it was created
        $fileName = basename($this->localFilePath);
        BkashTransactionBatch::where('file_name', $fileName)
            ->where('status_id', BkashTransaction::STATUS_PENDING_CHECKER)
            ->update(['status_id' => 9000]); // REJECTED/FAILED
    }
}
