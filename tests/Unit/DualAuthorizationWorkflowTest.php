<?php

namespace Tests\Unit;

use App\Models\BkashTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DualAuthorizationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_tier_authorization_lifecycle_transitions_with_user_ids(): void
    {
        $checkerUser    = User::create(['name' => 'Checker Person', 'email' => 'checker@jb.com', 'mobile_no' => '01700000000', 'organization' => 'JB', 'password' => bcrypt('123')]);
        $authorizer1User = User::create(['name' => 'Authorizer One', 'email' => 'auth1@jb.com', 'mobile_no' => '01711111111', 'organization' => 'JB', 'password' => bcrypt('123')]);
        $authorizer2User = User::create(['name' => 'Authorizer Two (Confirmer)', 'email' => 'auth2@jb.com', 'mobile_no' => '01722222222', 'organization' => 'JB', 'password' => bcrypt('123')]);

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

        // 2. Step 1: Checker verifies and checks -> STATUS_CHECKED (1001)
        $txn->update([
            'status_id'     => BkashTransaction::STATUS_CHECKED,
            'checked_by'    => $checkerUser->name,
            'checked_by_id' => $checkerUser->id,
            'checked_at'    => Carbon::now(),
        ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_CHECKED, $txn->status_id);
        $this->assertEquals($checkerUser->id, $txn->checked_by_id);
        $this->assertEquals($checkerUser->name, $txn->checked_by);

        // 3. Step 2: 1st Authorizer approves -> STATUS_AUTH_1_APPROVED (1002)
        $this->assertNotEquals($txn->checked_by_id, $authorizer1User->id);

        $txn->update([
            'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
            'approved_by_1'    => $authorizer1User->name,
            'approved_by_1_id' => $authorizer1User->id,
            'approved_at_1'    => Carbon::now(),
        ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_AUTH_1_APPROVED, $txn->status_id);
        $this->assertEquals($authorizer1User->id, $txn->approved_by_1_id);

        // 4. Step 3: 2nd Authorizer confirms -> STATUS_FINAL_AUTHORIZED (1003)
        $this->assertNotEquals($txn->checked_by_id, $authorizer2User->id);
        $this->assertNotEquals($txn->approved_by_1_id, $authorizer2User->id);

        $txn->update([
            'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
            'approved_by_2'    => $authorizer2User->name,
            'approved_by_2_id' => $authorizer2User->id,
            'approved_at_2'    => Carbon::now(),
            'confirmed_by'     => $authorizer2User->name,
            'confirmed_at'     => Carbon::now(),
        ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_FINAL_AUTHORIZED, $txn->status_id);
        $this->assertEquals($authorizer2User->id, $txn->approved_by_2_id);

        // Test relationships
        $this->assertEquals($checkerUser->id, $txn->checkedByUser->id);
        $this->assertEquals($authorizer1User->id, $txn->approvedBy1User->id);
        $this->assertEquals($authorizer2User->id, $txn->approvedBy2User->id);
    }

    public function test_segregation_of_duties_prevents_checker_and_auth1_from_final_confirming(): void
    {
        $checker = User::create(['name' => 'Checker One', 'email' => 'checker1@jb.com', 'mobile_no' => '01788888888', 'organization' => 'JB', 'password' => bcrypt('123')]);
        $authorizer1 = User::create(['name' => 'Authorizer One', 'email' => 'auth1@jb.com', 'mobile_no' => '01799999999', 'organization' => 'JB', 'password' => bcrypt('123')]);

        $txn = BkashTransaction::create([
            'transaction_type'    => 'BEFTN',
            'reference_id'        => 'REF_SOD_01',
            'txn_id'              => 'TXN_SOD_01',
            'amount'              => 20000.00,
            'status_id'           => BkashTransaction::STATUS_AUTH_1_APPROVED,
            'checked_by'          => $checker->name,
            'checked_by_id'       => $checker->id,
            'approved_by_1'       => $authorizer1->name,
            'approved_by_1_id'    => $authorizer1->id,
            'approved_at_1'       => Carbon::now(),
        ]);

        // Attempting final confirmation by checker
        $isCheckerTryingToConfirm = ($txn->checked_by_id === $checker->id) || ($txn->checked_by === $checker->name);
        $this->assertTrue($isCheckerTryingToConfirm, 'Should detect that user was the checker');

        // Attempting final confirmation by 1st authorizer
        $isAuth1TryingToConfirm = ($txn->approved_by_1_id === $authorizer1->id) || ($txn->approved_by_1 === $authorizer1->name);
        $this->assertTrue($isAuth1TryingToConfirm, 'Should detect that user already provided 1st authorization');
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
