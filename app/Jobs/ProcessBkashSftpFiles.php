<?php

namespace App\Jobs;

use App\Models\Branch;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\BkashFailedTransaction;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProcessBkashSftpFiles implements ShouldQueue
{
    use Queueable;

    private const DEBIT_ACCOUNTS_WHITELIST = ['0100202707747', '0100224107522'];

    public function __construct()
    {
    }

    public function handle(): void
    {
        Log::info("Starting bKash SFTP File Ingestion Scanner...");

        $folders = [
            'Account to-Account' => 'A2A',
            'a2a'                => 'A2A',
            'BEFTN'              => 'BEFTN',
            'beftn'              => 'BEFTN',
            'RTGS'               => 'RTGS',
            'rtgs'               => 'RTGS',
        ];

        foreach ($folders as $folderName => $channelType) {
            try {
                $files = Storage::disk('public')->files("Bkash_Files/{$folderName}");
                
                if (empty($files) && Storage::disk('public')->exists("Bkash_Files")) {
                    $files = Storage::disk('public')->files("Bkash_Files");
                }

                foreach ($files as $fileKey => $filePath) {
                    $this->processSingleFile($filePath, $channelType);
                }
            } catch (\Exception $e) {
                Log::error("Error scanning SFTP folder {$folderName}: " . $e->getMessage());
            }
        }
    }

    private function processSingleFile(string $filePath, string $channelType): void
    {
        $fileName = basename($filePath);
        $fullLocalPath = storage_path("app/public/" . $filePath);

        if (!File::exists($fullLocalPath)) {
            return;
        }

        if (BkashTransactionBatch::where('file_name', $fileName)->exists()) {
            Log::warning("Skipping duplicate file: {$fileName}");
            return;
        }

        if (!$this->validateFileNamePattern($fileName, $channelType)) {
            Log::error("Invalid filename pattern for channel {$channelType}: {$fileName}");
            return;
        }

        Log::info("Processing bKash File: {$fileName} [{$channelType}]");

        $sheets = Excel::toCollection(collect([]), $fullLocalPath)->toArray();
        $importRows = array_shift($sheets);

        if (empty($importRows)) {
            Log::warning("Empty file encountered: {$fileName}");
            return;
        }

        $sha256 = hash_file('sha256', $fullLocalPath);

        $batch = BkashTransactionBatch::create([
            'file_name'        => $fileName,
            'transaction_type' => $channelType,
            'sha256'           => $sha256,
            'total_data'       => 0,
            'total_amount'     => 0.00,
            'status_id'        => 1000,
            'created_by'       => 'SYSTEM',
            'create_date'      => Carbon::now(),
        ]);

        $validCount = 0;
        $totalAmount = 0.0;

        foreach ($importRows as $index => $row) {
            if ($index === 0) {
                continue;
            }

            if (empty(array_filter($row))) {
                continue;
            }

            $rowValidation = $this->validateRowData($row, $channelType);

            if ($rowValidation['is_valid']) {
                $validCount++;
                $amount = (float)($rowValidation['data']['amount']);
                $totalAmount += $amount;

                BkashTransaction::create([
                    'batch_id'             => $batch->id,
                    'file_name'            => $fileName,
                    'transaction_type'     => $channelType,
                    'reference_id'         => $rowValidation['data']['reference_id'],
                    'txn_id'               => $rowValidation['data']['txn_id'],
                    'debit_account_no'     => $rowValidation['data']['debit_account_no'],
                    'debit_account_title'  => $rowValidation['data']['debit_account_title'],
                    'credit_account_no'    => $rowValidation['data']['credit_account_no'],
                    'credit_account_title' => $rowValidation['data']['credit_account_title'],
                    'credit_routing'       => $rowValidation['data']['credit_routing'],
                    'credit_bank'          => $rowValidation['data']['credit_bank'],
                    'amount'               => $amount,
                    'status_id'            => BkashTransaction::STATUS_PENDING_CHECKER,
                    'created_by'           => 'SYSTEM',
                    'create_date'          => Carbon::now(),
                ]);
            } else {
                BkashFailedTransaction::create([
                    'batch_id'         => $batch->id,
                    'file_name'        => $fileName,
                    'row_number'       => $index + 1,
                    'transaction_type' => $channelType,
                    'reference_id'     => $rowValidation['data']['reference_id'] ?? null,
                    'debit_account_no' => $rowValidation['data']['debit_account_no'] ?? null,
                    'credit_account_no'=> $rowValidation['data']['credit_account_no'] ?? null,
                    'amount'           => (float)($rowValidation['data']['amount'] ?? 0),
                    'failure_code'     => 'VALIDATION_FAILED',
                    'reject_reason'    => implode(', ', $rowValidation['errors']),
                ]);
            }
        }

        $batch->update([
            'total_data'   => $validCount,
            'total_amount' => $totalAmount,
        ]);

        if ($validCount > 0) {
            NotificationService::dispatchStage1($fileName, $validCount, $totalAmount);
        }


        Log::info("Completed Processing File {$fileName}: {$validCount} valid transactions imported.");
    }

    private function validateFileNamePattern(string $fileName, string $channelType): bool
    {
        $patterns = [
            'A2A'   => '/^JANATA_BANK_\d{4}_\d{2}_\d{2}_\d+Slot\d+\.(xls|xlsx)$/i',
            'BEFTN' => '/^BEFTN_JANATA_BANK_\d{4}_\d{2}_\d{2}_\d+Slot\d+\.(xls|xlsx)$/i',
            'RTGS'  => '/^RTGS_JANATA_BANK_\d{4}_\d{2}_\d{2}_\d+Slot\d+\.(xls|xlsx)$/i',
        ];

        return isset($patterns[$channelType]) ? (bool)preg_match($patterns[$channelType], $fileName) : true;
    }

    private function validateRowData(array $row, string $channelType): array
    {
        $errors = [];
        
        $refId        = trim((string)($row[0] ?? ''));
        $beneName     = trim((string)($row[1] ?? ''));
        $beneAccount  = trim((string)($row[2] ?? ''));
        $amount       = (float)($row[3] ?? 0);
        $routingNo    = trim((string)($row[4] ?? ''));
        $bankName     = trim((string)($row[5] ?? ''));
        $debitAccount = trim((string)($row[6] ?? $row[4] ?? ''));
        $txnId        = trim((string)($row[7] ?? $row[8] ?? $refId));

        if (empty($refId)) {
            $errors[] = "Reference ID is missing";
        }

        if (empty($beneAccount)) {
            $errors[] = "Beneficiary account is missing";
        }

        if ($amount <= 0) {
            $errors[] = "Invalid transaction amount";
        }

        if ($channelType === 'RTGS' && $amount < 100000) {
            $errors[] = "RTGS amount must be at least BDT 100,000";
        }

        return [
            'is_valid' => empty($errors),
            'errors'   => $errors,
            'data'     => [
                'reference_id'         => $refId,
                'txn_id'               => $txnId ?: (string)Str::uuid(),
                'debit_account_no'     => $debitAccount,
                'debit_account_title'  => 'bKash Account',
                'credit_account_no'    => $beneAccount,
                'credit_account_title' => $beneName,
                'credit_routing'       => $routingNo,
                'credit_bank'          => $bankName,
                'amount'               => $amount,
            ],
        ];
    }
}