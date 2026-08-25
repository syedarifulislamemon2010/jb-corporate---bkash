<?php

namespace Tests\Unit;

use App\Helper\ValueDateHelper;
use App\Models\BkashTransaction;
use App\Services\Mt940GeneratorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ValueDateAndMt940HolidayTest extends TestCase
{
    use RefreshDatabase;

    public function test_value_date_resolves_same_day_during_business_hours(): void
    {
        // Wednesday at 11:30 AM (Regular business hours)
        $date = Carbon::parse('2026-08-26 11:30:00'); // Wednesday
        $resolved = ValueDateHelper::resolve($date);

        $this->assertEquals('2026-08-26', $resolved->toDateString());
    }

    public function test_value_date_resolves_next_day_after_business_hours(): void
    {
        Config::set('bkash.business_hours_end', '16:00');

        // Wednesday at 17:30 PM (After 16:00 cut-off)
        $date = Carbon::parse('2026-08-26 17:30:00'); // Wednesday
        $resolved = ValueDateHelper::resolve($date);

        $this->assertEquals('2026-08-27', $resolved->toDateString()); // Thursday
    }

    public function test_value_date_skips_friday_and_saturday_weekend(): void
    {
        Config::set('bkash.business_hours_end', '16:00');

        // Thursday at 18:00 PM (After business hours -> should advance to Sunday)
        $thursdayEvening = Carbon::parse('2026-08-27 18:00:00'); // Thursday
        $resolved = ValueDateHelper::resolve($thursdayEvening);

        $this->assertEquals('2026-08-30', $resolved->toDateString()); // Sunday

        // Friday transaction during day -> should advance to Sunday
        $friday = Carbon::parse('2026-08-28 10:00:00'); // Friday
        $resolvedFriday = ValueDateHelper::resolve($friday);

        $this->assertEquals('2026-08-30', $resolvedFriday->toDateString()); // Sunday
    }

    public function test_value_date_skips_bank_holidays(): void
    {
        Config::set('bkash.bank_holidays', ['2026-09-01']); // Tuesday holiday

        // Monday evening at 19:00 PM -> Tuesday is holiday -> should advance to Wednesday
        $mondayEvening = Carbon::parse('2026-08-31 19:00:00');
        $resolved = ValueDateHelper::resolve($mondayEvening);

        $this->assertEquals('2026-09-02', $resolved->toDateString()); // Wednesday
    }

    public function test_mt940_generator_queries_by_value_date(): void
    {
        $account = '0100202707747';
        $targetDate = Carbon::parse('2026-08-30'); // Sunday value date

        // 1. Transaction created on Friday (2026-08-28), settled on Friday, but value_date is Sunday (2026-08-30)
        $txn = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_VALUE_DATE_TEST',
            'txn_id'              => 'TXN_VALUE_DATE_TEST',
            'amount'              => 50000.00,
            'credit_account_no'   => $account,
            'debit_account_no'    => '0100224107522',
            'status_id'           => BkashTransaction::STATUS_CBS_SUCCESS,
            'create_date'         => Carbon::parse('2026-08-28 19:00:00'),
            'value_date'          => '2026-08-30',
        ]);
        // Update updated_at to Friday date to simulate execution on holiday
        $txn->timestamps = false;
        $txn->updated_at = Carbon::parse('2026-08-28 19:05:00');
        $txn->save();

        // 2. Generate MT940 for the value date (2026-08-30)
        $statement = Mt940GeneratorService::generateStatement($account, $targetDate);

        // 3. Assert the transaction is included in the statement based on value_date
        $this->assertStringContainsString('REF_VALUE_DATE_TEST', $statement);
        $this->assertStringContainsString('000050000,00', $statement);
    }
}
