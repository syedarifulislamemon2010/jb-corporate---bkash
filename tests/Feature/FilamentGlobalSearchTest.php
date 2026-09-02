<?php

namespace Tests\Feature;

use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->adminUser = User::create([
            'name'         => 'Global Search Admin',
            'email'        => 'globalsearch@jb.com',
            'mobile_no'    => '01711223344',
            'organization' => 'Janata Bank PLC.',
            'password'     => bcrypt('Secret123!'),
        ]);
        $this->adminUser->assignRole('super_admin');
    }

    public function test_global_search_returns_results_for_failed_transactions_without_query_error(): void
    {
        $failedTxn = BkashFailedTransaction::create([
            'batch_id'               => 'batch-uuid-001',
            'file_name'              => 'RTGS_JANATA_BANK_FAILED_2026.xlsx',
            'row_number'             => 2,
            'transaction_type'       => 'RTGS',
            'txn_id'                 => 'TXN_FAIL_9988',
            'reference_id'           => 'REF_FAIL_9988',
            'source_account_no'      => '0100202707747',
            'beneficiary_account_no' => '0100998877665',
            'amount'                 => 75000.00,
            'failure_code'           => 'DORMANT_ACCOUNT',
            'reject_reason'          => 'Beneficiary account is dormant',
        ]);

        $this->actingAs($this->adminUser);

        // Test Livewire Global Search component searching for the failed transaction reference_id
        Livewire::test(\Filament\Livewire\GlobalSearch::class)
            ->set('search', 'REF_FAIL_9988')
            ->assertSee('Failed Txn: TXN_FAIL_9988')
            ->assertSee('RTGS_JANATA_BANK_FAILED_2026.xlsx');

        // Test searching by file_name
        Livewire::test(\Filament\Livewire\GlobalSearch::class)
            ->set('search', 'FAILED_2026')
            ->assertSee('Failed Txn: TXN_FAIL_9988');

        // Test searching by txn_id
        Livewire::test(\Filament\Livewire\GlobalSearch::class)
            ->set('search', 'TXN_FAIL_9988')
            ->assertSee('Failed Txn: TXN_FAIL_9988');
    }

    public function test_global_search_finds_normal_transactions_and_batches(): void
    {
        $batch = BkashTransactionBatch::create([
            'file_name'        => 'RTGS_JANATA_BANK_SUCCESS_2026.xlsx',
            'transaction_type' => 'RTGS',
            'total_data'       => 1,
            'total_amount'     => 50000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        $txn = BkashTransaction::create([
            'batch_id'         => (string) $batch->id,
            'file_name'        => 'RTGS_JANATA_BANK_SUCCESS_2026.xlsx',
            'transaction_type' => 'RTGS',
            'txn_id'           => 'TXN_OK_1234',
            'reference_id'     => 'REF_OK_1234',
            'amount'           => 50000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(\Filament\Livewire\GlobalSearch::class)
            ->set('search', 'REF_OK_1234')
            ->assertSee('Txn: TXN_OK_1234');
    }
}
