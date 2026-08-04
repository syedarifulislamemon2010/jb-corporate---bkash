<?php

namespace App\Console\Commands;

use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\BkashFailedTransaction;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FetchSftpFilesCommand extends Command
{
    protected $signature = 'sftp:fetch-bkash-files';

    protected $description = 'Scans SFTP folders (Account-to-Account, BEFTN, RTGS) every 15 minutes, validates file uniqueness, single debit account rule, duplicate txn_id, RTGS limit, and ingests transactions.';

    public function handle(): int
    {
        $this->info('Starting SFTP file scanner...');

        $folders = [
            'Account-to-Account' => 'A2A',
            'BEFTN'              => 'BEFTN',
            'RTGS'               => 'RTGS',
        ];

        $validNamingPatterns = [
            'A2A'   => '/^JANATA_BANK_\d{4}_\d{2}_\d{2}_\d+Slot\d+\.(xlsx|xls)$/i',
            'BEFTN' => '/^BEFTN_JANATA_BANK_\d{4}_\d{2}_\d{2}_\d+Slot\d+\.(xlsx|xls)$/i',
            'RTGS'  => '/^RTGS_JANATA_BANK_\d{4}_\d{2}_\d{2}_\d+Slot\d+\.(xlsx|xls)$/i',
        ];

        $allowedDebitAccounts = ['0100202707747', '0100224107522'];

        foreach ($folders as $folderName => $channelType) {
            $sftpDisk = Storage::disk('local'); // Fallback or sftp disk
            $files = glob(storage_path("app/sftp/{$folderName}/*.{xlsx,xls}"), GLOB_BRACE);

            if (empty($files)) {
                continue;
            }

            foreach ($files as $filePath) {
                $fileName = basename($filePath);

                // File Uniqueness Check
                if (BkashTransactionBatch::where('file_name', $fileName)->exists()) {
                    $this->warn("Skipping duplicate file: {$fileName}");
                    Log::warning("SFTP Fetch: Duplicate file skipped {$fileName}");
                    continue;
                }

                $sha256 = hash_file('sha256', $filePath);
                $importRows = [];

                try {
                    $spreadsheet = IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $importRows = $sheet->toArray(null, true, true, false);
                } catch (\Throwable $e) {
                    Log::error("Failed to parse SFTP file {$fileName}: " . $e->getMessage());
                    continue;
                }

                if (empty($importRows)) {
                    continue;
                }

                $batch = BkashTransactionBatch::create([
                    'file_name'        => $fileName,
                    'transaction_type' => $channelType,
                    'sha256'           => $sha256,
                    'total_data'       => 0,
                    'total_amount'     => 0.00,
                    'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
                    'created_by'       => 'SFTP_AUTO_CRON',
                    'create_date'      => Carbon::now(),
                ]);

                $validCount = 0;
                $totalAmount = 0.00;
                $headerRow = array_values((array)($importRows[0] ?? []));

                // Inspect rows for single debit account rule
                $detectedDebitAccount = null;

                foreach ($importRows as $index => $row) {
                    if ($index === 0 || empty(array_filter((array)$row))) {
                        continue;
                    }

                    $rowArr = array_values((array)$row);
                    $mapped = $this->mapRowData($headerRow, $rowArr);

                    $refId       = $mapped['reference_id'] ?? null;
                    $accountName = $mapped['debit_account_title'] ?? null;
                    $accountNo   = $mapped['debit_account_no'] ?? null;
                    $amount      = (float)($mapped['amount'] ?? 0);
                    $routingNo   = $mapped['debit_routing'] ?? null;
                    $bankName    = $mapped['credit_routing'] ?? null;
                    $branchName  = $mapped['credit_bank'] ?? null;
                    $debitAcc    = $mapped['credit_account_no'] ?? null;
                    $txnId       = $mapped['txn_id'] ?? (string)Str::uuid();

                    // Single Debit Account Rule & TCSA/Operational Validation
                    if ($debitAcc) {
                        if ($detectedDebitAccount === null) {
                            $detectedDebitAccount = $debitAcc;
                        } elseif ($detectedDebitAccount !== $debitAcc) {
                            // Multiple debit accounts in single file violated
                            BkashFailedTransaction::create([
                                'batch_id'         => $batch->id,
                                'file_name'        => $fileName,
                                'row_number'       => $index + 1,
                                'transaction_type' => $channelType,
                                'reference_id'     => $refId ?? 'N/A',
                                'credit_account_no'=> $debitAcc,
                                'amount'           => $amount,
                                'failure_code'     => 'MULTI_DEBIT_ACC',
                                'reject_reason'    => 'Single Debit Account Rule violated inside file.',
                            ]);
                            continue;
                        }

                        if (!in_array($debitAcc, $allowedDebitAccounts)) {
                            BkashFailedTransaction::create([
                                'batch_id'         => $batch->id,
                                'file_name'        => $fileName,
                                'row_number'       => $index + 1,
                                'transaction_type' => $channelType,
                                'reference_id'     => $refId ?? 'N/A',
                                'credit_account_no'=> $debitAcc,
                                'amount'           => $amount,
                                'failure_code'     => 'INVALID_DEBIT_ACC',
                                'reject_reason'    => "Debit account {$debitAcc} is neither TCSA nor Operational Account.",
                            ]);
                            continue;
                        }
                    }

                    // Global Duplicate Txn ID check
                    if (BkashTransaction::where('txn_id', $txnId)->exists()) {
                        BkashFailedTransaction::create([
                            'batch_id'         => $batch->id,
                            'file_name'        => $fileName,
                            'row_number'       => $index + 1,
                            'transaction_type' => $channelType,
                            'reference_id'     => $refId ?? 'N/A',
                            'credit_account_no'=> $debitAcc,
                            'amount'           => $amount,
                            'failure_code'     => 'DUPLICATE_TXN_ID',
                            'reject_reason'    => "Global Duplicate Transaction ID {$txnId} blocked.",
                        ]);
                        continue;
                    }

                    // RTGS Limit Check >= 100,000 BDT
                    if ($channelType === 'RTGS' && $amount < 100000) {
                        BkashFailedTransaction::create([
                            'batch_id'         => $batch->id,
                            'file_name'        => $fileName,
                            'row_number'       => $index + 1,
                            'transaction_type' => $channelType,
                            'reference_id'     => $refId ?? 'N/A',
                            'credit_account_no'=> $debitAcc,
                            'amount'           => $amount,
                            'failure_code'     => 'RTGS_MIN_LIMIT',
                            'reject_reason'    => 'RTGS amount must be greater than or equal to 100,000 BDT.',
                        ]);
                        continue;
                    }

                    if ($refId && $amount > 0) {
                        $validCount++;
                        $totalAmount += $amount;

                        BkashTransaction::create([
                            'batch_id'             => $batch->id,
                            'file_name'            => $fileName,
                            'transaction_type'     => $channelType,
                            'reference_id'         => Str::limit($refId, 255, ''),
                            'txn_id'               => Str::limit($txnId, 100, ''),
                            'debit_account_title'  => $accountName ? Str::limit($accountName, 150, '') : null,
                            'debit_account_no'     => $accountNo ? Str::limit($accountNo, 100, '') : null,
                            'debit_routing'        => $routingNo ? Str::limit($routingNo, 20, '') : null,
                            'credit_account_no'    => $debitAcc ? Str::limit($debitAcc, 100, '') : null,
                            'credit_routing'       => $bankName ? Str::limit($bankName, 100, '') : null,
                            'credit_bank'          => $branchName ? Str::limit($branchName, 255, '') : null,
                            'amount'               => $amount,
                            'status_id'            => BkashTransaction::STATUS_PENDING_CHECKER,
                            'created_by'           => 'SFTP_CRON',
                            'create_date'          => Carbon::now(),
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
            }
        }

        $this->info('SFTP file scanner completed.');
        return Command::SUCCESS;
    }

    private function mapRowData(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($headers as $colIndex => $headerName) {
            $rawHeader = trim((string)$headerName);
            $cleanHeader = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawHeader));
            $val = $row[$colIndex] ?? null;

            if ($cleanHeader === '' || $cleanHeader === 'sl') {
                continue;
            }

            if (in_array($cleanHeader, ['ref', 'refno', 'reference', 'referenceid', 'refid'])) {
                $mapped['reference_id'] = trim((string)$val);
            } elseif (in_array($cleanHeader, ['acname', 'bankaccountname', 'benename', 'beneficiaryname', 'accountname'])) {
                $mapped['debit_account_title'] = trim((string)$val);
            } elseif (in_array($cleanHeader, ['accountno', 'beneficiaryacno', 'bankaccountnumber', 'bankaccountno'])) {
                $mapped['debit_account_no'] = trim((string)$val);
            } elseif (in_array($cleanHeader, ['amount', 'amountbdt', 'amountintaka'])) {
                $cleanVal = preg_replace('/[^0-9.]/', '', str_replace(',', '', (string)$val));
                $mapped['amount'] = (float)$cleanVal;
            } elseif (in_array($cleanHeader, ['routingcode', 'routingnumber', 'beneroutingno', 'routingno'])) {
                $mapped['debit_routing'] = trim((string)$val);
            } elseif (in_array($cleanHeader, ['bankname', 'benebankname', 'bankbranchname'])) {
                $mapped['credit_routing'] = trim((string)$val);
            } elseif (in_array($cleanHeader, ['branchname', 'benebranchname'])) {
                $mapped['credit_bank'] = trim((string)$val);
            } elseif (in_array($cleanHeader, ['debitaccount', 'debitaccountno'])) {
                $mapped['credit_account_no'] = trim((string)$val);
            } elseif (in_array($cleanHeader, ['txnid', 'transactionid'])) {
                $mapped['txn_id'] = trim((string)$val);
            }
        }

        return $mapped;
    }
}
