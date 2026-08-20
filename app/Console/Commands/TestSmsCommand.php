<?php

namespace App\Console\Commands;

use App\Helper\SMSGenerateHelper;
use Illuminate\Console\Command;

class TestSmsCommand extends Command
{
    protected $signature = 'cbs:test-sms {mobile? : Recipient mobile number (e.g. 017XXXXXXXX)}';

    protected $description = 'Test Janata Bank SMS Gateway API via SMSGenerateHelper.';

    public function handle(): int
    {
        $this->info("=================================================");
        $this->info("   JANATA BANK - SMS GATEWAY API TESTER          ");
        $this->info("=================================================");

        $mobile = $this->argument('mobile') ?? $this->ask('Enter recipient mobile number (e.g., 017XXXXXXXX)');

        if (empty($mobile)) {
            $this->error('Mobile number is required!');
            return Command::FAILURE;
        }

        $apiUrl = config('bkash.sms_api_url', 'http://172.17.20.17/JBSmsApi/Send');
        $this->line("Target Gateway URL: <comment>{$apiUrl}</comment>");
        $this->line("Recipient Mobile:   <comment>{$mobile}</comment>");
        $this->newLine();

        $testMessage = "Dear User, this is a test SMS from Janata Bank Corporate Portal bKash Settlement System. Time: " . date('Y-m-d h:i:s A');

        $this->info("👉 Dispatching SMS via SMSGenerateHelper::sendDirectSms()...");

        $response = SMSGenerateHelper::sendDirectSms($mobile, $testMessage);

        $this->newLine();
        $this->info("=================================================");
        $this->info("               GATEWAY RESPONSE                  ");
        $this->info("=================================================");

        $this->line(json_encode($response, JSON_PRETTY_PRINT));
        $this->newLine();

        if (isset($response->responseCode) && $response->responseCode == 400) {
            $this->warn("⚠️ SMS Gateway returned an error or network timeout.");
        } else {
            $this->info("✅ SMS request processed successfully!");
        }

        return Command::SUCCESS;
    }
}
