<?php

namespace Tests\Feature;

use App\Jobs\ProcessBkashFileJob;
use App\Models\BkashTransaction;
use App\Models\NotificationOutbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ProcessBkashFileJobEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private string $uploadDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadDir = storage_path('app/Bkash_Uploads');
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    private function generateXlsx(string $fileName, array $headers, array $row): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $colIdx => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}1", $header);
        }

        foreach ($row as $colIdx => $val) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheet->setCellValue("{$colLetter}2", $val);
        }

        $fullPath = $this->uploadDir . '/' . $fileName;
        $writer = new Xlsx($spreadsheet);
        $writer->save($fullPath);

        return $fullPath;
    }

    public function test_sftp_job_processes_rtgs_file_and_sets_bb_reference_number(): void
    {
        $fileName = 'JOB_E2E_RTGS_' . uniqid() . '.xlsx';
        $headers = [
            'Date', 'Ref No.', 'A/C Name', 'Beneficiary A/C No',
            'Bank & Branch Name', 'Routing Code', 'Amount(BDT)', 'Debit Account', 'Txn ID'
        ];
        $row = [
            '2026-07-28', 'BB_JOB_REF_RTGS_555', 'Nasir Uddin', '1001141002472',
            'Sonali Bank PLC', '010260856', 200000.00, '0100202707747', 'TXN_JOB_RTGS_01'
        ];
        $fullPath = $this->generateXlsx($fileName, $headers, $row);

        try {
            $job = new ProcessBkashFileJob($fullPath, 'RTGS', 'SYSTEM');
            $job->handle();

            $txn = BkashTransaction::where('file_name', $fileName)->first();
            $this->assertNotNull($txn, 'Transaction must be processed by Job');
            $this->assertEquals('BB_JOB_REF_RTGS_555', $txn->reference_id);
            $this->assertEquals('BB_JOB_REF_RTGS_555', $txn->bb_reference_number, 'bb_reference_number must be set for RTGS');
            $this->assertEquals('1001141002472', $txn->beneficiary_account_no);
            $this->assertEquals('0100202707747', $txn->source_account_no);
            $this->assertEquals(200000.00, (float) $txn->amount);

            $outbox = NotificationOutbox::where('file_name', $fileName)
                ->where('event_type', 'STAGE_1_SFTP')
                ->first();
            $this->assertNotNull($outbox, 'NotificationOutbox record must be created by ProcessBkashFileJob');
            $this->assertEquals(1, $outbox->total_trn);
            $this->assertEquals(200000.00, (float) $outbox->total_amount);
        } finally {
            @unlink($fullPath);
        }
    }

    public function test_sftp_job_processes_a2a_file_with_null_bb_reference_number(): void
    {
        $fileName = 'JOB_E2E_A2A_' . uniqid() . '.xlsx';
        $headers = [
            'Date', 'Ref. No.', 'Bank Account Name', 'Bank Account Number',
            'Amount in Taka', 'Debit Account', 'Txn ID'
        ];
        $row = [
            '2026-07-28', 'JOB_A2A_REF_777', 'Farzana Akter', '0100123456789',
            '45000.00', '0100202707747', 'TXN_JOB_A2A_01'
        ];
        $fullPath = $this->generateXlsx($fileName, $headers, $row);

        try {
            $job = new ProcessBkashFileJob($fullPath, 'A2A', 'SYSTEM');
            $job->handle();

            $txn = BkashTransaction::where('file_name', $fileName)->first();
            $this->assertNotNull($txn);
            $this->assertEquals('JOB_A2A_REF_777', $txn->reference_id);
            $this->assertNull($txn->bb_reference_number, 'bb_reference_number must be null for A2A');
            $this->assertEquals('0100123456789', $txn->beneficiary_account_no);
            $this->assertEquals('0100202707747', $txn->source_account_no);
            $this->assertEquals(45000.00, (float) $txn->amount);

            $outbox = NotificationOutbox::where('file_name', $fileName)
                ->where('event_type', 'STAGE_1_SFTP')
                ->first();
            $this->assertNotNull($outbox);
        } finally {
            @unlink($fullPath);
        }
    }
}