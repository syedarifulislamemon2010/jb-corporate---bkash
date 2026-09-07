<?php

namespace Tests\Unit;

use App\Services\BkashExcelParserService;
use Tests\TestCase;

class NewHeaderFormatParsingTest extends TestCase
{
    /**
     * Test user-specified new header format:
     * ID=1, Sender Acc No=0100202707747, Receiver Routing No=175060283,
     * Receiver Name=Test Name, Receiver Acc No=0374901031972, Amount=5000
     */
    public function test_user_reported_header_format_maps_correctly_and_validates_successfully(): void
    {
        $headers = [
            'ID',
            'Sender Acc No',
            'Receiver Routing No',
            'Receiver Name',
            'Receiver Acc No',
            'Amount',
        ];

        $row = [
            1,
            '0100202707747',
            '175060283',
            'Test Name',
            '0374901031972',
            5000,
        ];

        $mapped = BkashExcelParserService::mapRowData($headers, $row, 'A2A');

        // Verify mapped values exactly match user specifications
        $this->assertEquals('0100202707747', $mapped['source_account_no'], 'Source Account No must match Sender Acc No');
        $this->assertEquals('0374901031972', $mapped['beneficiary_account_no'], 'Beneficiary Account No must match Receiver Acc No');
        $this->assertEquals('175060283', $mapped['credit_routing'], 'Credit routing must match Receiver Routing No');
        $this->assertEquals('175060283', $mapped['debit_routing'], 'Debit routing backward compatibility');
        $this->assertEquals(5000.0, $mapped['amount'], 'Amount must be parsed as 5000.0');
        $this->assertEquals('Test Name', $mapped['debit_account_title'], 'Receiver Name must map to debit_account_title');

        // Verify scheduled bank is auto-derived from routing 175 (Pubali Bank PLC)
        $this->assertEquals('Pubali Bank PLC', $mapped['credit_bank']);

        // ID must be skipped and NOT treated as txn_id
        $this->assertNotEquals('1', $mapped['txn_id'] ?? null);

        // Crucial: Verify transaction passes validation and does NOT fail
        $detectedDebit = null;
        $validation = BkashExcelParserService::validateRow($mapped, 'A2A', $detectedDebit);

        $this->assertTrue($validation['is_valid'], 'Transaction must be valid and NOT fail. Errors: ' . implode(', ', $validation['errors'] ?? []));
        $this->assertEmpty($validation['errors'], 'No validation errors should be present');
        $this->assertNull($validation['failure_code'], 'No failure code should be set');
    }

    /**
     * Test all variant combinations for sender, receiver, and routing aliases.
     */
    public function test_all_header_variants_are_supported(): void
    {
        $headers = [
            'id',
            'senderaccountno',
            'receiverroutingnumber',
            'receivername',
            'receiveraccountno',
            'amountbdt',
        ];

        $row = [
            99,
            '0100202707747',
            '025272828', // Janata Bank
            'Merchant Name',
            '0100987654321',
            '12,500.50',
        ];

        $mapped = BkashExcelParserService::mapRowData($headers, $row, 'A2A');

        $this->assertEquals('0100202707747', $mapped['source_account_no']);
        $this->assertEquals('0100987654321', $mapped['beneficiary_account_no']);
        $this->assertEquals('025272828', $mapped['credit_routing']);
        $this->assertEquals('Merchant Name', $mapped['debit_account_title']);
        $this->assertEquals(12500.50, $mapped['amount']);

        $detectedDebit = null;
        $validation = BkashExcelParserService::validateRow($mapped, 'A2A', $detectedDebit);
        $this->assertTrue($validation['is_valid']);
    }

    /**
     * Test that ID column does not collide or overwrite unique transaction references.
     */
    public function test_id_column_is_safely_ignored_as_row_counter(): void
    {
        $headers = ['ID', 'Ref No', 'Sender Acc', 'Receiver Acc', 'Amount'];
        $row = [1, 'REF_UNIQUE_001', '0100202707747', '0374901031972', 1000];

        $mapped = BkashExcelParserService::mapRowData($headers, $row, 'A2A');

        $this->assertEquals('REF_UNIQUE_001', $mapped['reference_id']);
        $this->assertNotEquals('1', $mapped['reference_id']);
        $this->assertNotEquals('1', $mapped['txn_id'] ?? null);
    }
}