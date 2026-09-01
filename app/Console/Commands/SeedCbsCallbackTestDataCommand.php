<?php

namespace App\Console\Commands;

use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SeedCbsCallbackTestDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:seed-cbs-callback-data {--reset : Reset existing test transaction if already present}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed test users (checker, 1st auth, 2nd auth) and a final-authorized test transaction for manual CBS callback API testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('⚠️ This command is disabled in production for safety.');
            return self::FAILURE;
        }

        $this->info('===============================================================');
        $this->info('    JANATA BANK — SEED CBS CALLBACK MANUAL TEST DATA           ');
        $this->info('===============================================================');
        $this->newLine();

        // 1. Ensure Roles Exist
        $roles = [
            'bkash_checker'      => 'bKash Checker — verifies uploaded transaction files',
            'bkash_authorizer_1' => 'bKash 1st Authorizer — first-level approval',
            'bkash_authorizer_2' => 'bKash 2nd Authorizer — final approval and CBS settlement',
        ];

        foreach ($roles as $roleName => $desc) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 2. Create or Retrieve Test Users
        $usersData = [
            [
                'name'         => 'Test Checker User',
                'email'        => 'test.checker@jbcorporate.test',
                'mobile_no'    => '01711000001',
                'organization' => 'Janata Bank PLC.',
                'role'         => 'bkash_checker',
            ],
            [
                'name'         => 'Test Authorizer 1',
                'email'        => 'test.authorizer1@jbcorporate.test',
                'mobile_no'    => '01711000002',
                'organization' => 'Janata Bank PLC.',
                'role'         => 'bkash_authorizer_1',
            ],
            [
                'name'         => 'Test Authorizer 2',
                'email'        => 'test.authorizer2@jbcorporate.test',
                'mobile_no'    => '01711000003',
                'organization' => 'Janata Bank PLC.',
                'role'         => 'bkash_authorizer_2',
            ],
        ];

        $seededUsers = [];
        foreach ($usersData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'         => $data['name'],
                    'mobile_no'    => $data['mobile_no'],
                    'organization' => $data['organization'],
                    'password'     => Hash::make('Password@123'),
                ]
            );

            if (!$user->hasRole($data['role'])) {
                $user->assignRole($data['role']);
            }

            $seededUsers[] = [
                'Role'  => $data['role'],
                'Name'  => $user->name,
                'Email' => $user->email,
                'Phone' => $user->mobile_no,
            ];
        }

        $this->info('👉 Step 1: Seeded / Verified 3-Tier Test Users:');
        $this->table(['Role', 'Name', 'Email', 'Mobile'], $seededUsers);
        $this->newLine();

        // 3. Create or Reset Test Transaction Batch
        $batchFileName = 'TEST_CBS_CALLBACK_' . date('Y_m_d') . '_Slot1.xlsx';
        $batch = BkashTransactionBatch::firstOrCreate(
            ['file_name' => $batchFileName],
            [
                'transaction_type' => 'A2A',
                'total_data'       => 1,
                'total_amount'     => 50000.00,
                'status_id'        => BkashTransaction::STATUS_FINAL_AUTHORIZED,
                'create_date'      => Carbon::today(),
            ]
        );

        // 4. Create or Reset Test Transaction
        $testRefId = 'TEST_REF_CBS_001';
        $testTxnId = 'TEST_TXN_CBS_001';

        $txn = BkashTransaction::where('reference_id', $testRefId)->orWhere('txn_id', $testTxnId)->first();

        $txnAttributes = [
            'batch_id'            => $batch->id,
            'file_name'           => $batchFileName,
            'row_sequence'        => 1,
            'transaction_type'    => 'A2A',
            'reference_id'        => $testRefId,
            'txn_id'              => $testTxnId,
            'beneficiary_account_no' => '0100111111111', // Beneficiary Acc
            'debit_account_title'    => 'Test Beneficiary User',
            'source_account_no'      => '0100202707747', // TCSA Pool (Source Account)
            'amount'                 => 50000.00,
            'status_id'              => BkashTransaction::STATUS_FINAL_AUTHORIZED, // 1003
            'checked_by'             => 'Test Checker User',
            'checked_at'             => Carbon::now()->subMinutes(15),
            'approved_by_1'          => 'Test Authorizer 1',
            'approved_at_1'          => Carbon::now()->subMinutes(10),
            'approved_by_2'          => 'Test Authorizer 2',
            'approved_at_2'          => Carbon::now()->subMinutes(5),
            'confirmed_by'           => null,
            'confirmed_at'           => null,
            'response_id'            => null,
            'cbs_success_at'         => null,
            'reject_reason'          => null,
        ];

        if ($txn) {
            $txn->update($txnAttributes);
            $this->info("👉 Step 2: Existing Test Transaction Reset to Status 1003 (FINAL_AUTHORIZED):");
        } else {
            $txn = BkashTransaction::create($txnAttributes);
            $this->info("👉 Step 2: Created New Test Transaction in Status 1003 (FINAL_AUTHORIZED):");
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['Transaction ID', $txn->txn_id],
                ['Reference ID', $txn->reference_id],
                ['Status ID', $txn->status_id . ' (STATUS_FINAL_AUTHORIZED)'],
                ['Amount (BDT)', number_format($txn->amount, 2)],
                ['Beneficiary Acc', $txn->beneficiary_account_no],
                ['TCSA Pool Acc', $txn->source_account_no],
            ]
        );
        $this->newLine();

        // 5. Output API Configuration and cURL Test Commands
        $apiKey  = config('bkash.cbs_callback_api_key', 'cbs-secret-callback-key-2026');
        $baseUrl = config('app.url', 'http://localhost:8000');

        $this->info('👉 Step 3: CBS Callback Configuration & Quickstart');
        $this->line("   CBS Callback API Key : <comment>{$apiKey}</comment>");
        $this->line("   Callback Endpoint    : <comment>POST {$baseUrl}/api/cbs/response-callback</comment>");
        $this->newLine();

        $this->info('📋 Ready-to-use cURL for SUCCESS (Status 1006):');
        $this->line("curl -X POST {$baseUrl}/api/cbs/response-callback \\");
        $this->line('  -H "Content-Type: application/json" \\');
        $this->line("  -H \"X-CBS-API-Key: {$apiKey}\" \\");
        $this->line('  -d \'{"response_id":"CBS_RESP_SUCCESS_001","status_id":1006,"txn_id":"' . $testTxnId . '","confirmed_by":"JANATA_CBS_CORE"}\'');
        $this->newLine();

        $this->info('📋 Ready-to-use cURL for REJECTION (Status 1007):');
        $this->line("curl -X POST {$baseUrl}/api/cbs/response-callback \\");
        $this->line('  -H "Content-Type: application/json" \\');
        $this->line("  -H \"X-CBS-API-Key: {$apiKey}\" \\");
        $this->line('  -d \'{"response_id":"CBS_RESP_FAIL_001","status_id":1007,"txn_id":"' . $testTxnId . '","reason":"Beneficiary account dormant or closed","confirmed_by":"JANATA_CBS_CORE"}\'');
        $this->newLine();

        $this->info('===============================================================');
        $this->info('            SEEDING COMPLETE — READY FOR CBS TESTING           ');
        $this->info('===============================================================');

        return Command::SUCCESS;
    }
}