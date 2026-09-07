<?php

namespace Tests\Feature;

use App\Jobs\ExecuteCbsSettlementJob;
use App\Models\BkashTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CbsSettlementJobReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_has_retry_and_timeout_configuration(): void
    {
        $job = new ExecuteCbsSettlementJob([1, 2, 3]);

        $this->assertSame(3, $job->tries);
        $this->assertSame(120, $job->timeout);
        $this->assertSame(3, $job->maxExceptions);
        $this->assertEquals([10, 30, 60], $job->backoff());
    }

    public function test_failed_handler_marks_transaction_with_review_note(): void
    {
        $txn = BkashTransaction::create([
            'transaction_type'  => 'A2A',
            'reference_id'      => 'REF_FAIL_JOB_01',
            'txn_id'            => 'TXN_FAIL_JOB_01',
            'amount'            => 50000.00,
            'source_account_no' => '0100202707747',
            'beneficiary_account_no'  => '0100123456789',
            'status_id'         => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $job = new ExecuteCbsSettlementJob([$txn->id]);
        $exception = new \RuntimeException('Gateway connection timed out after 3 retries');

        // Trigger the failed handler directly as Laravel Queue worker would
        $job->failed($exception);

        $txn->refresh();

        // Transaction status must remain STATUS_FINAL_AUTHORIZED (business decision left to manual review)
        $this->assertEquals(BkashTransaction::STATUS_FINAL_AUTHORIZED, $txn->status_id);

        // Reject reason must contain manual review notice and the exception message
        $this->assertNotNull($txn->reject_reason);
        $this->assertStringContainsString('manual review', $txn->reject_reason);
        $this->assertStringContainsString('Gateway connection timed out', $txn->reject_reason);
    }
}