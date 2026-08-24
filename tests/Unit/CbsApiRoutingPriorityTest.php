<?php

namespace Tests\Unit;

use App\Models\BkashTransaction;
use App\Services\CbsApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CbsApiRoutingPriorityTest extends TestCase
{
    use RefreshDatabase;

    public function test_cbs_settlement_sends_credit_routing_as_primary_routing_no(): void
    {
        Http::fake([
            '*/api/token' => Http::response([
                'status' => 'APPROVED',
                'data'   => ['token' => 'mock_jwt_token_123'],
            ], 200),
            '*/api/bkash-transactions' => Http::response([
                'status'  => 'APPROVED',
                'message' => 'Successfully settled',
            ], 200),
        ]);

        $txn = BkashTransaction::create([
            'transaction_type'    => 'RTGS',
            'reference_id'        => 'REF_RTGS_TEST_01',
            'txn_id'              => 'TXN_RTGS_TEST_01',
            'amount'              => 500000.00,
            'credit_account_no'   => '0100202707747',
            'debit_account_no'    => '0370210009030',
            'debit_account_title' => 'Beneficiary Corp',
            'credit_routing'      => '225260856', // Beneficiary Routing
            'debit_routing'       => '315260856', // Debit Routing
            'status_id'           => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $service = new CbsApiService();
        $result = $service->settleTransaction($txn);

        $this->assertTrue($result['success']);

        // Assert that the payload sent to CBS had creditRoutingNo = 225260856 (beneficiary routing)
        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/api/bkash-transactions')) {
                $payload = $request->data();
                return isset($payload['creditRoutingNo']) && $payload['creditRoutingNo'] === '225260856';
            }
            return true;
        });
    }

    public function test_cbs_settlement_uses_debit_routing_as_backward_compatible_fallback(): void
    {
        Http::fake([
            '*/api/token' => Http::response([
                'status' => 'APPROVED',
                'data'   => ['token' => 'mock_jwt_token_123'],
            ], 200),
            '*/api/bkash-transactions' => Http::response([
                'status'  => 'APPROVED',
                'message' => 'Successfully settled',
            ], 200),
        ]);

        // Legacy record where only debit_routing was stored
        $txn = BkashTransaction::create([
            'transaction_type'    => 'BEFTN',
            'reference_id'        => 'REF_BEFTN_LEGACY_01',
            'txn_id'              => 'TXN_BEFTN_LEGACY_01',
            'amount'              => 25000.00,
            'credit_account_no'   => '0100202707747',
            'debit_account_no'    => '0500210006830',
            'debit_account_title' => 'Beneficiary Individual',
            'credit_routing'      => null,        // Missing in legacy
            'debit_routing'       => '125260856', // Stored in debit_routing
            'status_id'           => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $service = new CbsApiService();
        $result = $service->settleTransaction($txn);

        $this->assertTrue($result['success']);

        // Assert that fallback to debit_routing (125260856) was used
        Http::assertSent(function ($request) {
            if (str_contains($request->url(), '/api/bkash-transactions')) {
                $payload = $request->data();
                return isset($payload['creditRoutingNo']) && $payload['creditRoutingNo'] === '125260856';
            }
            return true;
        });
    }
}
