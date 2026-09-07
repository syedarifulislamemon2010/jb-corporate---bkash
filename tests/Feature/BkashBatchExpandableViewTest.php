<?php

namespace Tests\Feature;

use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BkashBatchExpandableViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_expandable_view_renders_correct_success_and_failed_counts(): void
    {
        $admin = User::create([
            'name'         => 'Admin User',
            'email'        => 'admin@test.com',
            'mobile_no'    => '01700000000',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('password'),
        ]);

        $batch = BkashTransactionBatch::create([
            'file_name' => 'TEST_BATCH_EXPAND_2026.xlsx',
            'transaction_type' => 'A2A',
            'total_data' => 20,
            'total_amount' => 150000.00,
            'status_id' => 1004,
            'created_by' => 'Admin User',
        ]);

        // Create 15 successful transactions (Status 1004)
        for ($i = 1; $i <= 15; $i++) {
            BkashTransaction::create([
                'batch_id' => $batch->id,
                'file_name' => $batch->file_name,
                'row_sequence' => $i,
                'transaction_type' => 'A2A',
                'reference_id' => "SUCC_REF_{$i}",
                'txn_id' => "TXN_SUCC_{$i}",
                'debit_account_title' => "Beneficiary {$i}",
                'beneficiary_account_no' => "01001111111{$i}",
                'source_account_no' => '0100202707747',
                'amount' => 8000.00,
                'status_id' => BkashTransaction::STATUS_CBS_SUCCESS,
                'created_by' => 'Admin User',
            ]);
        }

        // Create 5 failed transactions in BkashFailedTransaction table
        for ($j = 1; $j <= 5; $j++) {
            BkashFailedTransaction::create([
                'batch_id' => $batch->id,
                'file_name' => $batch->file_name,
                'row_number' => 15 + $j,
                'transaction_type' => 'A2A',
                'reference_id' => "FAIL_REF_{$j}",
                'source_account_no' => '0100202707747',
                'beneficiary_account_no' => "01009999999{$j}",
                'amount' => 6000.00,
                'failure_code' => 'ACCOUNT_INVALID',
                'reject_reason' => 'Invalid beneficiary account number',
            ]);
        }

        // Render the slide-over Blade view directly with this batch
        $view = $this->actingAs($admin)
            ->view('filament.resources.bkash-batches.detail-modal', ['batch' => $batch]);

        // Assert rendered view displays the batch details and correct counts
        $view->assertSee('TEST_BATCH_EXPAND_2026.xlsx');
        $view->assertSee('Admin User');
        $view->assertSee('Successful');
        $view->assertSee('Failed / Error');
        $view->assertSee('Pending');

        // Assert success count = 15
        $view->assertSee('15');

        // Assert failed count = 5
        $view->assertSee('5');

        // Assert export buttons point to the download-batch route
        $view->assertSee('Download as Excel');
        $view->assertSee('Download as CSV');
        $view->assertSee(route('admin.bkash.download-batch', ['file' => $batch->file_name, 'format' => 'xlsx']));
        $view->assertSee(route('admin.bkash.download-batch', ['file' => $batch->file_name, 'format' => 'csv']));
    }

    public function test_batch_download_controller_streams_excel_and_csv(): void
    {
        $admin = User::create([
            'name'         => 'Downloader User',
            'email'        => 'downloader@test.com',
            'mobile_no'    => '01711111111',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('password'),
        ]);

        $batch = BkashTransactionBatch::create([
            'file_name' => 'DOWNLOAD_TEST_BATCH.xlsx',
            'transaction_type' => 'A2A',
            'total_data' => 2,
            'total_amount' => 2000.00,
            'status_id' => 1000,
            'created_by' => 'Downloader',
        ]);

        BkashTransaction::create([
            'batch_id'               => $batch->id,
            'file_name'              => $batch->file_name,
            'row_sequence'           => 1,
            'transaction_type'       => 'A2A',
            'reference_id'           => 'DL_REF_1',
            'txn_id'                 => 'DL_TXN_1',
            'beneficiary_account_no' => '0100111111111',
            'source_account_no'      => '0100202707747',
            'amount'                 => 1000.00,
            'status_id'              => 1000,
            'created_by'             => 'Downloader',
        ]);

        // Test XLSX download
        $responseXlsx = $this->actingAs($admin)
            ->get(route('admin.bkash.download-batch', ['file' => $batch->file_name, 'format' => 'xlsx']));

        $responseXlsx->assertStatus(200);

        // Test CSV download
        $responseCsv = $this->actingAs($admin)
            ->get(route('admin.bkash.download-batch', ['file' => $batch->file_name, 'format' => 'csv']));

        $responseCsv->assertStatus(200);
        $responseCsv->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}