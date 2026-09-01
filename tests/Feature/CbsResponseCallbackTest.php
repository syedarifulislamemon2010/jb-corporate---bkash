<?php

namespace Tests\Feature;

use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CbsResponseCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected string $apiKey = 'test-cbs-api-key-12345';

    protected function setUp(): void
    {
        parent::setUp();
        config(['bkash.cbs_callback_api_key' => $this->apiKey]);
    }

    public function test_cbs_callback_updates_transaction_to_success_1006(): void
    {
        $txn = BkashTransaction::create([
            'transaction_type'  => 'A2A',
            'reference_id'      => 'REF_CALLBACK_01',
            'txn_id'            => 'TXN_CALLBACK_01',
            'amount'            => 15000.00,
            'source_account_no' => '0100202707747',
            'beneficiary_account_no'  => '0100111111111',
            'status_id'         => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $response = $this->withHeaders([
            'X-CBS-API-Key' => $this->apiKey,
        ])->postJson('/api/cbs/response-callback', [
            'response_id'  => 'CBS_RESP_998877',
            'status_id'    => 1006,
            'txn_id'       => 'TXN_CALLBACK_01',
            'confirmed_by' => 'JANATA_CBS_CORE',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'   => true,
                'message'   => 'Transaction status updated',
                'id'        => $txn->id,
                'status_id' => 1006,
            ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_CBS_RESPONSE_SUCCESS, $txn->status_id);
        $this->assertEquals('CBS_RESP_998877', $txn->response_id);
        $this->assertEquals('JANATA_CBS_CORE', $txn->confirmed_by);
        $this->assertNotNull($txn->confirmed_at);
        $this->assertNotNull($txn->cbs_success_at);
    }

    public function test_cbs_callback_updates_transaction_to_failed_1007_and_records_failed_transaction(): void
    {
        $txn = BkashTransaction::create([
            'transaction_type'  => 'BEFTN',
            'reference_id'      => 'REF_CALLBACK_FAIL_02',
            'txn_id'            => 'TXN_CALLBACK_FAIL_02',
            'amount'            => 25000.00,
            'source_account_no' => '0100202707747',
            'beneficiary_account_no'  => '0100222222222',
            'status_id'         => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $response = $this->withHeaders([
            'X-CBS-API-Key' => $this->apiKey,
        ])->postJson('/api/cbs/response-callback', [
            'response_id'  => 'CBS_RESP_FAIL_1122',
            'status_id'    => 1007,
            'reference_id' => 'REF_CALLBACK_FAIL_02',
            'reason'       => 'Beneficiary bank BACH clearing timeout',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'   => true,
                'status_id' => 1007,
            ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_CBS_RESPONSE_FAILED, $txn->status_id);
        $this->assertEquals('CBS_RESP_FAIL_1122', $txn->response_id);
        $this->assertEquals('Beneficiary bank BACH clearing timeout', $txn->reject_reason);

        // Check failed transactions table
        $failed = BkashFailedTransaction::where('reference_id', 'REF_CALLBACK_FAIL_02')->first();
        $this->assertNotNull($failed);
        $this->assertEquals('CBS_CALLBACK_REJECTED', $failed->failure_code);
        $this->assertEquals('Beneficiary bank BACH clearing timeout', $failed->reject_reason);
    }

    public function test_cbs_callback_rejects_missing_or_invalid_api_key(): void
    {
        // No header
        $response1 = $this->postJson('/api/cbs/response-callback', [
            'response_id' => 'CBS_123',
            'status_id'   => 1006,
            'txn_id'      => 'TXN_123',
        ]);
        $response1->assertStatus(401);

        // Wrong header
        $response2 = $this->withHeaders([
            'X-CBS-API-Key' => 'wrong-key',
        ])->postJson('/api/cbs/response-callback', [
            'response_id' => 'CBS_123',
            'status_id'   => 1006,
            'txn_id'      => 'TXN_123',
        ]);
        $response2->assertStatus(401);
    }

    public function test_cbs_callback_returns_404_when_transaction_not_found(): void
    {
        $response = $this->withHeaders([
            'X-CBS-API-Key' => $this->apiKey,
        ])->postJson('/api/cbs/response-callback', [
            'response_id' => 'CBS_NON_EXISTENT',
            'status_id'   => 1006,
            'txn_id'      => 'TXN_DOES_NOT_EXIST',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Transaction not found',
            ]);
    }

    public function test_cbs_callback_validates_status_id_and_required_identifiers(): void
    {
        // Invalid status_id (e.g. 9999)
        $response1 = $this->withHeaders([
            'X-CBS-API-Key' => $this->apiKey,
        ])->postJson('/api/cbs/response-callback', [
            'response_id' => 'CBS_123',
            'status_id'   => 9999,
            'txn_id'      => 'TXN_123',
        ]);
        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['status_id']);

        // Missing both reference_id and txn_id
        $response2 = $this->withHeaders([
            'X-CBS-API-Key' => $this->apiKey,
        ])->postJson('/api/cbs/response-callback', [
            'response_id' => 'CBS_123',
            'status_id'   => 1006,
        ]);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['reference_id', 'txn_id']);
    }
}
