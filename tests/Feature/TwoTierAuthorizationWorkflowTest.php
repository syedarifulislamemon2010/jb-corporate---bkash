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

    public function test_two_tier_workflow_transitions_correctly_with_segregation_of_duties(): void
    {
        // 1. Create 2 distinct users from Janata Bank (Authorizer & Confirmer)
        $authorizer = User::create([
            'name'         => 'Authorizer Person',
            'email'        => 'authorizer@janatabank.com',
            'mobile_no'    => '01711111111',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $confirmer = User::create([
            'name'         => 'Confirmer Person',
            'email'        => 'confirmer@janatabank.com',
            'mobile_no'    => '01722222222',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        // Create transaction at Stage 1 (Pending Authorization)
        $txn = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_STAGE_01',
            'txn_id'              => 'TXN_STAGE_01',
            'amount'              => 50000.00,
            'debit_account_no'    => '0100111111111',
            'credit_account_no'   => '0100202707747',
            'status_id'           => BkashTransaction::STATUS_PENDING_AUTHORIZATION,
        ]);

        // Step 1: Authorizer authorizes transaction -> Status 1001 (STATUS_AUTHORIZED)
        $txn->update([
            'status_id'        => BkashTransaction::STATUS_AUTHORIZED,
            'approved_by_1'    => $authorizer->name,
            'approved_by_1_id' => $authorizer->id,
            'approved_at_1'    => now(),
        ]);
        $this->assertEquals(BkashTransaction::STATUS_AUTHORIZED, $txn->status_id);
        $this->assertEquals($authorizer->id, $txn->approved_by_1_id);

        // Step 2: 2-Person Segregation of Duties on Confirmation Table
        // Authorizer cannot select their own transaction for final confirmation
        $confirmTable = \App\Filament\Resources\BkashTransactionConfirmations\Tables\BkashTransactionConfirmationsTable::configure(
            new \Filament\Tables\Table(new \Filament\Resources\Pages\ListRecords())
        );

        Auth::login($authorizer);
        $this->assertFalse($confirmTable->isRecordSelectable($txn));

        // Confirmer (different user) can select the transaction
        Auth::login($confirmer);
        $this->assertTrue($confirmTable->isRecordSelectable($txn));

        // Step 3: Confirmer confirms transaction -> Status 1003 (STATUS_FINAL_AUTHORIZED / STATUS_CONFIRMED)
        $txn->update([
            'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
            'approved_by_2'    => $confirmer->name,
            'approved_by_2_id' => $confirmer->id,
            'approved_at_2'    => now(),
            'confirmed_by'     => $confirmer->name,
            'confirmed_at'     => now(),
        ]);
        $this->assertEquals(BkashTransaction::STATUS_FINAL_AUTHORIZED, $txn->status_id);
        $this->assertEquals($confirmer->id, $txn->approved_by_2_id);
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
