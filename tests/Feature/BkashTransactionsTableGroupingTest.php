<?php

namespace Tests\Feature;

use App\Filament\Resources\BkashTransactions\Pages\ListBkashTransactions;
use App\Filament\Resources\BkashTransactions\Tables\BkashTransactionsTable;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Grouping\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class BkashTransactionsTableGroupingTest extends TestCase
{
    use RefreshDatabase;

    private User $checkerUser;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->checkerUser = User::create([
            'name'         => 'Test Checker User',
            'email'        => 'checker_table@janatabank.com',
            'mobile_no'    => '01712345678',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);
    }

    public function test_group_title_returns_plain_string_without_raw_html(): void
    {
        $fileName = 'TEST_GROUP_BATCH.xlsx';

        $batch = BkashTransactionBatch::create([
            'file_name'        => $fileName,
            'total_data'       => 2,
            'total_amount'     => 14137.17,
            'transaction_type' => 'A2A',
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        $txn = BkashTransaction::create([
            'batch_id'               => $batch->id,
            'file_name'              => $fileName,
            'transaction_type'       => 'A2A',
            'reference_id'           => 'REF101',
            'txn_id'                 => 'TXN_REF101',
            'amount'                 => 14137.17,
            'source_account_no'      => '0100202707747',
            'beneficiary_account_no' => '0100123456789',
            'status_id'              => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        $component = Livewire::actingAs($this->checkerUser)
            ->test(ListBkashTransactions::class);

        $component->assertSuccessful();

        // Inspect the group configuration
        $table = $component->instance()->getTable();
        $group = $table->getGrouping();
        $this->assertNotNull($group, 'Default group must be configured');
        $this->assertEquals('file_name', $group->getId());

        // Verify group title from record is plain string
        $title = $group->getTitle($txn);
        $this->assertIsString($title);
        $this->assertStringNotContainsString('<div', $title, 'Group title must NOT contain raw HTML div');
        $this->assertStringNotContainsString('<svg', $title, 'Group title must NOT contain raw SVG markup');
        $this->assertStringNotContainsString('x-bind', $title, 'Group title must NOT contain Alpine directives');
        $this->assertStringContainsString($fileName, $title);
        $this->assertStringContainsString('A2A', $title);
        $this->assertStringContainsString('14,137.17', $title);
    }

    public function test_table_has_download_action_and_download_url(): void
    {
        $fileName = 'DOWNLOAD_TEST.xlsx';

        $txn = BkashTransaction::create([
            'file_name'              => $fileName,
            'transaction_type'       => 'RTGS',
            'reference_id'           => 'RTGS_DL_001',
            'txn_id'                 => 'TXN_RTGS_DL_001',
            'amount'                 => 50000.00,
            'source_account_no'      => '0100202707747',
            'beneficiary_account_no' => '1001141002472',
            'status_id'              => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        $component = Livewire::actingAs($this->checkerUser)
            ->test(ListBkashTransactions::class);

        $table = $component->instance()->getTable();

        // Verify row action exists
        $action = $table->getAction('download_file');
        $this->assertNotNull($action, 'Download file row action must exist');

        $expectedUrl = route('admin.bkash.download-batch', ['file' => $fileName]);
        $action->record($txn);
        $this->assertEquals($expectedUrl, $action->getUrl());

        // Verify bulk action exists
        $bulkAction = $table->getBulkAction('download_source_file');
        $this->assertNotNull($bulkAction, 'Download source file bulk action must exist');
    }
}