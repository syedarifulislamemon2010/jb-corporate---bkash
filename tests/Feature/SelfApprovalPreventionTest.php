<?php

namespace Tests\Feature;

use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class SelfApprovalPreventionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        Gate::define('ViewAny:BkashTransaction', fn () => true);
        Gate::define('ViewAny:BkashTransactionBatch', fn () => true);
    }

    public function test_checker_cannot_perform_first_level_authorization_on_own_checked_transaction(): void
    {
        $checker = User::create([
            'name'         => 'Checker One',
            'email'        => 'checker1@jb.com',
            'mobile_no'    => '01700000001',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer1 = User::create([
            'name'         => 'Authorizer One',
            'email'        => 'auth1@jb.com',
            'mobile_no'    => '01700000002',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $txn = BkashTransaction::create([
            'transaction_type'    => 'A2A',
            'reference_id'        => 'REF_TEST_001',
            'txn_id'              => 'TXN_TEST_001',
            'amount'              => 25000.00,
            'status_id'           => BkashTransaction::STATUS_CHECKED,
            'checked_by'          => $checker->name,
            'checked_by_id'       => $checker->id,
            'checked_at'          => Carbon::now(),
        ]);

        // 1. Policy check for Checker user must DENY authorization
        $checkerResponse = Gate::forUser($checker)->inspect('authorize', $txn);
        $this->assertTrue($checkerResponse->denied(), 'Checker must be denied from 1st authorization');
        $this->assertEquals('You checked this file; 1st authorization must come from a different user.', $checkerResponse->message());

        // 2. Policy check for different user (Authorizer 1) must ALLOW authorization
        $auth1Response = Gate::forUser($authorizer1)->inspect('authorize', $txn);
        $this->assertTrue($auth1Response->allowed(), 'Different user must be allowed 1st authorization');
    }

    public function test_checker_and_first_authorizer_cannot_perform_second_level_final_confirmation(): void
    {
        $checker = User::create([
            'name'         => 'Checker One',
            'email'        => 'checker1@jb.com',
            'mobile_no'    => '01700000001',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer1 = User::create([
            'name'         => 'Authorizer One',
            'email'        => 'auth1@jb.com',
            'mobile_no'    => '01700000002',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer2 = User::create([
            'name'         => 'Authorizer Two',
            'email'        => 'auth2@jb.com',
            'mobile_no'    => '01700000003',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $txn = BkashTransaction::create([
            'transaction_type'    => 'BEFTN',
            'reference_id'        => 'REF_TEST_002',
            'txn_id'              => 'TXN_TEST_002',
            'amount'              => 50000.00,
            'status_id'           => BkashTransaction::STATUS_AUTH_1_APPROVED,
            'checked_by'          => $checker->name,
            'checked_by_id'       => $checker->id,
            'checked_at'          => Carbon::now(),
            'approved_by_1'       => $authorizer1->name,
            'approved_by_1_id'    => $authorizer1->id,
            'approved_at_1'       => Carbon::now(),
        ]);

        // 1. Checker must be blocked from final confirmation
        $checkerConfirmResponse = Gate::forUser($checker)->inspect('confirm', $txn);
        $this->assertTrue($checkerConfirmResponse->denied(), 'Checker must be denied from final confirmation');
        $this->assertEquals('You checked this file; final confirmation must come from a third distinct user.', $checkerConfirmResponse->message());

        // 2. 1st Authorizer must be blocked from final confirmation
        $auth1ConfirmResponse = Gate::forUser($authorizer1)->inspect('confirm', $txn);
        $this->assertTrue($auth1ConfirmResponse->denied(), '1st Authorizer must be denied from final confirmation');
        $this->assertEquals('You 1st-authorized this file; final confirmation must come from a third distinct user.', $auth1ConfirmResponse->message());

        // 3. Third distinct user (Authorizer 2) must be ALLOWED
        $auth2ConfirmResponse = Gate::forUser($authorizer2)->inspect('confirm', $txn);
        $this->assertTrue($auth2ConfirmResponse->allowed(), 'Third distinct user must be allowed final confirmation');
    }

    public function test_three_distinct_users_successfully_complete_entire_pipeline(): void
    {
        $userA = User::create(['name' => 'User A (Checker)', 'email' => 'a@jb.com', 'mobile_no' => '01711111111', 'organization' => 'JB', 'password' => bcrypt('123')]);
        $userB = User::create(['name' => 'User B (Auth 1)', 'email' => 'b@jb.com', 'mobile_no' => '01722222222', 'organization' => 'JB', 'password' => bcrypt('123')]);
        $userC = User::create(['name' => 'User C (Auth 2)', 'email' => 'c@jb.com', 'mobile_no' => '01733333333', 'organization' => 'JB', 'password' => bcrypt('123')]);

        // Step 1: Initial upload
        $txn = BkashTransaction::create([
            'transaction_type' => 'RTGS',
            'reference_id'     => 'REF_STAGE_E2E',
            'txn_id'           => 'TXN_STAGE_E2E',
            'amount'           => 100000.00,
            'status_id'        => BkashTransaction::STATUS_PENDING_CHECKER,
        ]);

        // User A performs Checker action
        $checkResponse = Gate::forUser($userA)->inspect('check', $txn);
        $this->assertTrue($checkResponse->allowed());

        $txn->update([
            'status_id'     => BkashTransaction::STATUS_CHECKED,
            'checked_by'    => $userA->name,
            'checked_by_id' => $userA->id,
            'checked_at'    => Carbon::now(),
        ]);

        // Step 2: 1st Authorization
        $this->assertTrue(Gate::forUser($userA)->inspect('authorize', $txn)->denied());
        $this->assertTrue(Gate::forUser($userB)->inspect('authorize', $txn)->allowed());

        $txn->update([
            'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
            'approved_by_1'    => $userB->name,
            'approved_by_1_id' => $userB->id,
            'approved_at_1'    => Carbon::now(),
        ]);

        // Step 3: Final Confirmation
        $this->assertTrue(Gate::forUser($userA)->inspect('confirm', $txn)->denied());
        $this->assertTrue(Gate::forUser($userB)->inspect('confirm', $txn)->denied());
        $this->assertTrue(Gate::forUser($userC)->inspect('confirm', $txn)->allowed());

        $txn->update([
            'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
            'approved_by_2'    => $userC->name,
            'approved_by_2_id' => $userC->id,
            'approved_at_2'    => Carbon::now(),
            'confirmed_by'     => $userC->name,
            'confirmed_at'     => Carbon::now(),
        ]);

        $txn->refresh();
        $this->assertEquals(BkashTransaction::STATUS_FINAL_AUTHORIZED, $txn->status_id);
        $this->assertEquals($userA->id, $txn->checked_by_id);
        $this->assertEquals($userB->id, $txn->approved_by_1_id);
        $this->assertEquals($userC->id, $txn->approved_by_2_id);
    }

    public function test_batch_methods_enforce_segregation_of_duties(): void
    {
        $checker = User::create([
            'name'         => 'Batch Checker',
            'email'        => 'batch_checker@jb.com',
            'mobile_no'    => '01700000010',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer1 = User::create([
            'name'         => 'Batch Auth1',
            'email'        => 'batch_auth1@jb.com',
            'mobile_no'    => '01700000011',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer2 = User::create([
            'name'         => 'Batch Auth2',
            'email'        => 'batch_auth2@jb.com',
            'mobile_no'    => '01700000012',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $batchId = (string) Str::uuid();
        $batch = BkashTransactionBatch::create([
            'id'               => $batchId,
            'file_name'        => 'batch_sod_test.xlsx',
            'transaction_type' => 'A2A',
            'total_data'       => 1,
            'total_amount'     => 1000.00,
            'status_id'        => BkashTransaction::STATUS_CHECKED,
        ]);

        $txn = BkashTransaction::create([
            'batch_id'         => $batchId,
            'file_name'        => 'batch_sod_test.xlsx',
            'transaction_type' => 'A2A',
            'reference_id'     => 'REF_SOD_01',
            'txn_id'           => 'TXN_SOD_01',
            'amount'           => 1000.00,
            'status_id'        => BkashTransaction::STATUS_CHECKED,
            'checked_by'       => $checker->name,
            'checked_by_id'    => $checker->id,
            'checked_at'       => Carbon::now(),
        ]);

        // Checker CANNOT authorize batch
        $this->assertFalse($batch->canUserAuthorize($checker));
        $this->assertFalse($batch->canUserSelectForAction('authorizeSelectedBatches', $checker));
        $this->assertStringContainsString('verified this file as Checker', $batch->getSelectionRestrictionReason('authorizeSelectedBatches', $checker));

        // Different user (Authorizer 1) CAN authorize batch
        $this->assertTrue($batch->canUserAuthorize($authorizer1));
        $this->assertTrue($batch->canUserSelectForAction('authorizeSelectedBatches', $authorizer1));
        $this->assertNull($batch->getSelectionRestrictionReason('authorizeSelectedBatches', $authorizer1));

        // Now move to 1st Authorization approved
        $txn->update([
            'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
            'approved_by_1'    => $authorizer1->name,
            'approved_by_1_id' => $authorizer1->id,
            'approved_at_1'    => Carbon::now(),
        ]);
        $batch->update(['status_id' => BkashTransaction::STATUS_AUTH_1_APPROVED]);

        // Checker CANNOT confirm batch
        $this->assertFalse($batch->canUserConfirm($checker));
        $this->assertFalse($batch->canUserSelectForAction('confirmSelectedBatches', $checker));

        // 1st Authorizer CANNOT confirm batch
        $this->assertFalse($batch->canUserConfirm($authorizer1));
        $this->assertFalse($batch->canUserSelectForAction('confirmSelectedBatches', $authorizer1));
        $this->assertStringContainsString('1st-authorized this file', $batch->getSelectionRestrictionReason('confirmSelectedBatches', $authorizer1));

        // 3rd distinct user (Authorizer 2) CAN confirm batch
        $this->assertTrue($batch->canUserConfirm($authorizer2));
        $this->assertTrue($batch->canUserSelectForAction('confirmSelectedBatches', $authorizer2));
        $this->assertNull($batch->getSelectionRestrictionReason('confirmSelectedBatches', $authorizer2));
    }

    public function test_authorizations_page_excludes_checker_from_selection_and_blocks_checkbox(): void
    {
        $checker = User::create([
            'name'         => 'Livewire Checker',
            'email'        => 'lw_checker@jb.com',
            'mobile_no'    => '01700000020',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer = User::create([
            'name'         => 'Livewire Authorizer',
            'email'        => 'lw_authorizer@jb.com',
            'mobile_no'    => '01700000021',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $checker->assignRole('super_admin');
        $authorizer->assignRole('super_admin');

        $batch = BkashTransactionBatch::create([
            'file_name'        => 'lw_auth_test.xlsx',
            'transaction_type' => 'A2A',
            'total_data'       => 1,
            'total_amount'     => 5000.00,
            'status_id'        => BkashTransaction::STATUS_CHECKED,
        ]);
        $batchId = (string) $batch->id;

        BkashTransaction::create([
            'batch_id'         => $batchId,
            'file_name'        => 'lw_auth_test.xlsx',
            'transaction_type' => 'A2A',
            'reference_id'     => 'REF_LW_01',
            'txn_id'           => 'TXN_LW_01',
            'amount'           => 5000.00,
            'status_id'        => BkashTransaction::STATUS_CHECKED,
            'checked_by'       => $checker->name,
            'checked_by_id'    => $checker->id,
            'checked_at'       => Carbon::now(),
        ]);

        // 1. Checker user on Authorization page:
        // - Cannot select via selectAll
        // - Cannot have batch in selectedBatches
        // - HTML renders restricted lock icon and NO active checkbox
        \Livewire\Livewire::actingAs($checker)
            ->test(\App\Filament\Resources\BkashTransactionAuthorizations\Pages\ListBkashTransactionAuthorizations::class)
            ->set('selectAll', true)
            ->assertSet('selectedBatches', [])
            ->set('selectedBatches', [$batchId])
            ->assertSet('selectedBatches', [])
            ->assertSee('jb-restricted-lock')
            ->assertSee('Restricted')
            ->assertDontSee('value="' . $batchId . '"', false);

        // 2. Different user on Authorization page:
        // - CAN select via selectAll
        // - HTML renders checkbox with batch ID value
        \Livewire\Livewire::actingAs($authorizer)
            ->test(\App\Filament\Resources\BkashTransactionAuthorizations\Pages\ListBkashTransactionAuthorizations::class)
            ->set('selectAll', true)
            ->assertSet('selectedBatches', [$batchId])
            ->assertSee('value="' . $batchId . '"', false);
    }

    public function test_confirmations_page_excludes_authorizer_and_checker_from_selection(): void
    {
        $checker = User::create([
            'name'         => 'Conf Checker',
            'email'        => 'conf_checker@jb.com',
            'mobile_no'    => '01700000030',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer1 = User::create([
            'name'         => 'Conf Auth1',
            'email'        => 'conf_auth1@jb.com',
            'mobile_no'    => '01700000031',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        $authorizer2 = User::create([
            'name'         => 'Conf Auth2',
            'email'        => 'conf_auth2@jb.com',
            'mobile_no'    => '01700000032',
            'organization' => 'Janata Bank',
            'password'     => bcrypt('Secret123!'),
        ]);

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $checker->assignRole('super_admin');
        $authorizer1->assignRole('super_admin');
        $authorizer2->assignRole('super_admin');

        $batch = BkashTransactionBatch::create([
            'file_name'        => 'lw_conf_test.xlsx',
            'transaction_type' => 'BEFTN',
            'total_data'       => 1,
            'total_amount'     => 12000.00,
            'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
        ]);
        $batchId = (string) $batch->id;

        BkashTransaction::create([
            'batch_id'         => $batchId,
            'file_name'        => 'lw_conf_test.xlsx',
            'transaction_type' => 'BEFTN',
            'reference_id'     => 'REF_CONF_01',
            'txn_id'           => 'TXN_CONF_01',
            'amount'           => 12000.00,
            'status_id'        => BkashTransaction::STATUS_AUTH_1_APPROVED,
            'checked_by'       => $checker->name,
            'checked_by_id'    => $checker->id,
            'checked_at'       => Carbon::now(),
            'approved_by_1'    => $authorizer1->name,
            'approved_by_1_id' => $authorizer1->id,
            'approved_at_1'    => Carbon::now(),
        ]);

        // 1. Authorizer 1 on Confirmation page:
        // - Blocked from selecting, checkbox omitted
        \Livewire\Livewire::actingAs($authorizer1)
            ->test(\App\Filament\Resources\BkashTransactionConfirmations\Pages\ListBkashTransactionConfirmations::class)
            ->set('selectAll', true)
            ->assertSet('selectedBatches', [])
            ->set('selectedBatches', [$batchId])
            ->assertSet('selectedBatches', [])
            ->assertSee('jb-restricted-lock')
            ->assertDontSee('value="' . $batchId . '"', false);

        // 2. Checker on Confirmation page:
        // - Blocked from selecting, checkbox omitted
        \Livewire\Livewire::actingAs($checker)
            ->test(\App\Filament\Resources\BkashTransactionConfirmations\Pages\ListBkashTransactionConfirmations::class)
            ->set('selectAll', true)
            ->assertSet('selectedBatches', [])
            ->set('selectedBatches', [$batchId])
            ->assertSet('selectedBatches', [])
            ->assertSee('jb-restricted-lock')
            ->assertDontSee('value="' . $batchId . '"', false);

        // 3. 3rd distinct user (Authorizer 2) on Confirmation page:
        // - Can select, checkbox present
        \Livewire\Livewire::actingAs($authorizer2)
            ->test(\App\Filament\Resources\BkashTransactionConfirmations\Pages\ListBkashTransactionConfirmations::class)
            ->set('selectAll', true)
            ->assertSet('selectedBatches', [$batchId])
            ->assertSee('value="' . $batchId . '"', false);
    }
}
