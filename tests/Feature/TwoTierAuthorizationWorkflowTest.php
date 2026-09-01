<?php

namespace Tests\Feature;

use App\Models\BkashTransaction;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TwoTierAuthorizationWorkflowTest extends TestCase
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
        // 1. Create 3 distinct users from Janata Bank (Checker, 1st Authorizer, 2nd Authorizer)
        $checker = User::create([
            'name'         => 'Checker Person',
            'email'        => 'checker@janatabank.com',
            'mobile_no'    => '01700000000',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer1 = User::create([
            'name'         => 'Authorizer Person 1',
            'email'        => 'authorizer1@janatabank.com',
            'mobile_no'    => '01711111111',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer2 = User::create([
            'name'         => 'Confirmer Person 2',
            'email'        => 'confirmer2@janatabank.com',
            'mobile_no'    => '01722222222',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        // Create transaction at Stage 1 (Pending Checker)
        $txn = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_STAGE_01',
            'txn_id'              => 'TXN_STAGE_01',
            'amount'              => 50000.00,
            'beneficiary_account_no'    => '0100111111111',
            'source_account_no'   => '0100202707747',
            'status_id'           => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        // Step 1: Checker checks transaction -> Status 1001 (STATUS_CHECKED)
        $txn->update([
            'status_id'     => BkashTransaction::STATUS_CHECKED,
            'checked_by'    => $checker->name,
            'checked_by_id' => $checker->id,
            'checked_at'    => now(),
        ]);
        $this->assertEquals(BkashTransaction::STATUS_CHECKED, $txn->status_id);
        $this->assertEquals($checker->id, $txn->checked_by_id);

        // Step 2: Segregation of Duties on 1st Authorization Table
        // Checker cannot select their own checked transaction for 1st authorization
        $auth1Table = \App\Filament\Resources\BkashTransactionAuthorizations\Tables\BkashTransactionAuthorizationsTable::configure(
            new \Filament\Tables\Table(new \Filament\Resources\Pages\ListRecords())
        );

        Auth::login($checker);
        $this->assertFalse($auth1Table->isRecordSelectable($txn));

        // 1st Authorizer (different user) can select
        Auth::login($authorizer1);
        $this->assertTrue($auth1Table->isRecordSelectable($txn));

        // Authorizer 1 authorizes -> Status 1002 (STATUS_AUTH_1_APPROVED)
        $txn->update([
            'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
            'approved_by_1'    => $authorizer1->name,
            'approved_by_1_id' => $authorizer1->id,
            'approved_at_1'    => now(),
        ]);
        $this->assertEquals(BkashTransaction::STATUS_AUTH_1_APPROVED, $txn->status_id);

        // Step 3: Segregation of Duties on 2nd Authorization / Confirmation Table
        $confirmTable = \App\Filament\Resources\BkashTransactionConfirmations\Tables\BkashTransactionConfirmationsTable::configure(
            new \Filament\Tables\Table(new \Filament\Resources\Pages\ListRecords())
        );

        // Neither Checker nor 1st Authorizer can select for final confirmation
        Auth::login($checker);
        $this->assertFalse($confirmTable->isRecordSelectable($txn));

        Auth::login($authorizer1);
        $this->assertFalse($confirmTable->isRecordSelectable($txn));

        // 2nd Authorizer (third distinct user) can select
        Auth::login($authorizer2);
        $this->assertTrue($confirmTable->isRecordSelectable($txn));

        // 2nd Authorizer confirms -> Status 1003 (STATUS_FINAL_AUTHORIZED)
        $txn->update([
            'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
            'approved_by_2'    => $authorizer2->name,
            'approved_by_2_id' => $authorizer2->id,
            'approved_at_2'    => now(),
            'confirmed_by'     => $authorizer2->name,
            'confirmed_at'     => now(),
        ]);
        $this->assertEquals(BkashTransaction::STATUS_FINAL_AUTHORIZED, $txn->status_id);
        $this->assertEquals($authorizer2->id, $txn->approved_by_2_id);
    }

    public function test_stage_2_notification_is_scoped_to_authorizer_organization_excluding_actor(): void
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
            'name'         => 'JB Confirmer 2',
            'email'        => 'jb_confirmer@janatabank.com',
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

        // Trigger Stage 2 notification from JB Authorizer 1
        $outbox = NotificationService::dispatchStage2('Test_Batch_01.xlsx', 10, 100000.00, $jbAuth1->name, $jbAuth1);

        $this->assertEquals('STAGE_2_CHECKED', $outbox->event_type);

        // Verify recipient emails scoped to Janata Bank (excluding sender)
        $jbEmails = NotificationService::getRecipientEmails($jbAuth1->organization, $jbAuth1->id);
        $this->assertContains('jb_confirmer@janatabank.com', $jbEmails);
        $this->assertNotContains('jb_auth1@janatabank.com', $jbEmails);
        $this->assertNotContains('officer@bkash.com', $jbEmails);

        // Verify recipient phones scoped to Janata Bank (excluding sender)
        $jbPhones = NotificationService::getRecipientPhones($jbAuth1->organization, $jbAuth1->id);
        $this->assertContains('01710000002', $jbPhones);
        $this->assertNotContains('01710000001', $jbPhones);
        $this->assertNotContains('01820000001', $jbPhones);
    }
}
