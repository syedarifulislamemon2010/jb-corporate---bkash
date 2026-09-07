<?php

namespace Tests\Unit;

use App\Models\BkashTransaction;
use App\Services\BkashExcelParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateTransactionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_duplicate_txn_id_is_blocked(): void
    {
        // 1. Existing transaction in DB
        BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_ORIGINAL',
            'txn_id'              => 'TXN_GLOBAL_DUP_999',
            'amount'              => 500.00,
            'source_account_no'   => '0100202707747',
            'beneficiary_account_no'    => '0100123456789',
            'status_id'           => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        // 2. Pre-fetch existing IDs
        BkashExcelParserService::prefetchExistingTxnIds(['TXN_GLOBAL_DUP_999']);

        // 3. Row data attempting to use the same txn_id
        $mapped = [
            'reference_id'      => 'REF_NEW_ROW',
            'txn_id'            => 'TXN_GLOBAL_DUP_999',
            'amount'            => 1000.00,
            'source_account_no' => '0100202707747',
            'beneficiary_account_no'  => '0100987654321',
        ];

        $result = BkashExcelParserService::validateRow($mapped, 'A2A');

        $this->assertFalse($result['is_valid']);
        $this->assertEquals('DUPLICATE_TXN_ID', $result['failure_code']);
        $this->assertStringContainsString('Global Duplicate Transaction ID TXN_GLOBAL_DUP_999 blocked', $result['errors'][0]);
    }

    public function test_unique_txn_id_passes_validation(): void
    {
        BkashExcelParserService::prefetchExistingTxnIds(['TXN_EXISTING_123']);

        $mapped = [
            'reference_id'      => 'REF_UNIQUE',
            'txn_id'            => 'TXN_BRAND_NEW_456',
            'amount'            => 1500.00,
            'source_account_no' => '0100202707747',
            'beneficiary_account_no'  => '0100987654321',
        ];

        $result = BkashExcelParserService::validateRow($mapped, 'A2A');

        $this->assertTrue($result['is_valid']);
        $this->assertNull($result['failure_code']);
        $this->assertEmpty($result['errors']);
    }
}
