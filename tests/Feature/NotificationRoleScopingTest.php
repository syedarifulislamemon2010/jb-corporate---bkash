<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationRoleScopingTest extends TestCase
{
    use RefreshDatabase;

    protected User $checker;
    protected User $auth1;
    protected User $auth2;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'bkash.email_enabled' => true,
            'bkash.sms_enabled'   => false, // test email dispatching
        ]);

        // 1. Ensure Roles exist
        Role::firstOrCreate(['name' => 'bkash_checker', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bkash_authorizer_1', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bkash_authorizer_2', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        // 2. Create 4 distinct users in the same organization
        $this->checker = User::create([
            'name'         => 'Checker User',
            'email'        => 'checker@jb.com',
            'mobile_no'    => '01711111111',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);
        $this->checker->assignRole('bkash_checker');

        $this->auth1 = User::create([
            'name'         => '1st Authorizer User',
            'email'        => 'auth1@jb.com',
            'mobile_no'    => '01722222222',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);
        $this->auth1->assignRole('bkash_authorizer_1');

        $this->auth2 = User::create([
            'name'         => '2nd Authorizer User',
            'email'        => 'auth2@jb.com',
            'mobile_no'    => '01733333333',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);
        $this->auth2->assignRole('bkash_authorizer_2');

        $this->admin = User::create([
            'name'         => 'Admin User',
            'email'        => 'admin@jb.com',
            'mobile_no'    => '01744444444',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);
        $this->admin->assignRole('Admin');
    }

    public function test_get_recipient_emails_filters_strictly_by_role_names(): void
    {
        // Stage 1 role scope: only bkash_checker
        $stage1Emails = NotificationService::getRecipientEmails(
            organization: 'Janata Bank',
            roleNames: ['bkash_checker']
        );
        $this->assertContains('checker@jb.com', $stage1Emails);
        $this->assertNotContains('auth1@jb.com', $stage1Emails);
        $this->assertNotContains('auth2@jb.com', $stage1Emails);
        $this->assertNotContains('admin@jb.com', $stage1Emails);

        // Stage 2 role scope: bkash_checker and bkash_authorizer_1
        $stage2Emails = NotificationService::getRecipientEmails(
            organization: 'Janata Bank',
            roleNames: ['bkash_checker', 'bkash_authorizer_1']
        );
        $this->assertContains('checker@jb.com', $stage2Emails);
        $this->assertContains('auth1@jb.com', $stage2Emails);
        $this->assertNotContains('auth2@jb.com', $stage2Emails);
        $this->assertNotContains('admin@jb.com', $stage2Emails);

        // Stage 3 & 4 role scope: bkash_checker, bkash_authorizer_1, bkash_authorizer_2
        $stage3Emails = NotificationService::getRecipientEmails(
            organization: 'Janata Bank',
            roleNames: ['bkash_checker', 'bkash_authorizer_1', 'bkash_authorizer_2']
        );
        $this->assertContains('checker@jb.com', $stage3Emails);
        $this->assertContains('auth1@jb.com', $stage3Emails);
        $this->assertContains('auth2@jb.com', $stage3Emails);
        $this->assertNotContains('admin@jb.com', $stage3Emails);
    }

    public function test_get_recipient_phones_filters_strictly_by_role_names(): void
    {
        $stage1Phones = NotificationService::getRecipientPhones(
            organization: 'Janata Bank',
            roleNames: ['bkash_checker']
        );
        $this->assertContains('01711111111', $stage1Phones);
        $this->assertNotContains('01722222222', $stage1Phones);
        $this->assertNotContains('01733333333', $stage1Phones);
        $this->assertNotContains('01744444444', $stage1Phones);
    }

    public function test_dispatch_stage_1_notifies_only_bkash_checker_and_excludes_admin_and_authorizers(): void
    {
        Mail::fake();

        $outbox = NotificationService::dispatchStage1(
            fileName: 'JANATA_BANK_2026_08_27_Slot1.xlsx',
            totalTrn: 10,
            totalAmount: 50000.00,
            senderUser: $this->admin
        );

        $this->assertNotNull($outbox);
        $this->assertEquals('STAGE_1_SFTP', $outbox->event_type);

        $recipients = NotificationService::getRecipientEmails(
            organization: 'Janata Bank',
            excludeUserId: $this->admin->id,
            roleNames: ['bkash_checker']
        );

        $this->assertContains('checker@jb.com', $recipients);
        $this->assertNotContains('auth1@jb.com', $recipients);
        $this->assertNotContains('auth2@jb.com', $recipients);
        $this->assertNotContains('admin@jb.com', $recipients);
    }

    public function test_dispatch_stage_2_notifies_authorizer_1_and_excludes_actor_checker_and_admin(): void
    {
        Mail::fake();

        $outbox = NotificationService::dispatchStage2(
            fileName: 'JANATA_BANK_2026_08_27_Slot1.xlsx',
            totalTrn: 10,
            totalAmount: 50000.00,
            authorizerName: $this->checker->name,
            senderUser: $this->checker
        );

        $this->assertNotNull($outbox);
        $this->assertEquals('STAGE_2_CHECKED', $outbox->event_type);

        $recipients = NotificationService::getRecipientEmails(
            organization: 'Janata Bank',
            excludeUserId: $this->checker->id,
            roleNames: ['bkash_checker', 'bkash_authorizer_1']
        );

        $this->assertNotContains('checker@jb.com', $recipients); // actor excluded
        $this->assertContains('auth1@jb.com', $recipients);
        $this->assertNotContains('auth2@jb.com', $recipients);
        $this->assertNotContains('admin@jb.com', $recipients);
    }

    public function test_dispatch_stage_3_notifies_checker_and_authorizer_2_excluding_actor_auth_1_and_admin(): void
    {
        Mail::fake();

        $outbox = NotificationService::dispatchStage3(
            fileName: 'JANATA_BANK_2026_08_27_Slot1.xlsx',
            totalTrn: 10,
            totalAmount: 50000.00,
            authorizerName1: $this->auth1->name,
            senderUser: $this->auth1
        );

        $this->assertNotNull($outbox);
        $this->assertEquals('STAGE_3_AUTH1', $outbox->event_type);

        $recipients = NotificationService::getRecipientEmails(
            organization: 'Janata Bank',
            excludeUserId: $this->auth1->id,
            roleNames: ['bkash_checker', 'bkash_authorizer_1', 'bkash_authorizer_2']
        );

        $this->assertContains('checker@jb.com', $recipients);
        $this->assertNotContains('auth1@jb.com', $recipients); // actor excluded
        $this->assertContains('auth2@jb.com', $recipients);
        $this->assertNotContains('admin@jb.com', $recipients);
    }

    public function test_dispatch_stage_4_notifies_checker_and_authorizer_1_excluding_actor_auth_2_and_admin(): void
    {
        Mail::fake();

        $outbox = NotificationService::dispatchStage4(
            fileName: 'JANATA_BANK_2026_08_27_Slot1.xlsx',
            totalTrn: 10,
            totalAmount: 50000.00,
            confirmerName: $this->auth2->name,
            senderUser: $this->auth2
        );

        $this->assertNotNull($outbox);
        $this->assertEquals('STAGE_4_AUTH2', $outbox->event_type);

        $recipients = NotificationService::getRecipientEmails(
            organization: 'Janata Bank',
            excludeUserId: $this->auth2->id,
            roleNames: ['bkash_checker', 'bkash_authorizer_1', 'bkash_authorizer_2']
        );

        $this->assertContains('checker@jb.com', $recipients);
        $this->assertContains('auth1@jb.com', $recipients);
        $this->assertNotContains('auth2@jb.com', $recipients); // actor excluded
        $this->assertNotContains('admin@jb.com', $recipients);
    }

    public function test_checker_action_notifies_all_janata_bank_users_excluding_actor_and_bkash(): void
    {
        Mail::fake();

        $bkashUser = User::create([
            'name'         => 'bKash Operator',
            'email'        => 'operator@bkash.com',
            'mobile_no'    => '01811000005',
            'organization' => 'bKash Limited',
            'password'     => bcrypt('Secret123!'),
        ]);

        NotificationService::dispatchStage2(
            fileName: 'JANATA_BANK_2026_09_06_Slot1.xlsx',
            totalTrn: 15,
            totalAmount: 75000.00,
            authorizerName: $this->checker->name,
            senderUser: $this->checker
        );

        // 1. Actor (Checker) excluded
        $this->assertEquals(0, $this->checker->notifications()->count());

        // 2. Both Authorizers and Admin in Janata Bank received database notifications
        $this->assertEquals(1, $this->auth1->notifications()->count());
        $this->assertEquals(1, $this->auth2->notifications()->count());
        $this->assertEquals(1, $this->admin->notifications()->count());

        // 3. bKash user isolated
        $this->assertEquals(0, $bkashUser->notifications()->count());
    }

    public function test_authorizer_1_action_notifies_all_janata_bank_users_excluding_actor(): void
    {
        Mail::fake();

        NotificationService::dispatchStage3(
            fileName: 'JANATA_BANK_2026_09_06_Slot1.xlsx',
            totalTrn: 15,
            totalAmount: 75000.00,
            authorizerName1: $this->auth1->name,
            senderUser: $this->auth1
        );

        $this->assertEquals(0, $this->auth1->notifications()->count()); // Actor excluded
        $this->assertEquals(1, $this->checker->notifications()->count());
        $this->assertEquals(1, $this->auth2->notifications()->count());
        $this->assertEquals(1, $this->admin->notifications()->count());
    }

    public function test_authorizer_2_action_notifies_all_janata_bank_users_excluding_actor(): void
    {
        Mail::fake();

        NotificationService::dispatchStage4(
            fileName: 'JANATA_BANK_2026_09_06_Slot1.xlsx',
            totalTrn: 15,
            totalAmount: 75000.00,
            confirmerName: $this->auth2->name,
            senderUser: $this->auth2
        );

        $this->assertEquals(0, $this->auth2->notifications()->count()); // Actor excluded
        $this->assertEquals(1, $this->checker->notifications()->count());
        $this->assertEquals(1, $this->auth1->notifications()->count());
        $this->assertEquals(1, $this->admin->notifications()->count());
    }

    public function test_dispatch_workflow_notification_dynamically_notifies_authorizers(): void
    {
        Mail::fake();

        $batch = \App\Models\BkashTransactionBatch::create([
            'file_name'          => 'JB_WORKFLOW_TEST.xlsx',
            'transaction_type'   => 'A2A',
            'total_transactions' => 5,
            'total_amount'       => 25000.00,
            'status_id'          => \App\Models\BkashTransaction::STATUS_PENDING_CHECKER,
            'create_date'        => \Carbon\Carbon::today(),
        ]);

        $txn = \App\Models\BkashTransaction::create([
            'batch_id'         => $batch->id,
            'file_name'        => 'JB_WORKFLOW_TEST.xlsx',
            'transaction_type' => 'A2A',
            'txn_id'           => 'TXN12345678',
            'channel'          => 'A2A',
            'beneficiary_acc'  => '01712345678',
            'amount'           => 5000.00,
            'status_id'        => \App\Models\BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        $outbox = NotificationService::dispatchWorkflowNotification(
            stage: 2,
            transaction: $txn,
            actorName: $this->checker->name,
            actorId: $this->checker->id
        );

        $this->assertNotNull($outbox);
        $this->assertEquals('STAGE_2_CHECKED', $outbox->event_type);
        $this->assertEquals('JB_WORKFLOW_TEST.xlsx', $outbox->file_name);

        $this->assertEquals(0, $this->checker->notifications()->count());
        $this->assertEquals(1, $this->auth1->notifications()->count());
        $this->assertEquals(1, $this->auth2->notifications()->count());
    }
}
