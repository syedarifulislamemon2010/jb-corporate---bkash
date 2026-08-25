<?php

namespace Tests\Unit;

use App\Models\BkashTransaction;
use App\Services\ExcelExportService;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class CheckerExcelExportTest extends TestCase
{
    public function test_checker_export_produces_exact_two_sheets_and_headers(): void
    {
        // 1. Setup mock transactions
        $t1 = new BkashTransaction([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_A2A_01',
            'txn_id'              => 'TXN_A2A_01',
            'debit_account_title' => 'John Doe',
            'debit_account_no'    => '0100123456789',
            'amount'              => 5000.00,
            'credit_account_no'   => '0100202707747',
        ]);
        $t1->create_date = now();

        $t2 = new BkashTransaction([
            'transaction_type'    => 'BEFTN',
            'reference_id'        => 'REF_BEFTN_01',
            'bb_reference_number' => 'REF_BEFTN_01',
            'txn_id'              => 'TXN_BEFTN_01',
            'debit_account_title' => 'Jane Smith',
            'debit_account_no'    => '2050123456789',
            'credit_routing'      => '125260856',
            'credit_bank'         => 'Islami Bank Gulshan Branch',
            'amount'              => 15000.00,
            'credit_account_no'   => '0100202707747',
        ]);
        $t2->create_date = now();

        $t3 = new BkashTransaction([
            'transaction_type'    => 'RTGS',
            'reference_id'        => 'REF_RTGS_01',
            'bb_reference_number' => 'REF_RTGS_01',
            'txn_id'              => 'TXN_RTGS_01',
            'debit_account_title' => 'Acme Corp',
            'debit_account_no'    => '3050123456789',
            'credit_routing'      => '225260856',
            'credit_bank'         => 'City Bank Principal Branch',
            'amount'              => 250000.00,
            'credit_account_no'   => '0100224107522',
        ]);
        $t3->create_date = now();

        $collection = new Collection([$t1, $t2, $t3]);

        // 2. Generate StreamedResponse and capture output buffer
        $response = ExcelExportService::exportCheckerReportXlsx($collection, 'test_export.xlsx');
        
        ob_start();
        $response->sendContent();
        $excelBinary = ob_get_clean();

        $this->assertNotEmpty($excelBinary);

        // 3. Load with PhpSpreadsheet to inspect structure
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_test_');
        file_put_contents($tempFile, $excelBinary);

        $spreadsheet = IOFactory::load($tempFile);

        // Assert 2 sheet names match exact sample file
        $sheetNames = $spreadsheet->getSheetNames();
        $this->assertContains('RTGS & BEFTN', $sheetNames);
        $this->assertContains('Account to Account', $sheetNames);

        // Assert Sheet 1: RTGS & BEFTN headers and row count
        $sheet1 = $spreadsheet->getSheetByName('RTGS & BEFTN');
        $expectedHeaders1 = [
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
        $actualHeaders1 = [];
        for ($col = 1; $col <= 9; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $actualHeaders1[] = $sheet1->getCell("{$colLetter}1")->getValue();
        }
        $this->assertEquals($expectedHeaders1, $actualHeaders1);

        // 2 data rows in Sheet 1 (BEFTN + RTGS)
        $this->assertEquals('REF_BEFTN_01', $sheet1->getCell('B2')->getValue());
        $this->assertEquals('REF_RTGS_01', $sheet1->getCell('B3')->getValue());

        // Assert Sheet 2: Account to Account headers and row count
        $sheet2 = $spreadsheet->getSheetByName('Account to Account');
        $expectedHeaders2 = [
            'Date',
            'Ref. No.',
            'Bank Account Name',
            'Bank Account Number',
            'Amount in Taka',
            'Debit Account',
            'Txn ID',
        ];
        $actualHeaders2 = [];
        for ($col = 1; $col <= 7; $col++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $actualHeaders2[] = $sheet2->getCell("{$colLetter}1")->getValue();
        }
        $this->assertEquals($expectedHeaders2, $actualHeaders2);

        // 1 data row in Sheet 2 (A2A)
        $this->assertEquals('REF_A2A_01', $sheet2->getCell('B2')->getValue());

        @unlink($tempFile);
    }
}
