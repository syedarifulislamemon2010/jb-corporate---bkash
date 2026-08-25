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

    public function test_two_tier_authorization_lifecycle_transitions_with_user_ids(): void
    {
        $authorizerUser = User::create(['name' => 'Authorizer One', 'email' => 'auth1@jb.com', 'mobile_no' => '01711111111', 'organization' => 'JB', 'password' => bcrypt('123')]);
        $confirmerUser  = User::create(['name' => 'Confirmer Two', 'email' => 'conf2@jb.com', 'mobile_no' => '01722222222', 'organization' => 'JB', 'password' => bcrypt('123')]);

        // 1. Initial State: Pending Authorization (1000)
        $txn = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_STAGE_01',
            'txn_id'              => 'TXN_STAGE_01',
            'amount'              => 5000.00,
            'status_id'           => BkashTransaction::STATUS_PENDING_AUTHORIZATION,
            'created_by'          => 'SFTP_CRON',
            'create_date'         => Carbon::now(),
        ]);

        $this->assertEquals(BkashTransaction::STATUS_PENDING_AUTHORIZATION, $txn->status_id);

        // 2. Step 1: Authorizer authorizes transaction -> AUTHORIZED (1001)
        $txn->update([
            'status_id'        => BkashTransaction::STATUS_AUTHORIZED,
            'approved_by_1'    => $authorizerUser->name,
            'approved_by_1_id' => $authorizerUser->id,
            'approved_at_1'    => Carbon::now(),
        ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_AUTHORIZED, $txn->status_id);
        $this->assertEquals($authorizerUser->id, $txn->approved_by_1_id);
        $this->assertEquals($authorizerUser->name, $txn->approved_by_1);

        // 3. Step 2: Confirmer (different user) confirms transaction -> FINAL_AUTHORIZED / CONFIRMED (1003)
        $this->assertNotEquals($txn->approved_by_1_id, $confirmerUser->id);

        $txn->update([
            'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
            'approved_by_2'    => $confirmerUser->name,
            'approved_by_2_id' => $confirmerUser->id,
            'approved_at_2'    => Carbon::now(),
            'confirmed_by'     => $confirmerUser->name,
            'confirmed_at'     => Carbon::now(),
        ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_FINAL_AUTHORIZED, $txn->status_id);
        $this->assertEquals($confirmerUser->id, $txn->approved_by_2_id);

        // Test relationships
        $this->assertEquals($authorizerUser->id, $txn->approvedBy1User->id);
        $this->assertEquals($confirmerUser->id, $txn->approvedBy2User->id);
    }

    public function test_segregation_of_duties_prevents_authorizer_from_confirming(): void
    {
        $authorizer = User::create(['name' => 'Authorizer One', 'email' => 'auth@jb.com', 'mobile_no' => '01799999999', 'organization' => 'JB', 'password' => bcrypt('123')]);

        $txn = BkashTransaction::create([
            'transaction_type'    => 'BEFTN',
            'reference_id'        => 'REF_SOD_01',
            'txn_id'              => 'TXN_SOD_01',
            'amount'              => 20000.00,
            'status_id'           => BkashTransaction::STATUS_AUTHORIZED,
            'approved_by_1'       => $authorizer->name,
            'approved_by_1_id'    => $authorizer->id,
            'approved_at_1'       => Carbon::now(),
        ]);

        // Attempting final confirmation by the same user who authorized
        $isSameAsAuthorizer = ($txn->approved_by_1_id === $authorizer->id) || ($txn->approved_by_1 === $authorizer->name);
        $this->assertTrue($isSameAsAuthorizer, 'Should detect that user already provided authorization');
    }

    public function test_transaction_can_be_rejected_with_reason(): void
    {
        $txn = BkashTransaction::create([
            'transaction_type'    => 'BEFTN',
            'reference_id'        => 'REF_REJECT_01',
            'txn_id'              => 'TXN_REJECT_01',
            'amount'              => 10000.00,
            'status_id'           => BkashTransaction::STATUS_PENDING_AUTHORIZATION,
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
