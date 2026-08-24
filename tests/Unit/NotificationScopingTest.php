<?php

namespace Tests\Unit;

use App\Models\BkashTransactionBatch;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationScopingTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_1_notification_includes_timestamp_and_today_file_count(): void
    {
        Mail::fake();
        \Illuminate\Support\Facades\Http::fake();
        config(['bkash.sms_enabled' => false]);

        // 1. Create batches for today
        BkashTransactionBatch::create([
            'file_name'        => 'JANATA_BANK_2026_08_24_1Slot1.xlsx',
            'transaction_type' => 'A2A',
            'total_data'       => 2,
            'total_amount'     => 5000.00,
            'create_date'      => Carbon::today(),
        ]);

        $uploader = User::create([
            'name'         => 'Uploader User',
            'email'        => 'uploader@jb.com',
            'mobile_no'    => '01711111111',
            'organization' => 'JB',
            'password'     => bcrypt('123'),
        ]);

        $checker = User::create([
            'name'         => 'Checker User',
            'email'        => 'checker@jb.com',
            'mobile_no'    => '01722222222',
            'organization' => 'JB',
            'password'     => bcrypt('123'),
        ]);

        // 2. Dispatch Stage 1 with explicit uploader
        $outbox = NotificationService::dispatchStage1('JANATA_BANK_2026_08_24_2Slot1.xlsx', 5, 25000.00, $uploader);

        $this->assertNotNull($outbox);
        $this->assertStringContainsString('Upload Time:', $outbox->email_payload);
        $this->assertStringContainsString('Total Files Uploaded Today: 1', $outbox->email_payload);
        $this->assertStringContainsString('JANATA_BANK_2026_08_24_2Slot1.xlsx', $outbox->email_payload);
    }

    public function test_recipient_lookup_excludes_actor_user_id(): void
    {
        $user1 = User::create(['name' => 'User One', 'email' => 'user1@jb.com', 'mobile_no' => '01700000001', 'organization' => 'JB', 'password' => bcrypt('123')]);
        $user2 = User::create(['name' => 'User Two', 'email' => 'user2@jb.com', 'mobile_no' => '01700000002', 'organization' => 'JB', 'password' => bcrypt('123')]);
        $user3 = User::create(['name' => 'User Three', 'email' => 'user3@other.com', 'mobile_no' => '01700000003', 'organization' => 'Other', 'password' => bcrypt('123')]);

        // Exclude User 1
        $emails = NotificationService::getRecipientEmails(excludeUserId: $user1->id);
        $this->assertNotContains('user1@jb.com', $emails);
        $this->assertContains('user2@jb.com', $emails);
        $this->assertContains('user3@other.com', $emails);

        $phones = NotificationService::getRecipientPhones(excludeUserId: $user1->id);
        $this->assertNotContains('01700000001', $phones);
        $this->assertContains('01700000002', $phones);
    }
}
