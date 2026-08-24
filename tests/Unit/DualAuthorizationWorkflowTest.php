<?php

namespace Tests\Unit;

use App\Models\BkashTransaction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DualAuthorizationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dual_authorization_lifecycle_transitions_in_correct_order(): void
    {
        // 1. Initial State: Pending Checker (1000)
        $txn = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_STAGE_01',
            'txn_id'              => 'TXN_STAGE_01',
            'amount'              => 5000.00,
            'status_id'           => BkashTransaction::STATUS_PENDING_CHECKER,
            'created_by'          => 'SFTP_CRON',
            'create_date'         => Carbon::now(),
        ]);

        $this->assertEquals(BkashTransaction::STATUS_PENDING_CHECKER, $txn->status_id);

        // 2. Step 1: Checker verifies transaction -> CHECKED (1001)
        $txn->update([
            'status_id'   => BkashTransaction::STATUS_CHECKED,
            'checked_by'  => 'Officer Asif',
            'checked_at'  => Carbon::now(),
        ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_CHECKED, $txn->status_id);
        $this->assertEquals('Officer Asif', $txn->checked_by);
        $this->assertNotNull($txn->checked_at);

        // 3. Step 2: 1st Approver authorizes -> AUTH_1_APPROVED (1002)
        $txn->update([
            'status_id'     => BkashTransaction::STATUS_AUTH_1_APPROVED,
            'approved_by_1' => 'Manager Tariq',
            'approved_at_1' => Carbon::now(),
        ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_AUTH_1_APPROVED, $txn->status_id);
        $this->assertEquals('Manager Tariq', $txn->approved_by_1);
        $this->assertNotNull($txn->approved_at_1);

        // 4. Step 3: 2nd / Final Approver approves -> FINAL_AUTHORIZED (1003)
        $txn->update([
            'status_id'     => BkashTransaction::STATUS_FINAL_AUTHORIZED,
            'approved_by_2' => 'DGM Rahman',
            'approved_at_2' => Carbon::now(),
        ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_FINAL_AUTHORIZED, $txn->status_id);
        $this->assertEquals('DGM Rahman', $txn->approved_by_2);
        $this->assertNotNull($txn->approved_at_2);
    }

    public function test_transaction_can_be_rejected_with_reason(): void
    {
        $txn = BkashTransaction::create([
            'transaction_type'    => 'BEFTN',
            'reference_id'        => 'REF_REJECT_01',
            'txn_id'              => 'TXN_REJECT_01',
            'amount'              => 10000.00,
            'status_id'           => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        $txn->update([
            'status_id'     => BkashTransaction::STATUS_REJECTED,
            'reject_reason' => 'Beneficiary Account Number format is invalid',
        ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_REJECTED, $txn->status_id);
        $this->assertEquals('Beneficiary Account Number format is invalid', $txn->reject_reason);
    }
}
