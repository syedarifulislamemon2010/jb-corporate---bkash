<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFileTransactionCountConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_files_and_transactions_counts_remain_synchronized_during_partial_and_full_transitions(): void
    {
        $dashboard = new Dashboard();

        // 1. Create a single batch with 3 transactions in STATUS_PENDING_CHECKER
        $batch1 = BkashTransactionBatch::create([
            'file_name'        => 'TEST_BATCH_001.xlsx',
            'transaction_type' => 'A2A',
            'total_data'       => 3,
            'total_amount'     => 75000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
            'create_date'      => Carbon::today(),
        ]);

        $txn1 = BkashTransaction::create([
            'batch_id'            => $batch1->id,
            'file_name'           => $batch1->file_name,
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_001',
            'txn_id'              => 'TXN_001',
            'amount'              => 25000.00,
            'status_id'           => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        $txn2 = BkashTransaction::create([
            'batch_id'            => $batch1->id,
            'file_name'           => $batch1->file_name,
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_002',
            'txn_id'              => 'TXN_002',
            'amount'              => 25000.00,
            'status_id'           => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        $txn3 = BkashTransaction::create([
            'batch_id'            => $batch1->id,
            'file_name'           => $batch1->file_name,
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_003',
            'txn_id'              => 'TXN_003',
            'amount'              => 25000.00,
            'status_id'           => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        // Assert initial state: 1 file, 3 transactions
        $stats = $dashboard->getActionStats();
        $this->assertEquals(1, $stats['pending_checker']['files']);
        $this->assertEquals(3, $stats['pending_checker']['trns']);
        $this->assertEquals(0, $stats['pending_auth1']['files']);
        $this->assertEquals(0, $stats['pending_auth1']['trns']);
        $this->assertEquals(0, $stats['pending_auth2']['files']);
        $this->assertEquals(0, $stats['pending_auth2']['trns']);

        // 2. Partial transition: Update 1 transaction to STATUS_CHECKED (2 remain in PENDING_CHECKER)
        $txn1->update(['status_id' => BkashTransaction::STATUS_CHECKED]);

        $stats = $dashboard->getActionStats();
        $this->assertEquals(1, $stats['pending_checker']['files'], 'Still 1 file with pending checker transactions');
        $this->assertEquals(2, $stats['pending_checker']['trns'], '2 transactions remaining in checker');
        $this->assertEquals(1, $stats['pending_auth1']['files'], '1 file with checked transactions awaiting auth1');
        $this->assertEquals(1, $stats['pending_auth1']['trns'], '1 transaction awaiting auth1');

        // 3. Complete checker stage for remaining transactions
        $txn2->update(['status_id' => BkashTransaction::STATUS_CHECKED]);
        $txn3->update(['status_id' => BkashTransaction::STATUS_CHECKED]);

        $stats = $dashboard->getActionStats();
        $this->assertEquals(0, $stats['pending_checker']['files'], '0 files awaiting check when all transactions moved');
        $this->assertEquals(0, $stats['pending_checker']['trns'], '0 transactions in checker');
        $this->assertEquals(1, $stats['pending_auth1']['files'], '1 file in auth1');
        $this->assertEquals(3, $stats['pending_auth1']['trns'], '3 transactions in auth1');

        // 4. Move all transactions to 1st auth approved (awaiting 2nd auth)
        $txn1->update(['status_id' => BkashTransaction::STATUS_AUTH_1_APPROVED]);
        $txn2->update(['status_id' => BkashTransaction::STATUS_AUTH_1_APPROVED]);
        $txn3->update(['status_id' => BkashTransaction::STATUS_AUTH_1_APPROVED]);

        $stats = $dashboard->getActionStats();
        $this->assertEquals(0, $stats['pending_auth1']['files']);
        $this->assertEquals(0, $stats['pending_auth1']['trns']);
        $this->assertEquals(1, $stats['pending_auth2']['files']);
        $this->assertEquals(3, $stats['pending_auth2']['trns']);

        // 5. Final settle all transactions
        $txn1->update(['status_id' => BkashTransaction::STATUS_FINAL_AUTHORIZED]);
        $txn2->update(['status_id' => BkashTransaction::STATUS_FINAL_AUTHORIZED]);
        $txn3->update(['status_id' => BkashTransaction::STATUS_FINAL_AUTHORIZED]);

        $stats = $dashboard->getActionStats();
        $this->assertEquals(0, $stats['pending_auth2']['files']);
        $this->assertEquals(0, $stats['pending_auth2']['trns']);
        $this->assertEquals(3, $stats['settled_today']['count']);
    }

    public function test_multiple_batches_count_distinct_files_correctly(): void
    {
        $dashboard = new Dashboard();

        $batchA = BkashTransactionBatch::create([
            'file_name'        => 'FILE_A.xlsx',
            'transaction_type' => 'A2A',
            'total_data'       => 2,
            'total_amount'     => 20000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
            'create_date'      => Carbon::today(),
        ]);

        $batchB = BkashTransactionBatch::create([
            'file_name'        => 'FILE_B.xlsx',
            'transaction_type' => 'BEFTN',
            'total_data'       => 3,
            'total_amount'     => 30000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
            'create_date'      => Carbon::today(),
        ]);

        BkashTransaction::create([
            'batch_id'         => $batchA->id,
            'file_name'        => 'FILE_A.xlsx',
            'transaction_type' => 'A2A',
            'reference_id'     => 'REF_A1',
            'txn_id'           => 'TXN_A1',
            'amount'           => 10000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);
        BkashTransaction::create([
            'batch_id'         => $batchA->id,
            'file_name'        => 'FILE_A.xlsx',
            'transaction_type' => 'A2A',
            'reference_id'     => 'REF_A2',
            'txn_id'           => 'TXN_A2',
            'amount'           => 10000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        BkashTransaction::create([
            'batch_id'         => $batchB->id,
            'file_name'        => 'FILE_B.xlsx',
            'transaction_type' => 'BEFTN',
            'reference_id'     => 'REF_B1',
            'txn_id'           => 'TXN_B1',
            'amount'           => 10000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);
        BkashTransaction::create([
            'batch_id'         => $batchB->id,
            'file_name'        => 'FILE_B.xlsx',
            'transaction_type' => 'BEFTN',
            'reference_id'     => 'REF_B2',
            'txn_id'           => 'TXN_B2',
            'amount'           => 10000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);
        BkashTransaction::create([
            'batch_id'         => $batchB->id,
            'file_name'        => 'FILE_B.xlsx',
            'transaction_type' => 'BEFTN',
            'reference_id'     => 'REF_B3',
            'txn_id'           => 'TXN_B3',
            'amount'           => 10000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        $stats = $dashboard->getActionStats();
        $this->assertEquals(2, $stats['pending_checker']['files'], 'Exactly 2 distinct files');
        $this->assertEquals(5, $stats['pending_checker']['trns'], 'Total 5 pending checker transactions');

        $banner = $dashboard->getUrgencyBanner();
        $this->assertNotNull($banner);
        $this->assertEquals(2, $banner['pending_checker']);
        $this->assertEquals(2, $banner['total']);
    }
}