<?php

namespace Tests\Unit;

use App\Models\BkashTransaction;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationTemplateWordingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['bkash.sms_enabled' => false]);
        config(['bkash.email_enabled' => false]);
        Queue::fake();
    }

    public function test_stage_1_notification_body_matches_exact_requirement_wording(): void
    {
        $fileName = 'JANATA_BANK_2026_07_28_1Slot1.xlsx';
        $totalTrn = 10;
        $totalAmount = 50000.00;
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

        $outbox = NotificationService::dispatchStage1($fileName, $totalTrn, $totalAmount);

        $expectedPrefix = "Dear Sir/Madam,\n"
                        . "File Name: \"{$fileName}\"\n"
                        . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\" (amount should be in comma separator).\n"
                        . "File is pending for Checker. Please Check this file.\n"
                        . "Thank you\n"
                        . "Best Regards,\n"
                        . "JANATA BANK";

        $this->assertStringStartsWith($expectedPrefix, $outbox->sms_payload);
    }

    public function test_stage_2_notification_body_matches_exact_requirement_wording(): void
    {
        $fileName = 'JANATA_BANK_2026_07_28_1Slot1.xlsx';
        $totalTrn = 10;
        $totalAmount = 50000.00;
        $checkerName = 'Md. Checker';
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

        $outbox = NotificationService::dispatchStage2($fileName, $totalTrn, $totalAmount, $checkerName);

        $expectedExact = "Dear Sir/Madam,\n"
                       . "File Name: \"{$fileName}\"\n"
                       . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\" (amount should be in comma separator)\n"
                       . "is checked by \"{$checkerName}\" (Checker name) & is pending for further Authorization/Approval.\n"
                       . "Thank you\n"
                       . "JANATA BANK";

        $this->assertEquals($expectedExact, $outbox->sms_payload);
    }

    public function test_stage_3_notification_body_matches_exact_requirement_wording(): void
    {
        $fileName = 'JANATA_BANK_2026_07_28_1Slot1.xlsx';
        $totalTrn = 10;
        $totalAmount = 50000.00;
        $auth1Name = 'First Authorizer';
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

        $outbox = NotificationService::dispatchStage3($fileName, $totalTrn, $totalAmount, $auth1Name);

        $expectedExact = "Dear Sir/Madam,\n"
                       . "File Name: \"{$fileName}\"\n"
                       . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\" (amount should be in comma separator)\n"
                       . "is Authorized by \"{$auth1Name}\" (First Authorizer's name) & is pending for further Authorization/Approval or final authorization.\n"
                       . "Thank you\n"
                       . "JANATA BANK";

        $this->assertEquals($expectedExact, $outbox->sms_payload);
    }

    public function test_stage_4_notification_body_matches_exact_requirement_wording(): void
    {
        $fileName = 'JANATA_BANK_2026_07_28_1Slot1.xlsx';
        $totalTrn = 10;
        $totalAmount = 50000.00;
        $auth2Name = 'Second Authorizer';
        $formattedAmount = BkashTransaction::formatBdtAmount($totalAmount);

        $outbox = NotificationService::dispatchStage4($fileName, $totalTrn, $totalAmount, $auth2Name);

        $expectedExact = "Dear Sir/Madam,\n"
                       . "File Name: \"{$fileName}\"\n"
                       . "Total Trn: \"{$totalTrn}\", Total Amount: \"{$formattedAmount}\" (amount should be in comma separator)\n"
                       . "is Authorized by \"{$auth2Name}\" (Second Authorizer's name) & is finally authorized.\n"
                       . "Thank you\n"
                       . "JANATA BANK";

        $this->assertEquals($expectedExact, $outbox->sms_payload);
    }
}
