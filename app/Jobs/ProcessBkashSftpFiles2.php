<?php

namespace App\Jobs;

use App\Models\Branch;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\BkashFailedTransaction;
use App\Services\NotificationService;
use Encore\Admin\Facades\Admin;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProcessBkashSftpFiles2 implements ShouldQueue
{
    use Queueable;

    private const DEBIT_ACCOUNTS_WHITELIST = ['0100202707747', '0100224107522'];

    public function __construct()
    {
    }

    public function handle(): void
    {
        Log::info("Starting bKash SFTP File Ingestion Scanner...");
//        $folders = [
//            'Account to-Account' => 'A2A',
//            'a2a'                => 'A2A',
//            'BEFTN'              => 'BEFTN',
//            'beftn'              => 'BEFTN',
//            'RTGS'               => 'RTGS',
//            'rtgs'               => 'RTGS',
//        ];
//
//        foreach ($folders as $folderName => $channelType) {
//            try {
//                $files = Storage::disk('public')->files("Bkash_Files/{$folderName}");
//
//                if (empty($files) && Storage::disk('public')->exists("Bkash_Files")) {
//                    $files = Storage::disk('public')->files("Bkash_Files");
//                }
//
//                foreach ($files as $fileKey => $filePath) {
//                    $this->processSingleFile($filePath, $channelType);
//                }
//            } catch (\Exception $e) {
//                Log::error("Error scanning SFTP folder {$folderName}: " . $e->getMessage());
//            }
//        }

        $file_list = Storage::disk('bkash_sftp')->files("/var/www/html/beftn_rtgs_bach/storage/app/public/bkash/");
        $dir = storage_path("app/public/BKASH");
        foreach ($file_list as $key => $value) {
            if (Storage::disk('public')->getSize($value) > 0) {
                $filename = str_replace('/var/www/html/beftn_rtgs_bach/storage/app/public/bkash/', '', $value);
                $topath = 'BKASH/' . $filename;
                Storage::disk('public')->put($topath, Storage::disk('bkash_sftp')->get($value));
                $path = '/var/www/html/beftn_rtgs_bach/storage/app/public/bkash/' . $filename;
                $path2 = '/var/www/html/beftn_rtgs_bach/storage/app/public/bkash_uploaded/' . $filename;
                if (!is_file($path2)) {
                    Storage::disk('bkash_sftp')->move($path, $path2);
                }
            }
        }
        if (!is_readable($dir)) {
            throw new \Exception('File Not Readable....');
        }
        if (!$this->is_dir_empty($dir)) {
            $files = \File::allFiles($dir);

            foreach ($files as $file) {
                $this->processSingleFile($file);
            }
        }
    }

    public function is_dir_empty($dir)
    {
        if (!is_readable($dir)) return NULL;
        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                return FALSE;
            }
        }
        return TRUE;
    }

    private function processSingleFile($file): void
    {
        $datas = Excel::toCollection(collect([]), $file)->toArray();
        $importData_arr = array_shift($datas);
        $filename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();
        $filepath = storage_path("app/public/BKASH/" . str_replace('-', '', Carbon::now()->toDateString()) . '/');
        if (!is_dir($filepath)) {
            mkdir($filepath);
        }
        $file->move($filepath, $filename . $extension);

        $type = explode('_', $filename);
        $totalData = 0;

        $totalAmount = 0;

        $batch = BkashTransactionBatch::create([
            'file_name' => $filename,
            'transaction_type' => $type,
            'total_data' => $totalData,
            'total_amount' => $totalAmount,
            'status_id' => 1000,
            'created_by' => 'SYSTEM',
            'create_date' => Carbon::now(),
        ]);
        foreach ($importData_arr as $index => $importData) {
            if ($index === 0) {
                continue;
            }

            if (empty(array_filter($importData))) {
                continue;
            }

            if ($type = 'BEFTN') {
                $ref = $importData[8];
                $debitAcc = $importData[7];
                $creditAcc = $importData[2];
                $creditAccTitle = $importData[1];
                $creditRouting = $importData[4];
                $amount = (float)$importData[3];
            } else if ($type = 'RTGS') {
                $ref = $importData[8];
                $debitAcc = $importData[7];
                $creditAcc = $importData[3];
                $creditAccTitle = $importData[2];
                $creditRouting = $importData[5];
                $amount = (float)$importData[6];
            } else if ($type = 'JANATA') {
                $ref = $importData[5];
                $debitAcc = $importData[4];
                $creditAcc = $importData[2];
                $creditAccTitle = $importData[1];
                $creditRouting = null;
                $amount = (float)$importData[3];
            }

            $rowValidation = $this->validateRowData($importData, $type);

            if ($rowValidation['is_valid']) {
                $validCount++;
                $totalAmount += $amount;
                BkashTransaction::create([
                    'batch_id' => $batch->id,
                    'file_name' => $filename,
                    'transaction_type' => $type,
                    'reference_id' => $ref,
                    'debit_account_no' => $debitAcc,
//                'debit_account_title'  => $rowValidation['data']['debit_account_title'],
                    'credit_account_no' => $creditAcc,
                    'credit_account_title' => $creditAccTitle,
                    'credit_routing' => $creditRouting,
                    'credit_bank' => substr($creditRouting, 0, 3),
                    'amount' => $amount,
                    'status_id' => BkashTransaction::STATUS_PENDING_CHECKER,
                    'created_by' => 'SYSTEM',
                    'create_date' => Carbon::now(),
                ]);
            } else {
                BkashFailedTransaction::create([
                    'batch_id' => $batch->id,
                    'file_name' => $filename,
                    'row_number' => $index + 1,
                    'transaction_type' => $type,
                    'reference_id' => $ref ?? null,
                    'debit_account_no' => $debitAcc ?? null,
                    'credit_account_no' => $creditAcc ?? null,
                    'amount' => (float)($amount ?? 0),
                    'failure_code' => 'VALIDATION_FAILED',
                    'reject_reason' => implode(', ', $rowValidation['errors']),
                ]);
            }
        }

        $batch->update([
            'total_data' => $validCount,
            'total_amount' => $totalAmount,
        ]);

        if ($validCount > 0) {
            NotificationService::dispatchStage1($filename, $validCount, $totalAmount);
        }


        Log::info("Completed Processing File {$filename}: {$validCount} valid transactions imported.");
    }

    private function validateRowData(array $row, string $channelType): array
    {
        $errors = [];

        $refId = trim((string)($row[0] ?? ''));
        $beneName = trim((string)($row[1] ?? ''));
        $beneAccount = trim((string)($row[2] ?? ''));
        $amount = (float)($row[3] ?? 0);
        $routingNo = trim((string)($row[4] ?? ''));
        $bankName = trim((string)($row[5] ?? ''));
        $debitAccount = trim((string)($row[6] ?? $row[4] ?? ''));
        $txnId = trim((string)($row[7] ?? $row[8] ?? $refId));

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
            'errors' => $errors,
            'data' => [
                'reference_id' => $refId,
                'txn_id' => $txnId ?: (string)Str::uuid(),
                'debit_account_no' => $debitAccount,
                'debit_account_title' => 'bKash Account',
                'credit_account_no' => $beneAccount,
                'credit_account_title' => $beneName,
                'credit_routing' => $routingNo,
                'credit_bank' => $bankName,
                'amount' => $amount,
            ],
        ];
    }
}
