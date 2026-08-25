<?php

namespace App\Console\Commands;

use App\Models\Mt940DeliveryLog;
use App\Services\Mt940GeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateMt940Command extends Command
{
    protected $signature = 'mt940:generate
                            {--account= : Specific account number (default: both TCSA & Operational)}
                            {--date= : Date for statement (default: today)}
                            {--push-sftp : Also push generated files to SFTP server}';

    protected $description = 'Generate MT940 SWIFT statements and optionally push to SFTP.';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $accounts = $this->option('account')
            ? [$this->option('account')]
            : ['0100202707747', '0100224107522'];

        $this->info("Generating MT940 statements for " . $date->format('Y-m-d') . "...");

        $generatedFiles = [];

        foreach ($accounts as $accountNumber) {
            try {
                $statement = Mt940GeneratorService::generateStatement($accountNumber, $date);

                $fileName = "MT940_{$accountNumber}_{$date->format('Ymd')}.sta";
                $generatedFiles[$accountNumber] = $fileName;

                Mt940DeliveryLog::create([
                    'account_no'     => $accountNumber,
                    'statement_date' => $date->format('Y-m-d'),
                    'file_name'      => $fileName,
                    'status'         => 'Generated Locally',
                    'is_ok'          => true,
                    'delivered_at'   => now(),
                ]);

                $this->info("✅ Generated: {$fileName}");
            } catch (\Throwable $e) {
                Mt940DeliveryLog::create([
                    'account_no'     => $accountNumber,
                    'statement_date' => $date->format('Y-m-d'),
                    'file_name'      => "MT940_{$accountNumber}_{$date->format('Ymd')}.sta",
                    'status'         => 'Failed',
                    'is_ok'          => false,
                    'error_message'  => $e->getMessage(),
                ]);

                $this->error("Failed for {$accountNumber}: " . $e->getMessage());
                Log::error("MT940 generation failed for {$accountNumber}: " . $e->getMessage());
            }
        }

        // Push to SFTP if requested
        if ($this->option('push-sftp') && !empty($generatedFiles)) {
            $this->pushToSftp($generatedFiles, $date);
        }

        $this->info("MT940 generation complete.");
        return Command::SUCCESS;
    }

    private function pushToSftp(array $generatedFiles, Carbon $date): void
    {
        $this->info("Pushing MT940 files to SFTP...");

        foreach ($generatedFiles as $accountNumber => $fileName) {
            try {
                $localPath = "MT940_Statements/{$fileName}";

                if (!Storage::disk('public')->exists($localPath)) {
                    $this->warn("File not found locally: {$localPath}");
                    continue;
                }

                $content = Storage::disk('public')->get($localPath);
                $remotePath = "/var/www/html/beftn-bach-rtgs/storage/app/public/mt940/{$fileName}";

                Storage::disk('bkash_sftp')->put($remotePath, $content);

                Mt940DeliveryLog::create([
                    'account_no'     => (string) $accountNumber,
                    'statement_date' => $date->format('Y-m-d'),
                    'file_name'      => $fileName,
                    'status'         => 'Delivered to SFTP',
                    'is_ok'          => true,
                    'delivered_at'   => now(),
                ]);

                $this->info("📤 Pushed to SFTP: {$fileName}");
                Log::info("MT940 pushed to SFTP: {$fileName}");
            } catch (\Throwable $e) {
                Mt940DeliveryLog::create([
                    'account_no'     => (string) $accountNumber,
                    'statement_date' => $date->format('Y-m-d'),
                    'file_name'      => $fileName,
                    'status'         => 'SFTP Delivery Failed',
                    'is_ok'          => false,
                    'delivered_at'   => now(),
                    'error_message'  => $e->getMessage(),
                ]);

                $this->error("SFTP push failed for {$accountNumber}: " . $e->getMessage());
                Log::error("MT940 SFTP push failed: " . $e->getMessage());
            }
        }
    }
}
