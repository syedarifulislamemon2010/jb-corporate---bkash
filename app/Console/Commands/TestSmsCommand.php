<?php

namespace App\Console\Commands;

use App\Helper\SMSGenerateHelper;
use Illuminate\Console\Command;

class TestSmsCommand extends Command
{
    protected $signature = 'cbs:test-sms {mobile? : Recipient mobile number} {--type=1 : Template type (1=Account Create, 5=RTGS Confirmation, 6=EFT Return, 7=RTGS Return)}';

    protected $description = 'Test Janata Bank SMS Gateway API with registered bank templates.';

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

        $type = (int) $this->option('type');
        $apiUrl = config('bkash.sms_api_url', 'http://172.17.20.17/JBSmsApi/Send');
        
        $this->line("Target Gateway URL: <comment>{$apiUrl}</comment>");
        $this->line("Recipient Mobile:   <comment>{$mobile}</comment>");
        $this->line("Template Type:      <comment>Type {$type}</comment>");
        $this->newLine();

        $this->info("👉 Dispatching SMS via SMSGenerateHelper::generate()...");

        if ($type == 5) {
            // RTGS Credit Confirmation
            $response = SMSGenerateHelper::generate($mobile, '', 5, '0100229766842', 'JANATA BANK', '500 BDT', date('Y-m-d'), date('h:i A'));
        } elseif ($type == 6) {
            // EFT Return Reason
            $response = SMSGenerateHelper::generate($mobile, '', 6, '0100229766842', 'JANATA BANK', '500 BDT', date('Y-m-d'), '', 'Invalid Routing');
        } elseif ($type == 7) {
            // RTGS Return Reason
            $response = SMSGenerateHelper::generate($mobile, '', 7, '0100229766842', 'JANATA BANK', '500 BDT', date('Y-m-d'), '', 'Account Closed');
        } else {
            // Default: Type 1 (Account Create)
            $response = SMSGenerateHelper::generate($mobile, 'Temp@' . rand(1000, 9999), 1);
        }

        $this->newLine();
        $this->info("=================================================");
        $this->info("               GATEWAY RESPONSE                  ");
        $this->info("=================================================");

        $this->line(json_encode($response, JSON_PRETTY_PRINT));
        $this->newLine();

        if (isset($response->StatusCode) && $response->StatusCode == 1) {
            $this->info("✅ SMS SENT SUCCESSFULLY! (SmsID: {$response->SmsID})");
        } else {
            $this->warn("⚠️ Gateway Response: " . ($response->Message ?? $response->StatusText ?? 'Error'));
        }

        return Command::SUCCESS;
    }
}
