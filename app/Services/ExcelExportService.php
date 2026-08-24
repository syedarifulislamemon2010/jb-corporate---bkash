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

    /**
     * Export transactions as 2-sheet XLSX format for Checker cross-checking.
     * Matches exact sample file format:
     * Sheet 1: "RTGS & BEFTN"
     * Sheet 2: "Account to Account"
     */
    public static function exportCheckerReportXlsx(
        Collection $transactions,
        string $fileName = 'Transaction_Process_Report.xlsx'
    ): StreamedResponse {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // -------------------------------------------------------------
        // Sheet 1: RTGS & BEFTN
        // -------------------------------------------------------------
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('RTGS & BEFTN');

        $headers1 = [
            'Date',
            'Ref No.',
            'A/C Name',
            'Beneficiary A/C No',
            'Bank & Branch Name',
            'Routing Code',
            'Amount(BDT)',
            'Debit Account',
            'Txn ID',
        ];

        foreach ($headers1 as $colIdx => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet1->setCellValue("{$colLetter}1", $h);
            $sheet1->getStyle("{$colLetter}1")->getFont()->setBold(true);
        }

        $row1 = 2;
        $rtgsBeftnTxns = $transactions->filter(fn($t) => in_array($t->transaction_type, ['RTGS', 'BEFTN']));
        foreach ($rtgsBeftnTxns as $t) {
            $bankBranch = trim(($t->credit_routing ? $t->credit_routing . ' ' : '') . ($t->credit_bank ?? ''));
            $sheet1->setCellValue("A{$row1}", $t->create_date?->format('d/m/Y') ?? $t->created_at?->format('d/m/Y'));
            $sheet1->setCellValue("B{$row1}", $t->bb_reference_number ?: $t->reference_id);
            $sheet1->setCellValue("C{$row1}", $t->debit_account_title);
            $sheet1->setCellValueExplicit("D{$row1}", (string) $t->debit_account_no, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet1->setCellValue("E{$row1}", $bankBranch);
            $sheet1->setCellValueExplicit("F{$row1}", (string) $t->debit_routing, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet1->setCellValue("G{$row1}", (float) $t->amount);
            $sheet1->setCellValueExplicit("H{$row1}", (string) $t->credit_account_no, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet1->setCellValue("I{$row1}", $t->txn_id);
            $row1++;
        }

        // -------------------------------------------------------------
        // Sheet 2: Account to Account
        // -------------------------------------------------------------
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Account to Account');

        $headers2 = [
            'Date',
            'Ref. No.',
            'Bank Account Name',
            'Bank Account Number',
            'Amount in Taka',
            'Debit Account',
            'Txn ID',
        ];

        foreach ($headers2 as $colIdx => $h) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet2->setCellValue("{$colLetter}1", $h);
            $sheet2->getStyle("{$colLetter}1")->getFont()->setBold(true);
        }

        $row2 = 2;
        $a2aTxns = $transactions->filter(fn($t) => $t->transaction_type === 'A2A');
        foreach ($a2aTxns as $t) {
            $sheet2->setCellValue("A{$row2}", $t->create_date?->format('d/m/Y') ?? $t->created_at?->format('d/m/Y'));
            $sheet2->setCellValue("B{$row2}", $t->reference_id);
            $sheet2->setCellValue("C{$row2}", $t->debit_account_title);
            $sheet2->setCellValueExplicit("D{$row2}", (string) $t->debit_account_no, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet2->setCellValue("E{$row2}", (float) $t->amount);
            $sheet2->setCellValueExplicit("F{$row2}", (string) $t->credit_account_no, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet2->setCellValue("G{$row2}", $t->txn_id);
            $row2++;
        }

        // Set active sheet back to first sheet
        $spreadsheet->setActiveSheetIndex(0);

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($spreadsheet) {
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
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
