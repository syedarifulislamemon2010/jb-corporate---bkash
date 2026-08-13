<?php

namespace App\Services;

use App\Models\BkashTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Mt940GeneratorService
{
    /**
     * Limit string length safely.
     */
    private static function strLimit(?string $value, int $limit = 100, string $end = ''): string
    {
        if ($value === null) return '';
        return \Illuminate\Support\Str::limit($value, $limit, $end);
    }

    /**
     * Generate SWIFT MT940 Customer Statement File for a given bank account number.
     *
     * SWIFT MT940 Format Structure:
     * :20: Transaction Reference Number
     * :25: Account Identification
     * :28C: Statement Number / Sequence Number
     * :60F: Opening Balance (C/D, Date YYMMDD, Currency, Amount)
     * :61: Statement Line (Date YYMMDD, Entry Date MMDD, C/D, Amount, Trans Type, Ref)
     * :86: Information to Account Owner (Narrative/Details)
     * :62F: Closing Balance (C/D, Date YYMMDD, Currency, Amount)
     */
    public static function generateStatement(string $accountNumber, ?Carbon $date = null): string
    {
        $targetDate = $date ?? Carbon::today();
        $dateFormatted = $targetDate->format('ymd');
        $dateFull = $targetDate->format('Y-m-d');
        $refNumber = 'JB' . $targetDate->format('Ymd') . \Illuminate\Support\Str::random(8);

        // Fetch settled transactions for this account on target date
        $transactions = BkashTransaction::where(function ($q) use ($accountNumber) {
            $q->where('credit_account_no', $accountNumber)
              ->orWhere('debit_account_no', $accountNumber);
        })
        ->whereIn('status_id', [
            BkashTransaction::STATUS_FINAL_AUTHORIZED,
            BkashTransaction::STATUS_CBS_SUCCESS
        ])
        ->whereDate('updated_at', $dateFull)
        ->orderBy('updated_at', 'asc')
        ->get();

        $openingBalance = $accountNumber === '0100202707747' ? 542000000.50 : 18500000.00;
        $runningBalance = $openingBalance;

        $mt940 = ":20:{$refNumber}\r\n";
        $mt940 .= ":25:{$accountNumber}\r\n";
        $mt940 .= ":28C:00001/001\r\n";

        // Opening Balance :60F:
        $openingSign = $openingBalance >= 0 ? 'C' : 'D';
        $formattedOpening = sprintf('%012.2f', abs($openingBalance));
        $formattedOpening = str_replace('.', ',', $formattedOpening);
        $mt940 .= ":60F:{$openingSign}{$dateFormatted}BDT{$formattedOpening}\r\n";

        // Statement Lines :61: & :86:
        foreach ($transactions as $index => $txn) {
            $isDebit = ($txn->credit_account_no === $accountNumber);
            $sign = $isDebit ? 'D' : 'C';
            $amountStr = sprintf('%012.2f', (float)$txn->amount);
            $amountStr = str_replace('.', ',', $amountStr);
            $txnRef = $txn->reference_id ?: $txn->txn_id;

            // Line :61:
            $mt940 .= ":61:{$dateFormatted}{$targetDate->format('md')}{$sign}NBNK{$amountStr}NONREF//{$txnRef}\r\n";

            // Details :86:
            $narrative = static::strLimit("TRN/{$txn->transaction_type}/REF:{$txnRef}/ACC:{$txn->debit_account_no}", 65);
            $mt940 .= ":86:{$narrative}\r\n";

            if ($isDebit) {
                $runningBalance -= (float)$txn->amount;
            } else {
                $runningBalance += (float)$txn->amount;
            }
        }

        // Closing Balance :62F:
        $closingSign = $runningBalance >= 0 ? 'C' : 'D';
        $formattedClosing = sprintf('%012.2f', abs($runningBalance));
        $formattedClosing = str_replace('.', ',', $formattedClosing);
        $mt940 .= ":62F:{$closingSign}{$dateFormatted}BDT{$formattedClosing}\r\n";

        // Save to SFTP folder & storage
        $fileName = "MT940_{$accountNumber}_{$targetDate->format('YMD')}.sta";
        Storage::disk('local')->put("sftp/MT940_Statements/{$fileName}", $mt940);
        Storage::disk('public')->put("MT940_Statements/{$fileName}", $mt940);

        Log::info("Generated MT940 statement for account {$accountNumber}: {$fileName}");

        return $mt940;
    }
}
