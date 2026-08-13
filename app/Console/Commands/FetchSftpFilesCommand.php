<?php

namespace App\Console\Commands;

use App\Jobs\ProcessBkashFileJob;
use App\Models\BkashTransactionBatch;
use App\Services\BkashExcelParserService;
use App\Services\SftpFileTransferService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchSftpFilesCommand extends Command
{
    protected $signature = 'sftp:fetch-bkash-files {--dry-run : List files without processing}';

    protected $description = 'Fetch bKash Excel files from SFTP server, download locally, and dispatch processing jobs.';

    public function handle(SftpFileTransferService $sftpService): int
    {
        $this->info('Starting bKash SFTP file scanner...');
        Log::info('SFTP Scanner: Starting scan cycle.');

        $remoteFiles = $sftpService->fetchNewFiles();

        if ($remoteFiles->isEmpty()) {
            $this->info('No new files found on SFTP server.');
            Log::info('SFTP Scanner: No new files found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$remoteFiles->count()} file(s) on SFTP server.");

        if ($this->option('dry-run')) {
            $remoteFiles->each(fn (string $f) => $this->line(' - ' . basename($f)));
            return Command::SUCCESS;
        }

        $processed = 0;
        $skipped = 0;

        foreach ($remoteFiles as $remoteFilePath) {
            $fileName = basename($remoteFilePath);

            // Skip already-ingested files
            if (BkashTransactionBatch::where('file_name', $fileName)->exists()) {
                $this->warn("Skipping duplicate: {$fileName}");
                Log::warning("SFTP Scanner: Duplicate file skipped — {$fileName}");

                // Still move it out of source folder
                $sftpService->moveToUploaded($remoteFilePath);
                $skipped++;
                continue;
            }

            // Step 1: Download to local
            $localPath = $sftpService->downloadToLocal($remoteFilePath);

            if (!$localPath) {
                $this->error("Failed to download: {$fileName}");
                continue;
            }

            // Step 2: Detect channel type from filename
            $channelType = BkashExcelParserService::detectChannelType($fileName);

            if ($channelType === 'UNKNOWN') {
                $this->warn("Unknown channel type for: {$fileName}, defaulting to A2A");
                $channelType = 'A2A';
            }

            // Step 3: Dispatch processing job to queue
            ProcessBkashFileJob::dispatch($localPath, $channelType, 'SFTP_CRON');

            $this->info("Dispatched job for: {$fileName} [{$channelType}]");

            // Step 4: Move file on SFTP to uploaded folder
            $sftpService->moveToUploaded($remoteFilePath);

            $processed++;
        }

        $this->info("Scan complete. Processed: {$processed}, Skipped: {$skipped}");
        Log::info("SFTP Scanner: Cycle complete. Processed: {$processed}, Skipped: {$skipped}");

        return Command::SUCCESS;
    }
}
