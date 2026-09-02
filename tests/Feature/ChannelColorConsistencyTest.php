<?php

namespace Tests\Feature;

use App\Filament\Resources\BkashBatches\BkashBatchResource;
use App\Filament\Resources\BkashBatches\Pages\ListBkashBatches;
use App\Filament\Resources\BkashFailedTransactions\BkashFailedTransactionResource;
use App\Filament\Resources\BkashFailedTransactions\Pages\ListBkashFailedTransactions;
use App\Filament\Resources\BkashReports\BkashReportsResource;
use App\Filament\Resources\BkashReports\Pages\ListBkashReports;
use App\Filament\Resources\BkashTransactionAuthorizations\Pages\ListBkashTransactionAuthorizations;
use App\Filament\Resources\BkashTransactionAuthorizations\Tables\BkashTransactionAuthorizationsTable;
use App\Filament\Resources\BkashTransactionConfirmations\Pages\ListBkashTransactionConfirmations;
use App\Filament\Resources\BkashTransactionConfirmations\Tables\BkashTransactionConfirmationsTable;
use App\Filament\Resources\BkashTransactions\Pages\ListBkashTransactions;
use App\Filament\Resources\BkashTransactions\Tables\BkashTransactionsTable;
use Filament\Facades\Filament;
use Filament\Tables\Table;
use Tests\TestCase;

class ChannelColorConsistencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_panel_has_purple_color_registered(): void
    {
        $panel = Filament::getPanel('admin');
        $colors = $panel->getColors();

        $this->assertArrayHasKey('purple', $colors);
    }

    public function test_bkash_batch_channel_and_failed_count_colors(): void
    {
        $livewire = new ListBkashBatches();
        $table = BkashBatchResource::table(new Table($livewire));

        $columns = collect($table->getColumns())->keyBy(fn ($col) => $col->getName());

        $this->assertArrayHasKey('transaction_type', $columns);
        $channelCol = $columns['transaction_type'];

        // Verify channel colors
        $this->assertEquals('success', $channelCol->getColor('A2A'));
        $this->assertEquals('purple', $channelCol->getColor('BEFTN'));
        $this->assertEquals('warning', $channelCol->getColor('RTGS'));
        $this->assertNotEquals('danger', $channelCol->getColor('RTGS'));

        // Verify failed count color logic: 0 is gray, >0 is danger
        $this->assertArrayHasKey('failed_count', $columns);
        $failedCol = $columns['failed_count'];
        $this->assertEquals('gray', $failedCol->getColor(0));
        $this->assertEquals('gray', $failedCol->getColor('0'));
        $this->assertEquals('danger', $failedCol->getColor(1));
        $this->assertEquals('danger', $failedCol->getColor(5));
    }

    public function test_all_transaction_resources_have_uniform_channel_colors(): void
    {
        // 1. BkashReportsResource
        $reportsTable = BkashReportsResource::table(new Table(new ListBkashReports()));
        $reportsChannelCol = collect($reportsTable->getColumns())->first(fn ($c) => $c->getName() === 'transaction_type');
        $this->assertNotNull($reportsChannelCol);
        $this->assertEquals('success', $reportsChannelCol->getColor('A2A'));
        $this->assertEquals('purple', $reportsChannelCol->getColor('BEFTN'));
        $this->assertEquals('warning', $reportsChannelCol->getColor('RTGS'));

        // 2. BkashTransactionsTable
        $transactionsTable = BkashTransactionsTable::configure(new Table(new ListBkashTransactions()));
        $txnsChannelCol = collect($transactionsTable->getColumns())->first(fn ($c) => $c->getName() === 'transaction_type');
        $this->assertNotNull($txnsChannelCol);
        $this->assertEquals('success', $txnsChannelCol->getColor('A2A'));
        $this->assertEquals('purple', $txnsChannelCol->getColor('BEFTN'));
        $this->assertEquals('warning', $txnsChannelCol->getColor('RTGS'));

        // 3. BkashTransactionAuthorizationsTable
        $authTable = BkashTransactionAuthorizationsTable::configure(new Table(new ListBkashTransactionAuthorizations()));
        $authChannelCol = collect($authTable->getColumns())->first(fn ($c) => $c->getName() === 'transaction_type');
        $this->assertNotNull($authChannelCol);
        $this->assertEquals('success', $authChannelCol->getColor('A2A'));
        $this->assertEquals('purple', $authChannelCol->getColor('BEFTN'));
        $this->assertEquals('warning', $authChannelCol->getColor('RTGS'));

        // 4. BkashTransactionConfirmationsTable
        $confTable = BkashTransactionConfirmationsTable::configure(new Table(new ListBkashTransactionConfirmations()));
        $confChannelCol = collect($confTable->getColumns())->first(fn ($c) => $c->getName() === 'transaction_type');
        $this->assertNotNull($confChannelCol);
        $this->assertEquals('success', $confChannelCol->getColor('A2A'));
        $this->assertEquals('purple', $confChannelCol->getColor('BEFTN'));
        $this->assertEquals('warning', $confChannelCol->getColor('RTGS'));

        // 5. BkashFailedTransactionResource
        $failedTable = BkashFailedTransactionResource::table(new Table(new ListBkashFailedTransactions()));
        $failedChannelCol = collect($failedTable->getColumns())->first(fn ($c) => $c->getName() === 'transaction_type');
        $this->assertNotNull($failedChannelCol);
        $this->assertEquals('success', $failedChannelCol->getColor('A2A'));
        $this->assertEquals('purple', $failedChannelCol->getColor('BEFTN'));
        $this->assertEquals('warning', $failedChannelCol->getColor('RTGS'));
    }

    public function test_detail_modal_css_has_non_conflicting_channel_badges(): void
    {
        $viewContent = file_get_contents(resource_path('views/filament/resources/bkash-batches/detail-modal.blade.php'));

        // RTGS must be amber (#fffbeb / #b45309), not rose/red
        $this->assertStringContainsString('.jb-badge-rtgs', $viewContent);
        $this->assertStringNotContainsString('.jb-badge-rtgs { background-color: #ffe4e6;', $viewContent);

        // BEFTN must be purple (#f5f3ff / #6d28d9), not amber
        $this->assertStringContainsString('.jb-badge-beftn', $viewContent);
        $this->assertStringNotContainsString('.jb-badge-beftn { background-color: #fef3c7;', $viewContent);
    }
}
