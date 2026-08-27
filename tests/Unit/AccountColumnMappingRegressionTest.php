<?php

namespace Tests\Unit;

use App\Services\BkashExcelParserService;
use Tests\TestCase;

class AccountColumnMappingRegressionTest extends TestCase
{
    /**
     * Regression guard ensuring Excel "Debit Account" (TCSA source) maps to DB 'credit_account_no'
     * and Beneficiary "Account No" maps to DB 'debit_account_no'.
     */
    public function test_excel_debit_account_maps_to_db_credit_account_no_and_beneficiary_to_debit_account_no(): void
    {
        $headers = [
            'Date',
            'Ref. No.',
            'Bank Account Name',
            'Bank Account Number',
            'Amount in Taka',
            'Debit Account',
        ];

        $row = [
            '2026-07-28',
            'FT26209REF001',
            'Karim Rahman',
            '0100123456789',       // Beneficiary destination account
            '25,000.00',
            '111613120722698',     // bKash TCSA source account from "Debit Account" column
        ];

        $mapped = BkashExcelParserService::mapRowData($headers, $row, 'A2A');

        // Verify that 'credit_account_no' in DB holds the TCSA source number from "Debit Account"
        $this->assertEquals('111613120722698', $mapped['credit_account_no']);

        // Verify that 'debit_account_no' in DB holds the beneficiary account number
        $this->assertEquals('0100123456789', $mapped['debit_account_no']);

        // Verify amount cleaned
        $this->assertEquals(25000.00, $mapped['amount']);
        $this->assertEquals('FT26209REF001', $mapped['reference_id']);
        $this->assertEquals('Karim Rahman', $mapped['debit_account_title']);
    }
}
