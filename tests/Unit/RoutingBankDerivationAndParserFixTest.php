<?php

namespace Tests\Unit;

use App\Models\BkashTransaction;
use App\Services\BkashExcelParserService;
use Tests\TestCase;

class RoutingBankDerivationAndParserFixTest extends TestCase
{
    /**
     * Test derivation of scheduled bank names from 9-digit routing numbers.
     */
    public function test_derive_credit_bank_from_routing_codes(): void
    {
        $this->assertEquals('Bengal Commercial Bank Limited', BkashExcelParserService::deriveCreditBankFromRouting('315260856'));
        $this->assertEquals('Mutual Trust Bank PLC', BkashExcelParserService::deriveCreditBankFromRouting('145330527'));
        $this->assertEquals('Pubali Bank PLC', BkashExcelParserService::deriveCreditBankFromRouting('175060283'));
        $this->assertEquals('Dhaka Bank PLC', BkashExcelParserService::deriveCreditBankFromRouting('090263136'));
        $this->assertEquals('Janata Bank PLC', BkashExcelParserService::deriveCreditBankFromRouting('025272828'));
        $this->assertEquals('Sonali Bank PLC', BkashExcelParserService::deriveCreditBankFromRouting('010260000'));
        $this->assertEquals('BRAC Bank PLC', BkashExcelParserService::deriveCreditBankFromRouting('035260000'));
        $this->assertEquals('Dutch-Bangla Bank PLC', BkashExcelParserService::deriveCreditBankFromRouting('085260000'));
        $this->assertEquals('Islami Bank Bangladesh PLC', BkashExcelParserService::deriveCreditBankFromRouting('115260000'));

        // Invalid inputs
        $this->assertNull(BkashExcelParserService::deriveCreditBankFromRouting(null));
        $this->assertNull(BkashExcelParserService::deriveCreditBankFromRouting(''));
        $this->assertNull(BkashExcelParserService::deriveCreditBankFromRouting('12'));
        $this->assertNull(BkashExcelParserService::deriveCreditBankFromRouting('999000000'));
    }

    /**
     * Test that Bank Name is NOT overwritten by Branch Name when both columns exist.
     */
    public function test_bank_name_not_overwritten_by_branch_name_when_both_present(): void
    {
        $headers = [
            'Ref',
            'Bank_Account_Name',
            'Bank_Account_No',
            'Amount',
            'RoutingNumber',
            'Bank Name',
            'Branch Name',
            'Debit Account',
            'Txn ID',
        ];

        $row = [
            'RM41107 - CILANTRLTDRM41107',
            'CILANTRO LIMITED',
            '1001141002472',
            '675.11',
            '315260856',
            'BENGAL COMMERCIAL BANK LIMITED',
            'CORPORATE',
            '111613120722698',
            '9B76KWRU8E',
        ];

        $mapped = BkashExcelParserService::mapRowData($headers, $row, 'BEFTN');

        // Bank name must remain the bank name, NOT overwritten by 'CORPORATE'
        $this->assertEquals('BENGAL COMMERCIAL BANK LIMITED', $mapped['credit_bank']);
        $this->assertEquals('CORPORATE', $mapped['branch_name']);

        // Account mapping checks
        $this->assertEquals('111613120722698', $mapped['source_account_no']); // Debit Account (TCSA)
        $this->assertEquals('1001141002472', $mapped['beneficiary_account_no']);     // Beneficiary Account
        $this->assertEquals('315260856', $mapped['credit_routing']);           // Routing Code
        $this->assertEquals(675.11, $mapped['amount']);
    }

    /**
     * Test that Bank Name is auto-derived from Routing Number when Bank Name is omitted.
     */
    public function test_bank_name_auto_derived_when_bank_name_column_missing(): void
    {
        $headers = [
            'Ref',
            'Bank_Account_Name',
            'Bank_Account_No',
            'Amount',
            'RoutingNumber',
            'Debit Account',
            'Txn ID',
        ];

        $row = [
            'RM99887',
            'Karim Uddin',
            '0174901031972',
            '15000.00',
            '175060283', // Pubali Bank routing code (175)
            '0100202707747',
            'TXN_PUBALI_01',
        ];

        $mapped = BkashExcelParserService::mapRowData($headers, $row, 'BEFTN');

        $this->assertEquals('Pubali Bank PLC', $mapped['credit_bank']);
    }
}