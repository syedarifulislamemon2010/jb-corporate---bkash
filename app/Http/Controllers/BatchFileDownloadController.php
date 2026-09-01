<?php

namespace App\Http\Controllers;

use App\Models\BkashTransaction;
use App\Services\ExcelExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BatchFileDownloadController extends Controller
{
    public function download(Request $request): Response
    {
        $fileName = $request->query('file');
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if (empty($fileName)) {
            abort(400, 'File name is required.');
        }

        $transactions = BkashTransaction::where('file_name', $fileName)->get();
        if ($transactions->isEmpty()) {
            abort(404, 'No transactions found for this file.');
        }

        $baseName = pathinfo($fileName, PATHINFO_FILENAME);

        if ($format === 'csv') {
            $exportFileName = $baseName . '_Export.csv';
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$exportFileName}\"",
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
                    'Status ID',
                    'Txn ID',
                    'Created At',
                ]);

                foreach ($transactions as $txn) {
                    fputcsv($handle, [
                        $txn->file_name,
                        $txn->row_sequence !== null ? $txn->row_sequence + 1 : '',
                        $txn->transaction_type,
                        $txn->reference_id,
                        $txn->source_account_no,
                        $txn->beneficiary_account_no,
                        number_format((float) $txn->amount, 2, '.', ''),
                        $txn->status_id,
                        $txn->txn_id,
                        $txn->created_at?->format('d/m/Y H:i'),
                    ]);
                }

                fclose($handle);
            }, 200, $headers);
        }

        $exportFileName = $baseName . '_Export.xlsx';
        return ExcelExportService::exportCheckerReportXlsx($transactions, $exportFileName);
    }
}