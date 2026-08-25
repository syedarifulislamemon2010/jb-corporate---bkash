<?php

namespace Tests\Unit;

use App\Jobs\ExecuteCbsSettlementJob;
use App\Models\BkashTransaction;
use App\Models\PostingAttempt;
use App\Services\CbsApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChronologicalSettlementOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_settlement_executes_in_exact_chronological_row_sequence_order(): void
    {
        // 1. Create out-of-order inserted transactions simulating file rows 1, 2, 3, 4
        $txn3 = BkashTransaction::create([
            'file_name'        => 'BEFTN_JANATA_BANK_2026_08_24_1Slot1.xlsx',
            'row_sequence'     => 3,
            'transaction_type' => 'BEFTN',
            'reference_id'     => 'REF_ROW_03',
            'txn_id'           => 'TXN_ROW_03',
            'amount'           => 3000.00,
            'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $txn1 = BkashTransaction::create([
            'file_name'        => 'BEFTN_JANATA_BANK_2026_08_24_1Slot1.xlsx',
            'row_sequence'     => 1,
            'transaction_type' => 'BEFTN',
            'reference_id'     => 'REF_ROW_01',
            'txn_id'           => 'TXN_ROW_01',
            'amount'           => 1000.00,
            'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $txn4 = BkashTransaction::create([
            'file_name'        => 'BEFTN_JANATA_BANK_2026_08_24_1Slot1.xlsx',
            'row_sequence'     => 4,
            'transaction_type' => 'BEFTN',
            'reference_id'     => 'REF_ROW_04',
            'txn_id'           => 'TXN_ROW_04',
            'amount'           => 4000.00,
            'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        $txn2 = BkashTransaction::create([
            'file_name'        => 'BEFTN_JANATA_BANK_2026_08_24_1Slot1.xlsx',
            'row_sequence'     => 2,
            'transaction_type' => 'BEFTN',
            'reference_id'     => 'REF_ROW_02',
            'txn_id'           => 'TXN_ROW_02',
            'amount'           => 2000.00,
            'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
        ]);

        // 2. Track posting execution order with mock CBS service
        $postedOrder = [];
        $mockCbsService = $this->createMock(CbsApiService::class);
        $mockCbsService->expects($this->exactly(4))
            ->method('settleTransaction')
            ->willReturnCallback(function (BkashTransaction $txn) use (&$postedOrder) {
                $postedOrder[] = [
                    'row_sequence' => $txn->row_sequence,
                    'ref'          => $txn->reference_id,
                    'amount'       => $txn->amount,
                ];
                return [
                    'success'     => true,
                    'status_code' => 200,
                    'response'    => ['status' => 'APPROVED'],
                    'message'     => 'Settled successfully',
                ];
            });

        // 3. Dispatch settlement job with all transaction IDs (passed in arbitrary order)
        $job = new ExecuteCbsSettlementJob([$txn3->id, $txn1->id, $txn4->id, $txn2->id]);
        $job->handle($mockCbsService);

        // 4. Assert that posting occurred strictly in row_sequence order (1 -> 2 -> 3 -> 4)
        $this->assertCount(4, $postedOrder);
        $this->assertEquals(1, $postedOrder[0]['row_sequence']);
        $this->assertEquals('REF_ROW_01', $postedOrder[0]['ref']);

        $this->assertEquals(2, $postedOrder[1]['row_sequence']);
        $this->assertEquals('REF_ROW_02', $postedOrder[1]['ref']);

        $this->assertEquals(3, $postedOrder[2]['row_sequence']);
        $this->assertEquals('REF_ROW_03', $postedOrder[2]['ref']);

        $this->assertEquals(4, $postedOrder[3]['row_sequence']);
        $this->assertEquals('REF_ROW_04', $postedOrder[3]['ref']);
    }
}
