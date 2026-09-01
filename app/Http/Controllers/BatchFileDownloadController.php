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
        if (empty($fileName)) {
            abort(400, 'File name is required.');
        }

        $transactions = BkashTransaction::where('file_name', $fileName)->get();
        if ($transactions->isEmpty()) {
            abort(404, 'No transactions found for this file.');
        }

        $exportFileName = pathinfo($fileName, PATHINFO_FILENAME) . '_Export.xlsx';
        return ExcelExportService::exportCheckerReportXlsx($transactions, $exportFileName);
    }
}