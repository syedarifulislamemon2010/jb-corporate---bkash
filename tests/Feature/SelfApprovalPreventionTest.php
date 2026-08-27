<?php

namespace Tests\Feature;

use App\Models\BkashTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class SelfApprovalPreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_checker_cannot_perform_first_level_authorization_on_own_checked_transaction(): void
    {
        $checker = User::create([
            'name'         => 'Checker One',
            'email'        => 'checker1@jb.com',
            'mobile_no'    => '01700000001',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer1 = User::create([
            'name'         => 'Authorizer One',
            'email'        => 'auth1@jb.com',
            'mobile_no'    => '01700000002',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $txn = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_TEST_001',
            'txn_id'              => 'TXN_TEST_001',
            'amount'              => 25000.00,
            'status_id'           => BkashTransaction::STATUS_CHECKED,
            'checked_by'          => $checker->name,
            'checked_by_id'       => $checker->id,
            'checked_at'          => Carbon::now(),
        ]);

        // 1. Policy check for Checker user must DENY authorization
        $checkerResponse = Gate::forUser($checker)->inspect('authorize', $txn);
        $this->assertTrue($checkerResponse->denied(), 'Checker must be denied from 1st authorization');
        $this->assertEquals('You checked this file; 1st authorization must come from a different user.', $checkerResponse->message());

        // 2. Policy check for different user (Authorizer 1) must ALLOW authorization
        $auth1Response = Gate::forUser($authorizer1)->inspect('authorize', $txn);
        $this->assertTrue($auth1Response->allowed(), 'Different user must be allowed 1st authorization');
    }

    public function test_checker_and_first_authorizer_cannot_perform_second_level_final_confirmation(): void
    {
        $checker = User::create([
            'name'         => 'Checker One',
            'email'        => 'checker1@jb.com',
            'mobile_no'    => '01700000001',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer1 = User::create([
            'name'         => 'Authorizer One',
            'email'        => 'auth1@jb.com',
            'mobile_no'    => '01700000002',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer2 = User::create([
            'name'         => 'Authorizer Two',
            'email'        => 'auth2@jb.com',
            'mobile_no'    => '01700000003',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $txn = BkashTransaction::create([
            'transaction_type'    => 'BEFTN',
            'reference_id'        => 'REF_TEST_002',
            'txn_id'              => 'TXN_TEST_002',
            'amount'              => 50000.00,
            'status_id'           => BkashTransaction::STATUS_AUTH_1_APPROVED,
            'checked_by'          => $checker->name,
            'checked_by_id'       => $checker->id,
            'checked_at'          => Carbon::now(),
            'approved_by_1'       => $authorizer1->name,
            'approved_by_1_id'    => $authorizer1->id,
            'approved_at_1'       => Carbon::now(),
        ]);

        // 1. Checker must be blocked from final confirmation
        $checkerConfirmResponse = Gate::forUser($checker)->inspect('confirm', $txn);
        $this->assertTrue($checkerConfirmResponse->denied(), 'Checker must be denied from final confirmation');
        $this->assertEquals('You checked this file; final confirmation must come from a third distinct user.', $checkerConfirmResponse->message());

        // 2. 1st Authorizer must be blocked from final confirmation
        $auth1ConfirmResponse = Gate::forUser($authorizer1)->inspect('confirm', $txn);
        $this->assertTrue($auth1ConfirmResponse->denied(), '1st Authorizer must be denied from final confirmation');
        $this->assertEquals('You 1st-authorized this file; final confirmation must come from a third distinct user.', $auth1ConfirmResponse->message());

        // 3. Third distinct user (Authorizer 2) must be ALLOWED
        $auth2ConfirmResponse = Gate::forUser($authorizer2)->inspect('confirm', $txn);
        $this->assertTrue($auth2ConfirmResponse->allowed(), 'Third distinct user must be allowed final confirmation');
    }

    public function test_three_distinct_users_successfully_complete_entire_pipeline(): void
    {
        $userA = User::create(['name' => 'User A (Checker)', 'email' => 'a@jb.com', 'mobile_no' => '01711111111', 'organization' => 'JB', 'password' => bcrypt('123')]);
        $userB = User::create(['name' => 'User B (Auth 1)', 'email' => 'b@jb.com', 'mobile_no' => '01722222222', 'organization' => 'JB', 'password' => bcrypt('123')]);
        $userC = User::create(['name' => 'User C (Auth 2)', 'email' => 'c@jb.com', 'mobile_no' => '01733333333', 'organization' => 'JB', 'password' => bcrypt('123')]);

        // Step 1: Initial upload
        $txn = BkashTransaction::create([
            'transaction_type' => 'RTGS',
            'reference_id'     => 'REF_STAGE_E2E',
            'txn_id'           => 'TXN_STAGE_E2E',
            'amount'           => 100000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        // User A performs Checker action
        $checkResponse = Gate::forUser($userA)->inspect('check', $txn);
        $this->assertTrue($checkResponse->allowed());

        $txn->update([
            'status_id'     => BkashTransaction::STATUS_CHECKED,
            'checked_by'    => $userA->name,
            'checked_by_id' => $userA->id,
            'checked_at'    => Carbon::now(),
        ]);

        // Step 2: 1st Authorization
        $this->assertTrue(Gate::forUser($userA)->inspect('authorize', $txn)->denied());
        $this->assertTrue(Gate::forUser($userB)->inspect('authorize', $txn)->allowed());

        $txn->update([
            'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
            'approved_by_1'    => $userB->name,
            'approved_by_1_id' => $userB->id,
            'approved_at_1'    => Carbon::now(),
        ]);

        // Step 3: Final Confirmation
        $this->assertTrue(Gate::forUser($userA)->inspect('confirm', $txn)->denied());
        $this->assertTrue(Gate::forUser($userB)->inspect('confirm', $txn)->denied());
        $this->assertTrue(Gate::forUser($userC)->inspect('confirm', $txn)->allowed());

        $txn->update([
            'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
            'approved_by_2'    => $userC->name,
            'approved_by_2_id' => $userC->id,
            'approved_at_2'    => Carbon::now(),
            'confirmed_by'     => $userC->name,
            'confirmed_at'     => Carbon::now(),
        ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_FINAL_AUTHORIZED, $txn->status_id);
        $this->assertEquals($userA->id, $txn->checked_by_id);
        $this->assertEquals($userB->id, $txn->approved_by_1_id);
        $this->assertEquals($userC->id, $txn->approved_by_2_id);
    }
}
