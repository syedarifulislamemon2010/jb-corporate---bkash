<?php

namespace App\Services;

use App\Models\BkashTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BkashExcelParserService
{
    private const DEBIT_ACCOUNTS_WHITELIST = ['0100202707747', '0100224107522'];

    /** @var array Pre-fetched existing txn_ids for batch duplicate checking */
    private static array $existingTxnIds = [];

    /**
     * Pre-fetch existing transaction IDs for batch duplicate checking.
     * Call this before processing rows to avoid N+1 queries.
     */
    public static function prefetchExistingTxnIds(array $txnIds): void
    {
        static::$existingTxnIds = BkashTransaction::whereIn('txn_id', $txnIds)
            ->pluck('txn_id')
            ->toArray();
    }

    private static function getWhitelistedAccounts(): array
    {
        $csv = config('bkash.whitelisted_debit_accounts', '0100202707747,0100224107522');
        return array_map('trim', explode(',', $csv));
    }

    /**
     * Parse Excel/CSV file into array of rows.
     * Supports XLS, XLSX, CSV formats.
     */
    public static function parseFile(string $filePath): array
    {
        $importRows = [];
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($ext, ['xls', 'xlsx'])) {
            try {
                if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    foreach ($sheet->toArray(null, true, true, false) as $row) {
                        $importRows[] = $row;
                    }
                } elseif (class_exists('Maatwebsite\Excel\Facades\Excel')) {
                    $sheets = \Maatwebsite\Excel\Facades\Excel::toCollection(collect([]), $filePath)->toArray();
                    $importRows = array_shift($sheets) ?? [];
                }
            } catch (\Throwable $e) {
                Log::error("Failed to parse file {$filePath}: " . $e->getMessage());
                $importRows = [];
            }
        }

        if (empty($importRows) && $ext === 'csv' && is_file($filePath)) {
            if (($handle = fopen($filePath, 'r')) !== false) {
                while (($data = fgetcsv($handle, 5000, ',')) !== false) {
                    $importRows[] = $data;
                }
                fclose($handle);
            }
        }

        return $importRows;
    }

    /**
     * Auto-detect channel type from filename.
     * Returns: A2A, BEFTN, RTGS, or UNKNOWN
     */
    public static function detectChannelType(string $fileName): string
    {
        $upper = strtoupper($fileName);

        if (str_starts_with($upper, 'RTGS_')) {
            return 'RTGS';
        }
        if (str_starts_with($upper, 'BEFTN_')) {
            return 'BEFTN';
        }
        if (str_starts_with($upper, 'JANATA_BANK_')) {
            return 'A2A';
        }

        return 'UNKNOWN';
    }

    /**
     * Validate file naming convention.
     */
    public static function validateFileName(string $fileName, string $channelType): bool
    {
        $patterns = [
            'A2A'   => '/^JANATA_BANK_\d{4}_\d{2}_\d{2}_\d+Slot\d+\.(xls|xlsx)$/i',
            'BEFTN' => '/^BEFTN_JANATA_BANK_\d{4}_\d{2}_\d{2}_\d+Slot\d+\.(xls|xlsx)$/i',
            'RTGS'  => '/^RTGS_JANATA_BANK_\d{4}_\d{2}_\d{2}_\d+Slot\d+\.(xls|xlsx)$/i',
        ];

        if (!isset($patterns[$channelType])) {
            return true; // Allow unknown patterns
        }

        return (bool) preg_match($patterns[$channelType], $fileName);
    }

    /**
     * Map Excel row data using fuzzy header matching.
     * Returns normalized associative array.
     *
     * @param string $channelType  A2A, BEFTN, or RTGS — used for conditional field mapping.
     */
    public static function mapRowData(array $headers, array $row, string $channelType = 'A2A'): array
    {
        $mapped = [];

        foreach ($headers as $colIndex => $headerName) {
            $rawHeader = trim((string) $headerName);
            $cleanHeader = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawHeader));
            $val = $row[$colIndex] ?? null;

            if ($cleanHeader === '' || $cleanHeader === 'sl') {
                continue;
            }

            if (in_array($cleanHeader, ['ref', 'refno', 'reference', 'referenceid', 'refid'])) {
                $cleanVal = static::cleanString((string) $val, 255);
                $mapped['reference_id'] = $cleanVal;
                // For RTGS/BEFTN, this Ref No. is the BB Reference Number shared with Bangladesh Bank
                if (in_array($channelType, ['RTGS', 'BEFTN'])) {
                    $mapped['bb_reference_number'] = $cleanVal;
                }
            } elseif (in_array($cleanHeader, ['bbreferencenumber', 'bbreference', 'bbref', 'bbrefno', 'bbrefid', 'referencenumber'])) {
                $mapped['bb_reference_number'] = static::cleanString((string) $val, 100);
            } elseif (in_array($cleanHeader, ['date', 'executiondate', 'createdate', 'transactiondate', 'txndate'])) {
                $mapped['create_date'] = $val;
            } elseif (in_array($cleanHeader, ['returndate'])) {
                $mapped['return_date'] = $val;
            } elseif (in_array($cleanHeader, ['acname', 'bankaccountname', 'benename', 'beneficiaryname', 'accountname', 'beneaccountname'])) {
                $mapped['debit_account_title'] = static::cleanString((string) $val, 150);
            } elseif (in_array($cleanHeader, ['accountno', 'beneficiaryacno', 'bankaccountnumber', 'bankaccountno', 'beneaccountno', 'acno'])) {
                $mapped['debit_account_no'] = static::cleanString((string) $val, 100);
            } elseif (in_array($cleanHeader, ['amount', 'amountbdt', 'amountintaka'])) {
                $cleanVal = preg_replace('/[^0-9.]/', '', str_replace(',', '', (string) $val));
                $mapped['amount'] = (float) $cleanVal;
            } elseif (in_array($cleanHeader, ['routingcode', 'routingnumber', 'beneroutingno', 'routingno'])) {
                // Note: This is the beneficiary/credit-side routing number from the sample files
                $mapped['debit_routing'] = static::cleanString((string) $val, 20);
            } elseif (in_array($cleanHeader, ['bankname', 'benebankname'])) {
                $mapped['credit_routing'] = static::cleanString((string) $val, 100);
            } elseif (in_array($cleanHeader, ['branchname', 'benebranchname'])) {
                $mapped['credit_bank'] = static::cleanString((string) $val, 255);
            } elseif (in_array($cleanHeader, ['bankbranchname'])) {
                $mapped['credit_routing'] = static::cleanString((string) $val, 100);
            } elseif (in_array($cleanHeader, ['debitaccount', 'debitaccountno'])) {
                $mapped['credit_account_no'] = static::cleanString((string) $val, 100);
            } elseif (in_array($cleanHeader, ['txnid', 'transactionid'])) {
                $mapped['txn_id'] = static::cleanString((string) $val, 100);
            } elseif (in_array($cleanHeader, ['rejectreason'])) {
                $mapped['reject_reason'] = static::cleanString((string) $val, 255);
            }
        }

        return $mapped;
    }

    /**
     * Validate a single row of transaction data.
     * Returns ['is_valid' => bool, 'errors' => array, 'failure_code' => string|null]
     */
    public static function validateRow(
        array $mapped,
        string $channelType,
        ?string &$detectedDebitAccount = null,
        bool $enforceDebitAccountRule = true
    ): array {
        $errors = [];
        $failureCode = null;

        $refId = $mapped['reference_id'] ?? null;
        $amount = (float) ($mapped['amount'] ?? 0);
        $debitAccount = $mapped['credit_account_no'] ?? null;
        $txnId = $mapped['txn_id'] ?? null;

        // Required field checks
        if (empty($refId)) {
            $errors[] = 'Reference ID is missing';
            $failureCode = 'INVALID_ROW';
        }

        if ($amount <= 0) {
            $errors[] = 'Invalid transaction amount';
            $failureCode = $failureCode ?: 'INVALID_ROW';
        }

        // Single Debit Account Rule
        if ($enforceDebitAccountRule && $debitAccount) {
            if ($detectedDebitAccount === null) {
                $detectedDebitAccount = $debitAccount;
            } elseif ($detectedDebitAccount !== $debitAccount) {
                $errors[] = 'Single Debit Account Rule violated inside file.';
                $failureCode = 'MULTI_DEBIT_ACC';
            }

            // Whitelist check
            if (empty($errors) && !in_array($debitAccount, static::getWhitelistedAccounts())) {
                $errors[] = "Debit account {$debitAccount} is neither TCSA nor Operational Account.";
                $failureCode = 'INVALID_DEBIT_ACC';
            }
        }

        // Duplicate Transaction ID check (uses pre-fetched set for performance)
        if (empty($errors) && $txnId && in_array($txnId, static::$existingTxnIds)) {
            $errors[] = "Global Duplicate Transaction ID {$txnId} blocked.";
            $failureCode = 'DUPLICATE_TXN_ID';
        }

        // A2A-Specific Validation: Beneficiary account number required
        $beneAccount = $mapped['debit_account_no'] ?? null;
        if ($channelType === 'A2A' && empty($beneAccount)) {
            $errors[] = 'Beneficiary Account Number is required for Account-to-Account transfer.';
            $failureCode = 'INVALID_ACCOUNT_NO';
        }

        // RTGS & BEFTN Routing Code Validation (Conditional if required)
        $routingNo = $mapped['debit_routing'] ?? null;
        if (in_array($channelType, ['RTGS', 'BEFTN']) && config('bkash.validate_routing_numbers', false)) {
            if (empty($routingNo) || strlen($routingNo) !== 9 || !is_numeric($routingNo)) {
                $errors[] = "Valid 9-digit Routing Number required for {$channelType}.";
                $failureCode = 'INVALID_ROUTING_NO';
            }
        }

        // RTGS Minimum Limit
        if (empty($errors) && $channelType === 'RTGS' && $amount < config('bkash.rtgs_min_limit', 100000)) {
            $errors[] = 'RTGS amount must be at least BDT 1,00,000.';
            $failureCode = 'RTGS_MIN_LIMIT';
        }

        return [
            'is_valid'     => empty($errors),
            'errors'       => $errors,
            'failure_code' => $failureCode,
        ];
    }

    /**
     * Clean string: remove control characters, trim, limit length.
     */
    public static function cleanString(?string $value, int $maxLength = 100): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', (string) $value);
        $clean = trim($clean);

        if (empty($clean)) {
            return null;
        }

        return Str::limit($clean, $maxLength, '');
    }

    /**
     * Validate Single Debit Account rule at the file level before processing rows.
     *
     * @return array ['is_valid' => bool, 'debit_accounts' => array, 'error_message' => string|null]
     */
    public static function validateFileLevelDebitAccounts(array $importRows, array $headerRow): array
    {
        $detectedAccounts = [];

        foreach ($importRows as $index => $row) {
            if ($index === 0 || empty(array_filter((array) $row))) {
                continue;
            }

            $rowArr = array_values((array) $row);
            $mapped = static::mapRowData($headerRow, $rowArr);
            $debitAcc = static::cleanString($mapped['credit_account_no'] ?? null, 100);

            if (!empty($debitAcc)) {
                $detectedAccounts[$debitAcc] = true;
            }
        }

        $uniqueAccounts = array_keys($detectedAccounts);

        if (count($uniqueAccounts) > 1) {
            $accountsStr = implode(', ', $uniqueAccounts);
            return [
                'is_valid'       => false,
                'debit_accounts' => $uniqueAccounts,
                'error_message'  => "File contains multiple debit accounts: {$accountsStr} — expected single debit account per file.",
            ];
        }

        return [
            'is_valid'       => true,
            'debit_accounts' => $uniqueAccounts,
            'error_message'  => null,
        ];
    }

    /**
     * Get whitelisted debit accounts.
     */
    public static function getDebitAccountsWhitelist(): array
    {
        return static::getWhitelistedAccounts();
    }
}
