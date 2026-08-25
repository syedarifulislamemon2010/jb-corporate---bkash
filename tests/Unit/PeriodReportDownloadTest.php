<?php

namespace Tests\Unit;

use App\Models\BkashTransaction;
use App\Models\EftReturn;
use App\Services\ExcelExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodReportDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_transactions_for_different_periods(): void
    {
        $todayTxn = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_TODAY_01',
            'txn_id'              => 'TXN_TODAY_01',
            'amount'              => 5000.00,
            'credit_account_no'   => '0100202707747',
            'debit_account_no'    => '0100111111111',
            'create_date'         => now(),
            'status_id'           => BkashTransaction::STATUS_CBS_SUCCESS,
        ]);

        $thisWeekTxns = BkashTransaction::whereBetween('create_date', [now()->startOfWeek(), now()->endOfWeek()])->get();
        $responseWeek = ExcelExportService::exportCheckerReportXlsx($thisWeekTxns, 'test_week.xlsx');
        $this->assertEquals(200, $responseWeek->getStatusCode());
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $responseWeek->headers->get('Content-Type'));

        $thisMonthTxns = BkashTransaction::whereBetween('create_date', [now()->startOfMonth(), now()->endOfMonth()])->get();
        $responseMonth = ExcelExportService::exportCheckerReportXlsx($thisMonthTxns, 'test_month.xlsx');
        $this->assertEquals(200, $responseMonth->getStatusCode());

        $thisYearTxns = BkashTransaction::whereBetween('create_date', [now()->startOfYear(), now()->endOfYear()])->get();
        $responseYear = ExcelExportService::exportCheckerReportXlsx($thisYearTxns, 'test_year.xlsx');
        $this->assertEquals(200, $responseYear->getStatusCode());
    }

    public function test_export_eft_returns_for_different_periods(): void
    {
        $eft = EftReturn::create([
            'execution_date'    => now()->toDateString(),
            'return_date'       => now()->toDateString(),
            'amount'            => 1500.00,
            'service_type'      => 'BEFTN',
            'bene_bank_name'    => 'Sonali Bank',
            'bene_branch_name'  => 'Local Office',
            'bene_routing_no'   => '200260123',
            'bene_account'      => '0100888888888',
            'bene_name'         => 'Beneficiary User',
            'reject_reason'     => 'Account Closed',
            'particular'        => 'Salary Return',
        ]);

        $records = EftReturn::query()->whereBetween('execution_date', [now()->startOfMonth(), now()->endOfMonth()])->get();
        $response = ExcelExportService::exportEftReturnsReportXlsx($records, 'test_eft.xlsx');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
    }
}
