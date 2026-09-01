<?php

namespace App\Services;

use App\Helper\ValueDateHelper;
use App\Models\BkashTransaction;
use Carbon\Carbon;
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
                $mapped['beneficiary_account_no'] = static::cleanString((string) $val, 100);
            } elseif (in_array($cleanHeader, ['amount', 'amountbdt', 'amountintaka'])) {
                $cleanVal = preg_replace('/[^0-9.]/', '', str_replace(',', '', (string) $val));
                $mapped['amount'] = (float) $cleanVal;
            } elseif (in_array($cleanHeader, ['routingcode', 'routingnumber', 'beneroutingno', 'routingno'])) {
                // Beneficiary Routing Number (Credit-side routing)
                $routingVal = static::cleanString((string) $val, 20);
                $mapped['credit_routing'] = $routingVal;
                $mapped['debit_routing']  = $routingVal; // Backward compatibility
            } elseif (in_array($cleanHeader, ['bankname', 'benebankname', 'bank'])) {
                $mapped['credit_bank'] = static::cleanString((string) $val, 255);
            } elseif (in_array($cleanHeader, ['branchname', 'benebranchname', 'branch'])) {
                $mapped['branch_name'] = static::cleanString((string) $val, 255);
            } elseif (in_array($cleanHeader, ['bankbranchname', 'bankandbranchname'])) {
                // Combined Bank & Branch (e.g. in RTGS files: "MUTUAL TRUST BANK LTD.,GAZIPUR")
                $mapped['credit_bank'] = static::cleanString((string) $val, 255);
            } elseif (in_array($cleanHeader, ['debitaccount', 'debitaccountno'])) {
                $mapped['source_account_no'] = static::cleanString((string) $val, 100);
            } elseif (in_array($cleanHeader, ['txnid', 'transactionid'])) {
                $mapped['txn_id'] = static::cleanString((string) $val, 100);
            } elseif (in_array($cleanHeader, ['rejectreason'])) {
                $mapped['reject_reason'] = static::cleanString((string) $val, 255);
            }
        }

        // Auto-derive credit_bank from routing number (first 3 digits) if credit_bank is empty
        if (empty($mapped['credit_bank']) && !empty($mapped['credit_routing'])) {
            $derivedBank = static::deriveCreditBankFromRouting($mapped['credit_routing']);
            if ($derivedBank) {
                $mapped['credit_bank'] = $derivedBank;
            }
        }

        return $mapped;
    }

    /**
     * Auto-derive scheduled bank name from Bangladesh Bank 9-digit routing number (first 3 digits = Bank Code).
     */
    public static function deriveCreditBankFromRouting(?string $routingNumber): ?string
    {
        if (empty($routingNumber) || strlen(trim($routingNumber)) < 3) {
            return null;
        }

        $bankCode = substr(trim($routingNumber), 0, 3);

        $bankDirectory = [
            '010' => 'Sonali Bank PLC',
            '015' => 'Bangladesh Krishi Bank',
            '020' => 'Agrani Bank PLC',
            '025' => 'Janata Bank PLC',
            '030' => 'Rupali Bank PLC',
            '035' => 'BRAC Bank PLC',
            '040' => 'Bank Asia Limited',
            '045' => 'BASIC Bank Limited',
            '050' => 'Rajshahi Krishi Unnayan Bank',
            '055' => 'Eastern Bank PLC',
            '060' => 'First Security Islami Bank PLC',
            '065' => 'AB Bank Limited',
            '070' => 'The City Bank PLC',
            '075' => 'Community Bank Bangladesh PLC',
            '080' => 'Dhaka Bank PLC',
            '085' => 'Dutch-Bangla Bank PLC',
            '090' => 'Dhaka Bank PLC',
            '095' => 'EXIM Bank PLC',
            '105' => 'ICB Islamic Bank Limited',
            '110' => 'IFIC Bank PLC',
            '115' => 'Islami Bank Bangladesh PLC',
            '120' => 'Jamuna Bank PLC',
            '125' => 'Mercantile Bank PLC',
            '130' => 'Meghna Bank PLC',
            '135' => 'Midland Bank Limited',
            '140' => 'Modhumoti Bank Limited',
            '145' => 'Mutual Trust Bank PLC',
            '150' => 'National Bank Limited',
            '155' => 'National Credit & Commerce Bank PLC',
            '160' => 'NRB Bank Limited',
            '165' => 'NRB Commercial Bank PLC',
            '170' => 'One Bank PLC',
            '175' => 'Pubali Bank PLC',
            '180' => 'Padma Bank Limited',
            '185' => 'The Premier Bank PLC',
            '190' => 'Prime Bank PLC',
            '195' => 'Global Islami Bank PLC',
            '200' => 'Shahjalal Islami Bank PLC',
            '205' => 'Shimanto Bank Limited',
            '210' => 'Social Islami Bank PLC',
            '215' => 'Southeast Bank PLC',
            '220' => 'South Bangla Agriculture & Commerce Bank PLC',
            '225' => 'Standard Chartered Bank',
            '230' => 'State Bank of India',
            '235' => 'Standard Chartered Bank',
            '240' => 'Standard Bank PLC',
            '245' => 'Trust Bank Limited',
            '250' => 'Union Bank PLC',
            '255' => 'United Commercial Bank PLC',
            '260' => 'United Commercial Bank PLC',
            '265' => 'Uttara Bank PLC',
            '270' => 'Woori Bank',
            '275' => 'HSBC Bangladesh',
            '280' => 'Citibank N.A.',
            '285' => 'Commercial Bank of Ceylon PLC',
            '290' => 'Habib Bank Limited',
            '295' => 'National Bank of Pakistan',
            '300' => 'Bengal Commercial Bank Limited',
            '305' => 'Citizens Bank PLC',
            '310' => 'Probashi Kallyan Bank',
            '315' => 'Bengal Commercial Bank Limited',
        ];

        return $bankDirectory[$bankCode] ?? null;
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
        $debitAccount = $mapped['source_account_no'] ?? null;
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
        $beneAccount = $mapped['beneficiary_account_no'] ?? null;
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
            $debitAcc = static::cleanString($mapped['source_account_no'] ?? null, 100);

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

    /**
     * Build standardized BkashTransaction attribute array from parsed row data.
     */
    public static function buildTransactionData(
        array $mapped,
        string $channelType,
        $batch,
        int $rowIndex,
        string $fileName,
        ?string $createdBy = null,
        ?int $createdById = null
    ): array {
        $refId        = static::cleanString($mapped['reference_id'] ?? null, 255);
        $bbRef        = static::cleanString($mapped['bb_reference_number'] ?? null, 100);
        $accountName  = static::cleanString($mapped['debit_account_title'] ?? null, 150);
        $accountNo    = static::cleanString($mapped['beneficiary_account_no'] ?? null, 100);
        $amount       = (float) ($mapped['amount'] ?? 0);
        $routingNo    = static::cleanString($mapped['credit_routing'] ?? $mapped['debit_routing'] ?? null, 20);
        $bankName     = static::cleanString($mapped['credit_bank'] ?? null, 255);
        $branchName   = static::cleanString($mapped['branch_name'] ?? null, 255);
        $debitAccount = static::cleanString($mapped['source_account_no'] ?? null, 100);
        $txnId        = static::cleanString($mapped['txn_id'] ?? null, 100) ?: (string) Str::uuid();
        $createDate   = $mapped['create_date'] ?? null;
        $rejectReason = static::cleanString($mapped['reject_reason'] ?? null, 255);

        $parsedDate = $createDate ? Carbon::parse($createDate) : Carbon::now();
        $valueDate  = ValueDateHelper::resolve($parsedDate)->toDateString();

        $batchId = is_object($batch) ? $batch->id : $batch;

        $txnData = [
            'batch_id'               => $batchId,
            'file_name'              => $fileName,
            'row_sequence'           => $rowIndex,
            'transaction_type'       => $channelType,
            'reference_id'           => Str::limit($refId, 255, ''),
            'bb_reference_number'    => $bbRef ? Str::limit($bbRef, 100, '') : null,
            'txn_id'                 => Str::limit($txnId, 100, ''),
            'debit_account_title'    => $accountName ? Str::limit($accountName, 150, '') : null,
            'beneficiary_account_no' => $accountNo ? Str::limit($accountNo, 100, '') : null,
            'debit_routing'          => $routingNo ? Str::limit($routingNo, 20, '') : null,
            'source_account_no'      => $debitAccount ? Str::limit($debitAccount, 100, '') : null,
            'credit_routing'         => $routingNo ? Str::limit($routingNo, 20, '') : null,
            'credit_bank'            => $bankName ? Str::limit($bankName, 255, '') : null,
            'amount'                 => $amount,
            'status_id'              => BkashTransaction::STATUS_PENDING_CHECKER,
            'created_by'             => Str::limit($createdBy ?? 'SYSTEM', 255, ''),
            'create_date'            => $parsedDate,
            'value_date'             => $valueDate,
        ];

        if ($createdById !== null) {
            $txnData['created_by_id'] = $createdById;
        }

        if ($rejectReason) {
            $txnData['reject_reason'] = $rejectReason;
        }

        return $txnData;
    }

    /**
     * Build standardized BkashFailedTransaction attribute array from parsed row data.
     */
    public static function buildFailedTransactionData(
        array $mapped,
        string $channelType,
        $batch,
        int $rowIndex,
        string $fileName,
        ?string $failureCode = 'INVALID_ROW',
        ?string $rejectReason = null
    ): array {
        $refId        = static::cleanString($mapped['reference_id'] ?? null, 100) ?: 'N/A';
        $accountNo    = static::cleanString($mapped['beneficiary_account_no'] ?? null, 50);
        $debitAccount = static::cleanString($mapped['source_account_no'] ?? null, 50);
        $amount       = (float) ($mapped['amount'] ?? 0);
        $batchId      = is_object($batch) ? $batch->id : $batch;

        return [
            'batch_id'               => $batchId,
            'file_name'              => $fileName,
            'row_number'             => $rowIndex + 1,
            'transaction_type'       => $channelType,
            'reference_id'           => Str::limit($refId, 100, ''),
            'beneficiary_account_no' => $accountNo ? Str::limit($accountNo, 50, '') : null,
            'source_account_no'      => $debitAccount ? Str::limit($debitAccount, 50, '') : null,
            'amount'                 => $amount,
            'failure_code'           => $failureCode ?: 'INVALID_ROW',
            'reject_reason'          => $rejectReason ?: ($mapped['reject_reason'] ?? 'Validation failed'),
        ];
    }
}
