<?php

namespace App\Console\Commands;

use App\Models\BkashTransaction;
use App\Models\BkashTransactionBatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SeedTestUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:seed-users {--with-transaction : Also seed a test transaction in STATUS_FINAL_AUTHORIZED}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed test users with known credentials for Sanctum token-based Postman API testing (dev/staging only)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 1. Environment guard
        if (app()->environment('production')) {
            $this->error('❌ This command is disabled in production environment.');
            return Command::FAILURE;
        }

        $this->info('===============================================================');
        $this->info('    JANATA BANK — SEED TEST USERS FOR SANCTUM API TESTING      ');
        $this->info('===============================================================');
        $this->newLine();

        // 2. Ensure Roles Exist
        $roles = [
            'super_admin'        => 'Super Administrator with full system access',
            'bkash_checker'      => 'bKash Checker — verifies uploaded transaction files',
            'bkash_authorizer_1' => 'bKash 1st Authorizer — first-level approval',
            'bkash_authorizer_2' => 'bKash 2nd Authorizer — final approval and CBS settlement',
        ];

        foreach ($roles as $roleName => $desc) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // 3. Create or update test users with known credentials
        $usersData = [
            [
                'name'         => 'Syed Ariful Islam Emon',
                'email'        => 'emon@jb.com',
                'mobile_no'    => '01711223344',
                'organization' => 'Janata Bank PLC.',
                'role'         => 'super_admin',
                'password'     => '123456',
            ],
            [
                'name'         => 'G S Kibria',
                'email'        => 'kibria@jb.com',
                'mobile_no'    => '01738535099',
                'organization' => 'Janata Bank PLC.',
                'role'         => 'super_admin',
                'password'     => 'password',
            ],
            [
                'name'         => 'bKash Checker Test User',
                'email'        => 'checker@test.jbcorporate.com',
                'mobile_no'    => '01711000001',
                'organization' => 'Janata Bank PLC.',
                'role'         => 'bkash_checker',
                'password'     => 'Test@Pass123',
            ],
            [
                'name'         => 'bKash 1st Authorizer Test User',
                'email'        => 'authorizer1@test.jbcorporate.com',
                'mobile_no'    => '01711000002',
                'organization' => 'Janata Bank PLC.',
                'role'         => 'bkash_authorizer_1',
                'password'     => 'Test@Pass123',
            ],
            [
                'name'         => 'bKash 2nd Authorizer Test User',
                'email'        => 'authorizer2@test.jbcorporate.com',
                'mobile_no'    => '01711000003',
                'organization' => 'Janata Bank PLC.',
                'role'         => 'bkash_authorizer_2',
                'password'     => 'Test@Pass123',
            ],
        ];

        $seededUsersTable = [];
        foreach ($usersData as $data) {
            $user = User::where('email', $data['email'])
                ->orWhere('mobile_no', $data['mobile_no'])
                ->first();

            if ($user) {
                $user->update([
                    'name'         => $data['name'],
                    'email'        => $data['email'],
                    'mobile_no'    => $data['mobile_no'],
                    'organization' => $data['organization'],
                    'password'     => Hash::make($data['password']),
                ]);
            } else {
                $user = User::create([
                    'name'         => $data['name'],
                    'email'        => $data['email'],
                    'mobile_no'    => $data['mobile_no'],
                    'organization' => $data['organization'],
                    'password'     => Hash::make($data['password']),
                ]);
            }

            if (!$user->hasRole($data['role'])) {
                $user->assignRole($data['role']);
            }

            $seededUsersTable[] = [
                'Role'     => $data['role'],
                'Email'    => $data['email'],
                'Password' => $data['password'],
                'Name'     => $user->name,
                'Mobile'   => $user->mobile_no,
            ];
        }

        $this->info('👉 Step 1: Seeded / Verified Test Users:');
        $this->table(['Role', 'Email', 'Password', 'Name', 'Mobile'], $seededUsersTable);
        $this->newLine();

        // 4. Seed test transaction if requested (or default behavior)
        if ($this->option('with-transaction')) {
            $batchFileName = 'TEST_SANCTUM_CALLBACK_' . date('Y_m_d') . '_Slot1.xlsx';
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
                'beneficiary_account_no' => '0100111111111',
                'debit_account_title'    => 'Test Beneficiary User',
                'source_account_no'      => '0100202707747',
                'amount'                 => 50000.00,
                'status_id'              => BkashTransaction::STATUS_FINAL_AUTHORIZED, // 1003
                'checked_by'             => 'bKash Checker Test User',
                'checked_at'             => Carbon::now()->subMinutes(15),
                'approved_by_1'          => 'bKash 1st Authorizer Test User',
                'approved_at_1'          => Carbon::now()->subMinutes(10),
                'approved_by_2'          => 'bKash 2nd Authorizer Test User',
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
        }

        // 5. Output summary for Postman testing
        $baseUrl = config('app.url', 'http://localhost:8000');
        $this->info('👉 Step 3: Sanctum Token Testing Endpoints (Non-Production Only)');
        $this->line("   1. Issue Token : <comment>POST {$baseUrl}/api/test-auth/token</comment>");
        $this->line("   2. CBS Callback: <comment>POST {$baseUrl}/api/test-auth/cbs/response-callback</comment>");
        $this->newLine();

        $this->info('📋 Example cURL to get Sanctum Token:');
        $this->line("curl -X POST {$baseUrl}/api/test-auth/token \\");
        $this->line('  -H "Content-Type: application/json" \\');
        $this->line('  -d \'{"email":"checker@test.jbcorporate.com","password":"Test@Pass123"}\'');
        $this->newLine();

        $this->info('===============================================================');
        $this->info('                    SEEDING COMPLETE                           ');
        $this->info('===============================================================');

        return Command::SUCCESS;
    }
}