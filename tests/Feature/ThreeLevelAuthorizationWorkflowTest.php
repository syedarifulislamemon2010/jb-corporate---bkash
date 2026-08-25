<?php

namespace Tests\Feature;

use App\Models\BkashTransaction;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ThreeLevelAuthorizationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['bkash.sms_enabled' => false]);
        Queue::fake();
    }

    public function test_three_tier_workflow_transitions_correctly_with_segregation_of_duties(): void
    {
        // 1. Create 3 distinct users from Janata Bank
        $checker = User::create([
            'name'         => 'Checker Person',
            'email'        => 'checker@janatabank.com',
            'mobile_no'    => '01711111111',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $auth1 = User::create([
            'name'         => 'Authorizer One',
            'email'        => 'auth1@janatabank.com',
            'mobile_no'    => '01722222222',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $auth2 = User::create([
            'name'         => 'Authorizer Two',
            'email'        => 'auth2@janatabank.com',
            'mobile_no'    => '01733333333',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        // Create transaction at Stage 1 (Pending Checker)
        $txn = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_STAGE_01',
            'txn_id'              => 'TXN_STAGE_01',
            'amount'              => 50000.00,
            'debit_account_no'    => '0100111111111',
            'credit_account_no'   => '0100202707747',
            'status_id'           => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        // Step 1: Checker verifies transaction -> Status 1001 (STATUS_CHECKED)
        $txn->update([
            'status_id'     => BkashTransaction::STATUS_CHECKED,
            'checked_by'    => $checker->name,
            'checked_by_id' => $checker->id,
            'checked_at'    => now(),
        ]);
        $this->assertEquals(BkashTransaction::STATUS_CHECKED, $txn->status_id);
        $this->assertEquals($checker->id, $txn->checked_by_id);

        // Step 2: 1st Authorizer approves -> Status 1002 (STATUS_AUTH_1_APPROVED)
        // Verify Segregation of Duties: checker cannot approve 1st level
        $this->assertNotEquals($checker->id, $auth1->id);

        // Verify Authorizations table selectability: checker cannot select, but auth1 can select
        Auth::login($checker);
        $authTable = \App\Filament\Resources\BkashTransactionAuthorizations\Tables\BkashTransactionAuthorizationsTable::configure(
            new \Filament\Tables\Table(new \Filament\Resources\Pages\ListRecords())
        );
        $this->assertFalse($authTable->isRecordSelectable($txn));

        Auth::login($auth1);
        $this->assertTrue($authTable->isRecordSelectable($txn));

        $txn->update([
            'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
            'approved_by_1'    => $auth1->name,
            'approved_by_1_id' => $auth1->id,
            'approved_at_1'    => now(),
        ]);
        $this->assertEquals(BkashTransaction::STATUS_AUTH_1_APPROVED, $txn->status_id);
        $this->assertEquals($auth1->id, $txn->approved_by_1_id);

        // Step 3: 2nd Authorizer approves -> Status 1003 (STATUS_FINAL_AUTHORIZED)
        // Verify Segregation of Duties: auth1 or checker cannot provide final approval
        $this->assertNotEquals($auth1->id, $auth2->id);
        $this->assertNotEquals($checker->id, $auth2->id);

        // Verify Confirmations table selectability: checker and auth1 cannot select, but auth2 can select
        $confirmTable = \App\Filament\Resources\BkashTransactionConfirmations\Tables\BkashTransactionConfirmationsTable::configure(
            new \Filament\Tables\Table(new \Filament\Resources\Pages\ListRecords())
        );
        Auth::login($checker);
        $this->assertFalse($confirmTable->isRecordSelectable($txn));

        Auth::login($auth1);
        $this->assertFalse($confirmTable->isRecordSelectable($txn));

        Auth::login($auth2);
        $this->assertTrue($confirmTable->isRecordSelectable($txn));

        $txn->update([
            'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
            'approved_by_2'    => $auth2->name,
            'approved_by_2_id' => $auth2->id,
            'approved_at_2'    => now(),
        ]);
        $this->assertEquals(BkashTransaction::STATUS_FINAL_AUTHORIZED, $txn->status_id);
        $this->assertEquals($auth2->id, $txn->approved_by_2_id);
    }

    public function test_stage_3_notification_is_scoped_to_authorizer_organization_excluding_actor(): void
    {
        // Organization 1: Janata Bank
        $jbAuth1 = User::create([
            'name'         => 'JB Authorizer 1',
            'email'        => 'jb_auth1@janatabank.com',
            'mobile_no'    => '01710000001',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $jbAuth2 = User::create([
            'name'         => 'JB Authorizer 2',
            'email'        => 'jb_auth2@janatabank.com',
            'mobile_no'    => '01710000002',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        // Organization 2: bKash Ltd
        $bkashUser = User::create([
            'name'         => 'bKash Officer',
            'email'        => 'officer@bkash.com',
            'mobile_no'    => '01820000001',
            'organization' => 'bKash',
            'password'     => bcrypt('Secret123!'),
        ]);

        // Trigger Stage 3 notification from JB Authorizer 1
        $outbox = NotificationService::dispatchStage3('Test_Batch_01.xlsx', 10, 100000.00, $jbAuth1->name, $jbAuth1);

        $this->assertEquals('STAGE_3_AUTH1', $outbox->event_type);

        // Verify recipient emails scoped to Janata Bank (excluding sender)
        $jbEmails = NotificationService::getRecipientEmails($jbAuth1->organization, $jbAuth1->id);
        $this->assertContains('jb_auth2@janatabank.com', $jbEmails);
        $this->assertNotContains('jb_auth1@janatabank.com', $jbEmails);
        $this->assertNotContains('officer@bkash.com', $jbEmails);

        // Verify recipient phones scoped to Janata Bank (excluding sender)
        $jbPhones = NotificationService::getRecipientPhones($jbAuth1->organization, $jbAuth1->id);
        $this->assertContains('01710000002', $jbPhones);
        $this->assertNotContains('01710000001', $jbPhones);
        $this->assertNotContains('01820000001', $jbPhones);
    }
}
