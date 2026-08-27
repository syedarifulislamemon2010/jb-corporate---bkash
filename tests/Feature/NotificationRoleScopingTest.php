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
}
