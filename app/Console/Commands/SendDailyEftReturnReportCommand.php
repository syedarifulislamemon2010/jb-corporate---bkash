<?php

namespace App\Console\Commands;

use App\Models\EftReturn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SendDailyEftReturnReportCommand extends Command
{
    protected $signature = 'eft-return:send-daily {--date= : Specific return date (YYYY-MM-DD), default today}';

    protected $description = 'Generate daily EFT Return report in exact sample Excel format and email to bKash checkers/authorizers.';

    public function handle(): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::today()->toDateString();

        $this->info("Scanning EFT Return records for {$targetDate}...");
        Log::info("Daily EFT Return Report: Scanning records for {$targetDate}.");

        $returns = EftReturn::whereDate('created_at', $targetDate)
            ->orWhereDate('return_date', $targetDate)
            ->orWhereDate('returned_at', $targetDate)
            ->orderBy('id', 'asc')
            ->get();

        if ($returns->isEmpty()) {
            $this->info("No EFT Return records found for {$targetDate}. Skipping email dispatch.");
            Log::info("Daily EFT Return Report: No records for {$targetDate}. Email dispatch skipped.");
            return Command::SUCCESS;
        }

        $this->info("Found {$returns->count()} EFT Return record(s). Generating Excel attachment...");

        // Generate Excel Workbook
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('EFT Return Report');

        $headers = [
            'Execution Date',
            'Return Date',
            'Amount',
            'Service Type',
            'Bene. Bank Name',
            'Bene. Branch Name',
            'Bene. Routing No',
            'Bene. Account',
            'Bene. Name',
            'Reject Reason',
            'Particular',
        ];

        foreach ($headers as $idx => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$colLetter}1", $h);
            $sheet->getStyle("{$colLetter}1")->getFont()->setBold(true);
        }

        $rowIdx = 2;
        foreach ($returns as $r) {
            $execDate = $r->execution_date?->format('d/m/Y') ?? $r->created_at?->format('d/m/Y');
            $retDate  = $r->return_date?->format('d/m/Y') ?? ($r->returned_at?->format('d/m/Y') ?? Carbon::today()->format('d/m/Y'));
            $reason   = $r->return_reason ?: ($r->return_code ?: 'N/A');

            $sheet->setCellValue("A{$rowIdx}", $execDate);
            $sheet->setCellValue("B{$rowIdx}", $retDate);
            $sheet->setCellValue("C{$rowIdx}", (float) $r->amount);
            $sheet->setCellValue("D{$rowIdx}", $r->service_type ?: 'BEFTN');
            $sheet->setCellValue("E{$rowIdx}", $r->bene_bank_name ?: '');
            $sheet->setCellValue("F{$rowIdx}", $r->bene_branch_name ?: '');
            $sheet->setCellValueExplicit("G{$rowIdx}", (string) ($r->bene_routing_no ?: ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$rowIdx}", (string) ($r->beneficiary_account ?: ''), DataType::TYPE_STRING);
            $sheet->setCellValue("I{$rowIdx}", $r->bene_name ?: '');
            $sheet->setCellValue("J{$rowIdx}", $reason);
            $sheet->setCellValue("K{$rowIdx}", $r->particular ?: ($r->original_file_name ?: ''));
            $rowIdx++;
        }

        // Save temp file
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $fileName = "EFT_Return_Report_{$targetDate}.xlsx";
        $filePath = $tempDir . '/' . $fileName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $this->info("Excel generated at {$filePath}. Preparing email dispatch...");

        // Fetch recipients
        $recipients = User::whereNotNull('email')->pluck('email')->unique()->toArray();
        if (empty($recipients)) {
            $recipients = [config('bkash.email_from_address', 'bkash.checker@janatabank-bd.com')];
        }

        $fromAddress = config('bkash.email_from_address', 'noreply@janatabank-bd.com');
        $fromName    = config('bkash.email_from_name', 'Janata Bank Corporate Portal');

        $totalAmount = number_format($returns->sum('amount'), 2);
        $body = "Dear Sir/Madam,\n\n"
              . "Please find attached the Daily EFT Return Report for {$targetDate}.\n\n"
              . "Summary:\n"
              . "- Total Returned Transactions: {$returns->count()}\n"
              . "- Total Returned Amount: BDT {$totalAmount}\n\n"
              . "Thank you.\n\n"
              . "Best Regards,\n"
              . "Janata Bank PLC";

        try {
            Mail::raw($body, function ($message) use ($recipients, $fromAddress, $fromName, $targetDate, $filePath, $fileName) {
                $message->from($fromAddress, $fromName)
                    ->to($recipients)
                    ->subject("Daily EFT Return Report - {$targetDate} [Janata Bank Corporate Portal]")
                    ->attach($filePath, [
                        'as'   => $fileName,
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ]);
            });

            $this->info("Email dispatched successfully with attachment {$fileName}.");
            Log::info("Daily EFT Return Report: Email sent to " . implode(', ', $recipients) . " with attachment {$fileName}.");
        } catch (\Throwable $e) {
            $this->error("Failed to send email: " . $e->getMessage());
            Log::error("Daily EFT Return Report email dispatch failed: " . $e->getMessage());
        } finally {
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }

        return Command::SUCCESS;
    }
}
