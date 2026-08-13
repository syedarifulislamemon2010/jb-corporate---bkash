<?php

namespace App\Services;

use App\Models\BkashTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Export transactions as CSV (universally compatible with Excel).
     * No external package dependency — production-safe.
     */
    public static function exportTransactionsCsv(
        Collection $transactions,
        string $fileName = 'bkash_transactions.csv'
    ): StreamedResponse {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($handle, [
                'Ref No',
                'Txn ID',
                'Channel',
                'Bank Account Name',
                'Bank Account No',
                'Amount (BDT)',
                'Routing Code',
                'Bank Name',
                'Branch Name',
                'Debit Account',
                'Status',
                'File Name',
                'Checked By',
                'Checked At',
                '1st Auth By',
                '1st Auth At',
                '2nd Auth By',
                '2nd Auth At',
                'Created At',
            ]);

            foreach ($transactions as $txn) {
                fputcsv($handle, [
                    $txn->reference_id,
                    $txn->txn_id,
                    $txn->transaction_type,
                    $txn->debit_account_title,
                    $txn->debit_account_no,
                    number_format((float) $txn->amount, 2, '.', ''),
                    $txn->debit_routing,
                    $txn->credit_routing,
                    $txn->credit_bank,
                    $txn->credit_account_no,
                    static::statusLabel((int) $txn->status_id),
                    $txn->file_name,
                    $txn->checked_by,
                    $txn->checked_at?->format('d/m/Y H:i'),
                    $txn->approved_by_1,
                    $txn->approved_at_1?->format('d/m/Y H:i'),
                    $txn->approved_by_2,
                    $txn->approved_at_2?->format('d/m/Y H:i'),
                    $txn->create_date?->format('d/m/Y H:i'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export failed transactions as CSV.
     */
    public static function exportFailedTransactionsCsv(
        Collection $transactions,
        string $fileName = 'bkash_failed_transactions.csv'
    ): StreamedResponse {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'File Name',
                'Row No',
                'Channel',
                'Ref No',
                'Debit Account',
                'Beneficiary Account',
                'Amount (BDT)',
                'Failure Code',
                'Reason for Failure',
                'Failed At',
            ]);

            foreach ($transactions as $txn) {
                fputcsv($handle, [
                    $txn->file_name,
                    $txn->row_number,
                    $txn->transaction_type,
                    $txn->reference_id,
                    $txn->debit_account_no,
                    $txn->credit_account_no,
                    number_format((float) $txn->amount, 2, '.', ''),
                    $txn->failure_code,
                    $txn->reject_reason,
                    $txn->created_at?->format('d/m/Y H:i'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    private static function statusLabel(int $statusId): string
    {
        return match ($statusId) {
            1000 => 'Pending Checker',
            1001 => 'Checked',
            1002 => '1st Authorized',
            1003 => 'Final Authorized',
            1004 => 'CBS / BACH Settled',
            9000 => 'Rejected',
            default => 'Unknown',
        };
    }
}
