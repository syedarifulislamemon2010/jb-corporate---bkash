<?php

namespace Tests\Feature;

use App\Filament\Resources\BkashReports\BkashReportsResource;
use App\Filament\Resources\BkashReports\Pages\ListBkashReports;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Tests\TestCase;

class BkashReportsTableColumnsOrderTest extends TestCase
{
    public function test_bkash_reports_table_has_expected_column_order(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $livewire = new ListBkashReports();
        $table = new Table($livewire);
        $table = BkashReportsResource::table($table);

        $columns = $table->getColumns();
        $columnNames = array_keys($columns);

        // Required columns and order
        $expectedOrder = [
            'index',                  // #
            'create_date',            // Date
            'value_date',             // Value Date
            'txn_id',                 // Txn ID
            'transaction_type',       // Channel
            'status_id',              // Settlement Status
            'debit_account_title',    // Bank Account Name
            'beneficiary_account_no', // Beneficiary Account
            'credit_routing',         // Bank & Branch Name
            'debit_routing',          // Routing Code
            'amount',                 // Amount (BDT)
            'source_account_no',      // Source Account (TCSA/Ops)
            'reference_id',           // Ref No.
        ];

        foreach ($expectedOrder as $position => $colName) {
            $this->assertEquals(
                $colName,
                $columnNames[$position] ?? null,
                "Column at position {$position} should be '{$colName}', found " . ($columnNames[$position] ?? 'none')
            );
        }

        // Verify Labels
        $this->assertEquals('Date', $columns['create_date']->getLabel());
        $this->assertEquals('Value Date', $columns['value_date']->getLabel());
        $this->assertEquals('Txn ID', $columns['txn_id']->getLabel());
        $this->assertEquals('Channel', $columns['transaction_type']->getLabel());
        $this->assertEquals('Settlement Status', $columns['status_id']->getLabel());
        $this->assertEquals('Bank Account Name', $columns['debit_account_title']->getLabel());
        $this->assertEquals('Beneficiary Account', $columns['beneficiary_account_no']->getLabel());
        $this->assertEquals('Bank & Branch Name', $columns['credit_routing']->getLabel());
        $this->assertEquals('Routing Code', $columns['debit_routing']->getLabel());
        $this->assertEquals('Amount (BDT)', $columns['amount']->getLabel());
        $this->assertEquals('Source Account (TCSA/Ops)', $columns['source_account_no']->getLabel());
        $this->assertEquals('Ref No.', $columns['reference_id']->getLabel());
    }
}