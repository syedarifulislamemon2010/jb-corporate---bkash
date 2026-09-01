<?php

namespace Tests\Feature;

use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SanctumTokenAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'bkash_checker', 'guard_name' => 'web']);

        $this->testUser = User::create([
            'name'         => 'Test Checker User',
            'email'        => 'checker@test.jbcorporate.com',
            'mobile_no'    => '01711000001',
            'organization' => 'Janata Bank PLC.',
            'password'     => bcrypt('Test@Pass123'),
        ]);
        $this->testUser->assignRole('bkash_checker');
    }

    public function test_token_endpoint_issues_valid_sanctum_token_for_correct_credentials(): void
    {
        $response = $this->postJson('/api/test-auth/token', [
            'email'    => 'checker@test.jbcorporate.com',
            'password' => 'Test@Pass123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'organization',
                    'role',
                ],
            ])
            ->assertJson([
                'success' => true,
                'user'    => [
                    'email' => 'checker@test.jbcorporate.com',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_token_endpoint_rejects_invalid_credentials(): void
    {
        // Wrong password
        $response1 = $this->postJson('/api/test-auth/token', [
            'email'    => 'checker@test.jbcorporate.com',
            'password' => 'WrongPassword',
        ]);
        $response1->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);

        // Non-existent user
        $response2 = $this->postJson('/api/test-auth/token', [
            'email'    => 'nobody@test.jbcorporate.com',
            'password' => 'Test@Pass123',
        ]);
        $response2->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    public function test_token_endpoint_validates_required_fields(): void
    {
        $response = $this->postJson('/api/test-auth/token', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_token_protected_cbs_callback_succeeds_with_valid_sanctum_token_1006(): void
    {
        $txn = BkashTransaction::create([
            'transaction_type'  => 'A2A',
            'reference_id'      => 'REF_SANCTUM_001',
            'txn_id'            => 'TXN_SANCTUM_001',
            'amount'            => 50000.00,
            'source_account_no' => '0100202707747',
            'beneficiary_account_no'  => '0100111111111',
            'status_id'         => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        Sanctum::actingAs($this->testUser);

        $response = $this->postJson('/api/test-auth/cbs/response-callback', [
            'response_id'  => 'CBS_SANCTUM_RESP_1006',
            'status_id'    => 1006,
            'txn_id'       => 'TXN_SANCTUM_001',
            'confirmed_by' => 'TEST_VIA_POSTMAN',
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
        $this->assertEquals('CBS_SANCTUM_RESP_1006', $txn->response_id);
        $this->assertEquals('TEST_VIA_POSTMAN', $txn->confirmed_by);
        $this->assertNotNull($txn->confirmed_at);
        $this->assertNotNull($txn->cbs_success_at);
    }

    public function test_token_protected_cbs_callback_records_failure_1007(): void
    {
        $txn = BkashTransaction::create([
            'transaction_type'  => 'BEFTN',
            'reference_id'      => 'REF_SANCTUM_FAIL_002',
            'txn_id'            => 'TXN_SANCTUM_FAIL_002',
            'amount'            => 25000.00,
            'source_account_no' => '0100202707747',
            'beneficiary_account_no'  => '0100222222222',
            'status_id'         => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        Sanctum::actingAs($this->testUser);

        $response = $this->postJson('/api/test-auth/cbs/response-callback', [
            'response_id'  => 'CBS_SANCTUM_FAIL_002',
            'status_id'    => 1007,
            'reference_id' => 'REF_SANCTUM_FAIL_002',
            'reason'       => 'Beneficiary account dormant',
            'confirmed_by' => 'TEST_VIA_POSTMAN',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success'   => true,
                'status_id' => 1007,
            ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_CBS_RESPONSE_FAILED, $txn->status_id);
        $this->assertEquals('CBS_SANCTUM_FAIL_002', $txn->response_id);
        $this->assertEquals('Beneficiary account dormant', $txn->reject_reason);

        $failed = BkashFailedTransaction::where('reference_id', 'REF_SANCTUM_FAIL_002')->first();
        $this->assertNotNull($failed);
        $this->assertEquals('CBS_CALLBACK_REJECTED', $failed->failure_code);
    }

    public function test_token_protected_cbs_callback_rejects_unauthenticated_requests(): void
    {
        $response = $this->postJson('/api/test-auth/cbs/response-callback', [
            'response_id' => 'CBS_UNAUTH',
            'status_id'   => 1006,
            'txn_id'      => 'TXN_UNAUTH',
        ]);

        $response->assertStatus(401);
    }
}