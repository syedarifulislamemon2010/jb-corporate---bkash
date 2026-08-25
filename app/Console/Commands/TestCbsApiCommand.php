<?php

namespace App\Console\Commands;

use App\Services\CbsApiService;
use App\Models\BkashTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestCbsApiCommand extends Command
{
    protected $signature = 'cbs:test-api {--dry-run : Only test login without sending a test transaction}';

    protected $description = 'Test CBS / BACH Host-to-Host API connectivity, login authentication, and transaction posting.';

    public function handle(CbsApiService $apiService): int
    {
        $this->info("=================================================");
        $this->info("   JANATA BANK - CBS HOST-TO-HOST API TESTER     ");
        $this->info("=================================================");

        $baseUrl = config('bkash.cbs_api.base_url', 'http://172.18.18.64');
        $username = config('bkash.cbs_api.username', 'API');

        $this->line("Target Server: <comment>{$baseUrl}</comment>");
        $this->line("API Username:  <comment>{$username}</comment>");
        $this->newLine();

        // 1. Test Login & Authentication
        $this->info("👉 Step 1: Testing Login & Token Generation (POST /api/login)...");

        $token = $apiService->getAuthToken(forceRefresh: true);

        if (!empty($token)) {
            $this->info("✅ Login SUCCESSFUL!");
            $maskedToken = substr($token, 0, 15) . '...' . substr($token, -10);
            $this->line("   Bearer Token Received: <comment>{$maskedToken}</comment>");
        } else {
            $this->error("❌ Login FAILED!");
            $this->warn("   Please verify network connectivity to {$baseUrl} (VPN or Bank Intranet required).");
            return Command::FAILURE;
        }

        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info("Dry-run completed. Skipped transaction test.");
            return Command::SUCCESS;
        }

        // 2. Test BEFTN Transaction Posting
        $this->info("👉 Step 2: Testing BEFTN Transaction Endpoint (POST /api/bkash-transactions)...");

        $txn = BkashTransaction::where('status_id', 1003)->get();

        if(count($txn) > 0) {
            foreach ($txn as $transaction) {
                $result = $apiService->settleTransaction($transaction);

                if ($result['success']) {
                    if ($result['response']['responseCode'] === 200 || $result['response']['responseCode'] === 201) {
                        BkashTransaction::where('id', $transaction->id)->where('status_id', 1003)->update(['status_id' => 1004, 'response_id' => $result['response']['responseId']]);
                    }
                    $this->info("✅ Transaction Posting SUCCESSFUL!");
                    $this->line("   HTTP Status: <comment>{$result['status_code']}</comment>");
                    $this->line("   Server Response: " . json_encode($result['response'], JSON_PRETTY_PRINT));
                } else {
                    $this->warn("⚠️ Transaction Posting Response:");
                    $this->line("   HTTP Status: <comment>{$result['status_code']}</comment>");
                    $this->line("   Message:     <comment>{$result['message']}</comment>");
                    if (!empty($result['response'])) {
                        $this->line("   Payload:     " . (is_array($result['response']) ? json_encode($result['response']) : $result['response']));
                    }
                }
            }
        }

        $this->newLine();
        $this->info("=================================================");
        $this->info("                 TEST FINISHED                   ");
        $this->info("=================================================");

        return Command::SUCCESS;
    }
}
