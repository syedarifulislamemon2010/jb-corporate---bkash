<?php

namespace Tests\Feature;

use App\Filament\Resources\BkashTransactions\Pages\UploadBkashExcel;
use App\Models\BkashTransaction;
use App\Models\NotificationOutbox;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class UploadBkashExcelEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private User $uploader;
    private string $uploadDir;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->uploader = User::create([
            'name'         => 'Test Checker Uploader',
            'email'        => 'uploader_test@janatabank.com',
            'mobile_no'    => '01700000000',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

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

    public function test_upload_rtgs_excel_successfully_ingests_and_populates_bb_reference_number(): void
    {
        $fileName = 'TEST_E2E_RTGS_' . uniqid() . '.xlsx';
        $headers = [
            'Date', 'Ref No.', 'A/C Name', 'Beneficiary A/C No',
            'Bank & Branch Name', 'Routing Code', 'Amount(BDT)', 'Debit Account', 'Txn ID'
        ];
        $row = [
            '2026-07-28', 'BB_REF_RTGS_123456', 'Jamal Hossain', '1001141002472',
            'Sonali Bank PLC', '010260856', 150000.00, '0100202707747', 'TXN_RTGS_E2E_01'
        ];
        $fullPath = $this->generateXlsx($fileName, $headers, $row);

        try {
            $component = Livewire::actingAs($this->uploader)
                ->test(UploadBkashExcel::class)
                ->fillForm([
                    'channel_type' => 'RTGS',
                    'file'         => ['Bkash_Uploads/' . $fileName],
                ])
                ->call('submit');

            $component->assertHasNoErrors();

            // Verify BkashTransaction was created
            $txn = BkashTransaction::where('file_name', $fileName)->first();
            $this->assertNotNull($txn, 'Transaction must be created in DB');
            $this->assertEquals('BB_REF_RTGS_123456', $txn->reference_id);
            $this->assertEquals('BB_REF_RTGS_123456', $txn->bb_reference_number, 'bb_reference_number must be populated for RTGS');
            $this->assertEquals('1001141002472', $txn->beneficiary_account_no);
            $this->assertEquals('0100202707747', $txn->source_account_no);
            $this->assertEquals(150000.00, (float) $txn->amount);
            $this->assertEquals(BkashTransaction::STATUS_PENDING_CHECKER, $txn->status_id);

            // Verify NotificationOutbox stage 1 record was created
            $outbox = NotificationOutbox::where('file_name', $fileName)
                ->where('event_type', 'STAGE_1_SFTP')
                ->first();
            $this->assertNotNull($outbox, 'NotificationOutbox record must be created on successful upload');
            $this->assertEquals(1, $outbox->total_trn);
            $this->assertEquals(150000.00, (float) $outbox->total_amount);
        } finally {
            @unlink($fullPath);
        }
    }

    public function test_upload_beftn_excel_successfully_ingests_and_populates_bb_reference_number(): void
    {
        $fileName = 'TEST_E2E_BEFTN_' . uniqid() . '.xlsx';
        $headers = [
            'Date', 'Ref No.', 'A/C Name', 'Beneficiary A/C No',
            'Bank & Branch Name', 'Routing Code', 'Amount(BDT)', 'Debit Account', 'Txn ID'
        ];
        $row = [
            '2026-07-28', 'BB_REF_BEFTN_987654', 'Sultana Begum', '2001141009876',
            'Agrani Bank PLC', '020260856', 75000.00, '0100202707747', 'TXN_BEFTN_E2E_01'
        ];
        $fullPath = $this->generateXlsx($fileName, $headers, $row);

        try {
            $component = Livewire::actingAs($this->uploader)
                ->test(UploadBkashExcel::class)
                ->fillForm([
                    'channel_type' => 'BEFTN',
                    'file'         => ['Bkash_Uploads/' . $fileName],
                ])
                ->call('submit');

            $component->assertHasNoErrors();

            $txn = BkashTransaction::where('file_name', $fileName)->first();
            $this->assertNotNull($txn);
            $this->assertEquals('BB_REF_BEFTN_987654', $txn->reference_id);
            $this->assertEquals('BB_REF_BEFTN_987654', $txn->bb_reference_number, 'bb_reference_number must be populated for BEFTN');
            $this->assertEquals('2001141009876', $txn->beneficiary_account_no);
            $this->assertEquals('0100202707747', $txn->source_account_no);
            $this->assertEquals(75000.00, (float) $txn->amount);

            $outbox = NotificationOutbox::where('file_name', $fileName)
                ->where('event_type', 'STAGE_1_SFTP')
                ->first();
            $this->assertNotNull($outbox);
        } finally {
            @unlink($fullPath);
        }
    }

    public function test_upload_a2a_excel_successfully_ingests_with_null_bb_reference(): void
    {
        $fileName = 'TEST_E2E_A2A_' . uniqid() . '.xlsx';
        $headers = [
            'Date', 'Ref. No.', 'Bank Account Name', 'Bank Account Number',
            'Amount in Taka', 'Debit Account', 'Txn ID'
        ];
        $row = [
            '2026-07-28', 'A2A_INTERNAL_REF_111', 'Tariqul Islam', '0100123456789',
            '35000.00', '0100202707747', 'TXN_A2A_E2E_01'
        ];
        $fullPath = $this->generateXlsx($fileName, $headers, $row);

        try {
            $component = Livewire::actingAs($this->uploader)
                ->test(UploadBkashExcel::class)
                ->fillForm([
                    'channel_type' => 'A2A',
                    'file'         => ['Bkash_Uploads/' . $fileName],
                ])
                ->call('submit');

            $component->assertHasNoErrors();

            $txn = BkashTransaction::where('file_name', $fileName)->first();
            $this->assertNotNull($txn);
            $this->assertEquals('A2A_INTERNAL_REF_111', $txn->reference_id);
            $this->assertNull($txn->bb_reference_number, 'bb_reference_number should be null for A2A channel');
            $this->assertEquals('0100123456789', $txn->beneficiary_account_no);
            $this->assertEquals('0100202707747', $txn->source_account_no);
            $this->assertEquals(35000.00, (float) $txn->amount);

            $outbox = NotificationOutbox::where('file_name', $fileName)
                ->where('event_type', 'STAGE_1_SFTP')
                ->first();
            $this->assertNotNull($outbox);
        } finally {
            @unlink($fullPath);
        }
    }
}