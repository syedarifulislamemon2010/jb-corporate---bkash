<?php

namespace Tests\Unit;

use App\Jobs\ExecuteCbsSettlementJob;
use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use App\Services\CbsApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DormantAccountHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cbs_api_detects_dormant_account_and_normalizes_failure_code(): void
    {
        Http::fake([
            '*/api/login' => Http::response([
                'status' => 'APPROVED',
                'token'  => 'mock_token_123',
            ], 200),
            '*/api/bkash-transactions' => Http::response([
                'status'     => 'FAILED',
                'error_code' => 'DORMANT_ACC_01',
                'message'    => 'Account 0100999999999 is DORMANT. Posting not allowed.',
            ], 422),
        ]);

        $txn = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_DORMANT_TEST_01',
            'txn_id'              => 'TXN_DORMANT_TEST_01',
            'amount'              => 12000.00,
            'credit_account_no'   => '0100202707747',
            'debit_account_no'    => '0100999999999',
            'status_id'           => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $service = new CbsApiService();
        $result = $service->settleTransaction($txn);

        $this->assertFalse($result['success']);
        $this->assertEquals('DORMANT_ACCOUNT', $result['failure_code']);
        $this->assertStringContainsString('Account 0100999999999 is DORMANT', $result['reject_reason']);
    }

    public function test_batch_settles_valid_transactions_and_records_dormant_transactions_separately(): void
    {
        Http::fake([
            '*/api/login' => Http::response([
                'status' => 'APPROVED',
                'token'  => 'mock_token_123',
            ], 200),
            '*/api/bkash-transactions' => function ($request) {
                $payload = $request->data();
                if (($payload['creditAccount'] ?? '') === '0100222222222' || ($payload['uniqueId'] ?? '') === 'TXN_BATCH_02') {
                    return Http::response([
                        'status'     => 'REJECTED',
                        'error_code' => 'DORMANT_ACCOUNT',
                        'message'    => 'Account is DORMANT and restricted by bank.',
                    ], 400);
                }
                return Http::response(['status' => 'APPROVED', 'message' => 'Success'], 200);
            },
        ]);

        $txn1 = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_BATCH_01',
            'txn_id'              => 'TXN_BATCH_01',
            'amount'              => 5000.00,
            'credit_account_no'   => '0100202707747',
            'debit_account_no'    => '0100111111111',
            'status_id'           => BkashTransaction::STATUS_FINAL_AUTHORIZED,
            'row_sequence'        => 0,
        ]);

        $txnDormant = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_BATCH_02',
            'txn_id'              => 'TXN_BATCH_02',
            'amount'              => 8000.00,
            'credit_account_no'   => '0100202707747',
            'debit_account_no'    => '0100222222222',
            'status_id'           => BkashTransaction::STATUS_FINAL_AUTHORIZED,
            'row_sequence'        => 1,
        ]);

        $txn3 = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_BATCH_03',
            'txn_id'              => 'TXN_BATCH_03',
            'amount'              => 9000.00,
            'credit_account_no'   => '0100202707747',
            'debit_account_no'    => '0100333333333',
            'status_id'           => BkashTransaction::STATUS_FINAL_AUTHORIZED,
            'row_sequence'        => 2,
        ]);

        // Execute batch settlement job
        $job = new ExecuteCbsSettlementJob([$txn1->id, $txnDormant->id, $txn3->id]);
        $job->handle(new CbsApiService());

        // Refresh models
        $txn1->refresh();
        $txnDormant->refresh();
        $txn3->refresh();

        // 1. Assert Valid Txn 1 and Txn 3 succeeded
        $this->assertEquals(BkashTransaction::STATUS_CBS_SUCCESS, $txn1->status_id);
        $this->assertEquals(BkashTransaction::STATUS_CBS_SUCCESS, $txn3->status_id);

        // 2. Assert Dormant Txn failed with STATUS_REJECTED (9000)
        $this->assertEquals(BkashTransaction::STATUS_REJECTED, $txnDormant->status_id);
        $this->assertStringContainsString('Account is DORMANT', $txnDormant->reject_reason);

        // 3. Assert Failed Transaction record created in BkashFailedTransaction table
        $failedRecord = BkashFailedTransaction::where('reference_id', 'REF_BATCH_02')->first();
        $this->assertNotNull($failedRecord);
        $this->assertEquals('DORMANT_ACCOUNT', $failedRecord->failure_code);
        $this->assertStringContainsString('Account is DORMANT', $failedRecord->reject_reason);
    }
}
