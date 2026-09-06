<?php

namespace Tests\Feature;

use App\Filament\Resources\BkashFailedTransactions\BkashFailedTransactionResource;
use App\Filament\Resources\BkashTransactionAuthorizations\Pages\ListBkashTransactionAuthorizations;
use App\Filament\Resources\BkashTransactionConfirmations\Pages\ListBkashTransactionConfirmations;
use App\Filament\Resources\BkashTransactions\Pages\ListBkashTransactions;
use App\Models\BkashFailedTransaction;
use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FailedTransactionAuthorizationPreventionTest extends TestCase
{
    use RefreshDatabase;

    protected User $checker;
    protected User $authorizer1;
    protected User $authorizer2;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bkash_checker', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bkash_authorizer_1', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'bkash_authorizer_2', 'guard_name' => 'web']);

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->checker = User::create([
            'name'         => 'Test Checker',
            'email'        => 'checker@jb.com',
            'mobile_no'    => '01711000001',
            'organization' => 'Janata Bank PLC.',
            'password'     => bcrypt('Secret123'),
        ]);
        $this->checker->assignRole('bkash_checker');

        $this->authorizer1 = User::create([
            'name'         => 'Test Auth1',
            'email'        => 'auth1@jb.com',
            'mobile_no'    => '01711000002',
            'organization' => 'Janata Bank PLC.',
            'password'     => bcrypt('Secret123'),
        ]);
        $this->authorizer1->assignRole('bkash_authorizer_1');

        $this->authorizer2 = User::create([
            'name'         => 'Test Auth2',
            'email'        => 'auth2@jb.com',
            'mobile_no'    => '01711000003',
            'organization' => 'Janata Bank PLC.',
            'password'     => bcrypt('Secret123'),
        ]);
        $this->authorizer2->assignRole('bkash_authorizer_2');

        $this->admin = User::create([
            'name'         => 'Test Admin',
            'email'        => 'admin@jb.com',
            'mobile_no'    => '01711000004',
            'organization' => 'Janata Bank PLC.',
            'password'     => bcrypt('Secret123'),
        ]);
        $this->admin->assignRole('super_admin');
    }

    public function test_failed_transaction_badge_has_danger_color_and_css_styling(): void
    {
        $badgeColor = BkashFailedTransactionResource::getNavigationBadgeColor();
        $this->assertEquals('danger', $badgeColor);

        $cssContent = view('filament.custom-styles')->render();
        $this->assertStringContainsString('bkash-failed-transactions', $cssContent);
        $this->assertStringContainsString('#dc2626', $cssContent);
        $this->assertStringContainsString('#ef4444', $cssContent);
    }

    public function test_batch_with_failed_transactions_is_detected(): void
    {
        $batch = BkashTransactionBatch::create([
            'id'               => (string) Str::uuid(),
            'file_name'        => 'Batch_With_Failures.xlsx',
            'transaction_type' => 'A2A',
            'total_data'       => 2,
            'total_amount'     => 5000.00,
            'status_id'        => BkashTransaction::STATUS_CHECKED,
        ]);

        $txn1 = BkashTransaction::create([
            'batch_id'                => $batch->id,
            'file_name'               => $batch->file_name,
            'transaction_type'        => 'A2A',
            'reference_id'            => 'REF001',
            'txn_id'                  => 'TXN001',
            'source_account_no'       => '1111222233334',
            'beneficiary_account_no'  => '9999888877776',
            'amount'                  => 2500.00,
            'status_id'               => BkashTransaction::STATUS_CHECKED,
        ]);

        $this->assertFalse($batch->hasFailedTransactions());
        $this->assertFalse($txn1->belongsToFailedBatch());

        BkashFailedTransaction::create([
            'batch_id'               => $batch->id,
            'file_name'              => $batch->file_name,
            'transaction_type'       => 'A2A',
            'row_number'             => 2,
            'reference_id'           => 'REF002',
            'source_account_no'      => '1111222233334',
            'beneficiary_account_no' => '0000000000000',
            'amount'                 => 2500.00,
            'failure_code'           => 'INVALID_ACCOUNT_NO',
            'reject_reason'          => 'Beneficiary account is invalid',
        ]);

        $this->assertTrue($batch->hasFailedTransactions());
        $this->assertTrue($txn1->belongsToFailedBatch());
    }

    public function test_creating_failed_transaction_automatically_reverts_batch_to_checker(): void
    {
        $batch = BkashTransactionBatch::create([
            'id'               => (string) Str::uuid(),
            'file_name'        => 'Accidentally_Checked_File.xlsx',
            'transaction_type' => 'A2A',
            'total_data'       => 1,
            'total_amount'     => 1000.00,
            'status_id'        => BkashTransaction::STATUS_CHECKED,
        ]);

        $txn = BkashTransaction::create([
            'batch_id'          => $batch->id,
            'file_name'         => $batch->file_name,
            'transaction_type'  => 'A2A',
            'reference_id'      => 'REF_CHECKED_01',
            'txn_id'            => 'TXN_CHECKED_01',
            'source_account_no' => '1111222233334',
            'amount'            => 1000.00,
            'status_id'         => BkashTransaction::STATUS_CHECKED,
            'checked_by'        => 'Some Checker',
            'checked_at'        => now(),
        ]);

        BkashFailedTransaction::create([
            'batch_id'         => $batch->id,
            'file_name'        => $batch->file_name,
            'transaction_type' => 'A2A',
            'row_number'       => 2,
            'reference_id'     => 'REF_FAIL_02',
            'amount'           => 500.00,
            'failure_code'     => 'CBS_REJECTED',
            'reject_reason'    => 'Account is dormant',
        ]);

        $batch->refresh();
        $txn->refresh();

        $this->assertEquals(BkashTransaction::STATUS_PENDING_CHECKER, $batch->status_id);
        $this->assertEquals(BkashTransaction::STATUS_PENDING_CHECKER, $txn->status_id);
        $this->assertNull($txn->checked_by);
        $this->assertNull($txn->checked_at);
    }

    public function test_failed_batch_is_excluded_from_authorization_list_and_cannot_be_authorized(): void
    {
        $batch = BkashTransactionBatch::create([
            'id'               => (string) Str::uuid(),
            'file_name'        => 'Failed_File_Auth.xlsx',
            'transaction_type' => 'A2A',
            'total_data'       => 1,
            'total_amount'     => 1500.00,
            'status_id'        => BkashTransaction::STATUS_CHECKED,
        ]);

        BkashTransaction::create([
            'batch_id'          => $batch->id,
            'file_name'         => $batch->file_name,
            'transaction_type'  => 'A2A',
            'reference_id'      => 'REF_AUTH_01',
            'txn_id'            => 'TXN_AUTH_01',
            'source_account_no' => '1111222233334',
            'amount'            => 1500.00,
            'status_id'         => BkashTransaction::STATUS_CHECKED,
        ]);

        BkashFailedTransaction::create([
            'batch_id'         => $batch->id,
            'file_name'        => $batch->file_name,
            'transaction_type' => 'A2A',
            'row_number'       => 3,
            'reference_id'     => 'REF_AUTH_FAIL',
            'amount'           => 500.00,
            'failure_code'     => 'DORMANT_ACCOUNT',
            'reject_reason'    => 'Dormant account',
        ]);

        $batch->update(['status_id' => BkashTransaction::STATUS_CHECKED]);

        $this->actingAs($this->authorizer1);
        $page = new ListBkashTransactionAuthorizations();

        $batches = $page->getBatches();
        $this->assertFalse($batches->contains('id', $batch->id));

        $batch->refresh();
        $this->assertEquals(BkashTransaction::STATUS_PENDING_CHECKER, $batch->status_id);
    }

    public function test_attempting_to_authorize_failed_batch_is_blocked_and_reverts(): void
    {
        $batch = BkashTransactionBatch::create([
            'id'               => (string) Str::uuid(),
            'file_name'        => 'Force_Authorize_Fail.xlsx',
            'transaction_type' => 'BEFTN',
            'total_data'       => 1,
            'total_amount'     => 2000.00,
            'status_id'        => BkashTransaction::STATUS_CHECKED,
        ]);

        $txn = BkashTransaction::create([
            'batch_id'          => $batch->id,
            'file_name'         => $batch->file_name,
            'transaction_type'  => 'BEFTN',
            'reference_id'      => 'REF_BEFTN_01',
            'txn_id'            => 'TXN_BEFTN_01',
            'source_account_no' => '1111222233334',
            'amount'            => 2000.00,
            'status_id'         => BkashTransaction::STATUS_CHECKED,
        ]);

        BkashFailedTransaction::create([
            'batch_id'         => $batch->id,
            'file_name'        => $batch->file_name,
            'transaction_type' => 'BEFTN',
            'row_number'       => 2,
            'reference_id'     => 'REF_BEFTN_FAIL',
            'amount'           => 500.00,
            'failure_code'     => 'INVALID_ROUTING',
            'reject_reason'    => 'Routing number not found',
        ]);

        $batch->update(['status_id' => BkashTransaction::STATUS_CHECKED]);
        $txn->update(['status_id' => BkashTransaction::STATUS_CHECKED]);

        $this->actingAs($this->authorizer1);
        $page = new ListBkashTransactionAuthorizations();
        $page->selectedBatches = [(string) $batch->id];
        $page->authorizeSelectedBatches();

        $batch->refresh();
        $txn->refresh();

        $this->assertEquals(BkashTransaction::STATUS_PENDING_CHECKER, $batch->status_id);
        $this->assertEquals(BkashTransaction::STATUS_PENDING_CHECKER, $txn->status_id);
        $this->assertNull($txn->approved_by_1);
    }

    public function test_reverted_failed_batches_appear_in_checker_verify_files(): void
    {
        $batch = BkashTransactionBatch::create([
            'id'               => (string) Str::uuid(),
            'file_name'        => 'Reverted_Back_To_Checker.xlsx',
            'transaction_type' => 'RTGS',
            'total_data'       => 1,
            'total_amount'     => 100000.00,
            'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
        ]);

        BkashTransaction::create([
            'batch_id'          => $batch->id,
            'file_name'         => $batch->file_name,
            'transaction_type'  => 'RTGS',
            'reference_id'      => 'REF_RTGS_01',
            'txn_id'            => 'TXN_RTGS_01',
            'source_account_no' => '1111222233334',
            'amount'            => 100000.00,
            'status_id'         => BkashTransaction::STATUS_AUTH_1_APPROVED,
        ]);

        BkashFailedTransaction::create([
            'batch_id'         => $batch->id,
            'file_name'        => $batch->file_name,
            'transaction_type' => 'RTGS',
            'row_number'       => 2,
            'reference_id'     => 'REF_RTGS_FAIL',
            'amount'           => 50000.00,
            'failure_code'     => 'CBS_REJECTED',
            'reject_reason'    => 'Invalid transaction type',
        ]);

        $this->actingAs($this->checker);
        $page = new ListBkashTransactions();
        $batches = $page->getBatches();

        $this->assertTrue($batches->contains('id', $batch->id));
        $batch->refresh();
        $this->assertEquals(BkashTransaction::STATUS_PENDING_CHECKER, $batch->status_id);
    }

    public function test_policy_denies_authorization_for_failed_batch_transactions(): void
    {
        $batch = BkashTransactionBatch::create([
            'id'               => (string) Str::uuid(),
            'file_name'        => 'Policy_Check_File.xlsx',
            'transaction_type' => 'A2A',
            'total_data'       => 1,
            'total_amount'     => 500.00,
            'status_id'        => BkashTransaction::STATUS_CHECKED,
        ]);

        $txn = BkashTransaction::create([
            'batch_id'          => $batch->id,
            'file_name'         => $batch->file_name,
            'transaction_type'  => 'A2A',
            'reference_id'      => 'REF_POL_01',
            'txn_id'            => 'TXN_POL_01',
            'source_account_no' => '1111222233334',
            'amount'            => 500.00,
            'status_id'         => BkashTransaction::STATUS_CHECKED,
            'checked_by'        => 'Different Checker',
            'checked_by_id'     => 9999,
        ]);

        BkashFailedTransaction::create([
            'batch_id'         => $batch->id,
            'file_name'        => $batch->file_name,
            'transaction_type' => 'A2A',
            'row_number'       => 2,
            'reference_id'     => 'REF_POL_FAIL',
            'amount'           => 100.00,
            'failure_code'     => 'CBS_REJECTED',
            'reject_reason'    => 'CBS error',
        ]);

        $policy = new \App\Policies\BkashTransactionPolicy();
        $response = $policy->authorize($this->authorizer1, $txn);

        $this->assertTrue($response->denied());
        $this->assertStringContainsString('failed', $response->message());
    }
}
