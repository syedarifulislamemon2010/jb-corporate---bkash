<?php

namespace Tests\Feature;

use App\Jobs\ExecuteCbsSettlementJob;
use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use App\Models\PostingAttempt;
use App\Services\CbsApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTransactionAtomicityTest extends TestCase
{
    use RefreshDatabase;

    protected string $apiKey = 'test-atomicity-api-key-999';

    protected function setUp(): void
    {
        parent::setUp();
        config(['bkash.cbs_callback_api_key' => $this->apiKey]);
    }

    public function test_cbs_callback_failure_creates_failed_transaction_and_updates_status_atomically(): void
    {
        $txn = BkashTransaction::create([
            'transaction_type'  => 'BEFTN',
            'reference_id'      => 'REF_ATOMIC_FAIL_01',
            'txn_id'            => 'TXN_ATOMIC_FAIL_01',
            'amount'            => 35000.00,
            'source_account_no' => '0100202707747',
            'beneficiary_account_no'  => '0100987654321',
            'status_id'         => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $response = $this->withHeaders([
            'X-CBS-API-Key' => $this->apiKey,
        ])->postJson('/api/cbs/response-callback', [
            'response_id'  => 'CBS_RESP_ATOMIC_01',
            'status_id'    => 1007,
            'reference_id' => 'REF_ATOMIC_FAIL_01',
            'reason'       => 'BEFTN batch clearing network error',
        ]);

        $response->assertStatus(200);

        $txn->refresh();

        // 1. Transaction updated to STATUS_CBS_RESPONSE_FAILED
        $this->assertEquals(BkashTransaction::STATUS_CBS_RESPONSE_FAILED, $txn->status_id);
        $this->assertEquals('CBS_RESP_ATOMIC_01', $txn->response_id);
        $this->assertEquals('BEFTN batch clearing network error', $txn->reject_reason);

        // 2. Failed transaction record created atomically
        $failedTxn = BkashFailedTransaction::where('reference_id', 'REF_ATOMIC_FAIL_01')->first();
        $this->assertNotNull($failedTxn);
        $this->assertEquals('CBS_CALLBACK_REJECTED', $failedTxn->failure_code);
        $this->assertEquals('BEFTN batch clearing network error', $failedTxn->reject_reason);
        $this->assertEquals(35000.00, (float) $failedTxn->amount);
    }

    public function test_cbs_settlement_job_atomically_updates_attempt_and_transaction_on_success(): void
    {
        $txn = BkashTransaction::create([
            'transaction_type'  => 'A2A',
            'reference_id'      => 'REF_ATOMIC_JOB_OK',
            'txn_id'            => 'TXN_ATOMIC_JOB_OK',
            'amount'            => 20000.00,
            'source_account_no' => '0100202707747',
            'beneficiary_account_no'  => '0100555555555',
            'status_id'         => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $mockCbsService = $this->createMock(CbsApiService::class);
        $mockCbsService->expects($this->once())
            ->method('settleTransaction')
            ->willReturn([
                'success'     => true,
                'status_code' => 200,
                'message'     => 'Settled successfully',
            ]);

        $job = new ExecuteCbsSettlementJob([$txn->id]);
        $job->handle($mockCbsService);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_CBS_SUCCESS, $txn->status_id);
        $this->assertNotNull($txn->cbs_success_at);

        $attempt = PostingAttempt::where('txn_id', $txn->txn_id)->first();
        $this->assertNotNull($attempt);
        $this->assertEquals('SUCCESS', $attempt->outcome);
    }

    public function test_cbs_settlement_job_atomically_updates_attempt_and_creates_failed_record_on_failure(): void
    {
        $txn = BkashTransaction::create([
            'transaction_type'  => 'RTGS',
            'reference_id'      => 'REF_ATOMIC_JOB_FAIL',
            'txn_id'            => 'TXN_ATOMIC_JOB_FAIL',
            'amount'            => 150000.00,
            'source_account_no' => '0100202707747',
            'beneficiary_account_no'  => '0100666666666',
            'status_id'         => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $mockCbsService = $this->createMock(CbsApiService::class);
        $mockCbsService->expects($this->once())
            ->method('settleTransaction')
            ->willReturn([
                'success'      => false,
                'failure_code' => 'INVALID_ROUTING',
                'reject_reason' => 'Routing number not recognized by clearing house',
            ]);

        $job = new ExecuteCbsSettlementJob([$txn->id]);
        $job->handle($mockCbsService);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_REJECTED, $txn->status_id);
        $this->assertEquals('Routing number not recognized by clearing house', $txn->reject_reason);

        $attempt = PostingAttempt::where('txn_id', $txn->txn_id)->first();
        $this->assertNotNull($attempt);
        $this->assertEquals('FAILED', $attempt->outcome);

        $failed = BkashFailedTransaction::where('reference_id', 'REF_ATOMIC_JOB_FAIL')->first();
        $this->assertNotNull($failed);
        $this->assertEquals('INVALID_ROUTING', $failed->failure_code);
        $this->assertEquals('Routing number not recognized by clearing house', $failed->reject_reason);
    }
}