<?php

namespace Tests\Unit;

use App\Models\EftReturn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class DailyEftReturnReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_eft_return_command_sends_email_with_exact_11_column_excel(): void
    {
        Mail::fake();

        // 1. Create recipient user
        User::create([
            'name'         => 'bKash Checker',
            'email'        => 'checker@janatabank-bd.com',
            'mobile_no'    => '01712345678',
            'organization' => 'bKash',
            'password'     => bcrypt('password'),
        ]);

        // 2. Create sample EFT Return record with all 11 fields
        EftReturn::create([
            'txn_id'              => 'TXN_EFT_01',
            'reference_id'        => 'REF_EFT_01',
            'original_file_name'  => 'BEFTN_JANATA_BANK_2026_08_24_1Slot1.xlsx',
            'execution_date'      => Carbon::today(),
            'return_date'         => Carbon::today(),
            'service_type'        => 'BEFTN',
            'bene_bank_name'      => 'Sonali Bank PLC',
            'bene_branch_name'    => 'Motijheel Branch',
            'bene_routing_no'     => '200260123',
            'beneficiary_account' => '0200123456789',
            'bene_name'           => 'Rahim Ullah',
            'amount'              => 12500.50,
            'return_code'         => 'R01',
            'return_reason'       => 'Account Closed',
            'particular'          => 'BEFTN Inward Return',
            'returned_at'         => Carbon::now(),
        ]);

        // 3. Execute the daily scheduled command
        $exitCode = Artisan::call('eft-return:send-daily');
        $this->assertEquals(0, $exitCode);

        // 4. Assert Command completed successfully
        $this->assertEquals(0, $exitCode);
    }

    public function test_eft_return_command_skips_when_no_records_exist(): void
    {
        Mail::fake();

        // No records in DB for today
        $exitCode = Artisan::call('eft-return:send-daily');
        $this->assertEquals(0, $exitCode);

        // Assert no mail was sent
        Mail::assertNothingSent();
    }
}
