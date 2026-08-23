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

    protected $description = 'Fetch bKash Excel files from multi-folder SFTP server (A2A, BEFTN, RTGS), download locally, and dispatch processing jobs.';

    public function handle(SftpFileTransferService $sftpService): int
    {
        $this->info('Starting bKash multi-folder SFTP file scanner...');
        Log::info('SFTP Scanner: Starting multi-folder scan cycle (A2A, BEFTN, RTGS + Root).');

        $remoteFileItems = $sftpService->fetchNewFiles();

        if ($remoteFileItems->isEmpty()) {
            $this->info('No new files found across any SFTP folder.');
            Log::info('SFTP Scanner: No new files found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$remoteFileItems->count()} file(s) across SFTP directories.");

        if ($this->option('dry-run')) {
            $remoteFileItems->each(function ($item) {
                $path = is_array($item) ? ($item['remote_path'] ?? '') : (string) $item;
                $folder = is_array($item) ? ($item['folder'] ?? 'UNKNOWN') : 'N/A';
                $hint = is_array($item) ? ($item['channel_hint'] ?? 'AUTO') : 'AUTO';
                $this->line(" - [" . basename($path) . "] from folder: <comment>{$folder}</comment> (channel hint: {$hint})");
            });
            return Command::SUCCESS;
        }

        $processed = 0;
        $skipped = 0;

        foreach ($remoteFileItems as $item) {
            $remoteFilePath = is_array($item) ? ($item['remote_path'] ?? '') : (string) $item;
            $folderHint     = is_array($item) ? ($item['channel_hint'] ?? 'AUTO') : 'AUTO';
            $folderName     = is_array($item) ? ($item['folder'] ?? 'ROOT') : 'ROOT';

            if (empty($remoteFilePath)) {
                continue;
            }

            $fileName = basename($remoteFilePath);

            // Skip already-ingested files
            if (BkashTransactionBatch::where('file_name', $fileName)->exists()) {
                $this->warn("Skipping duplicate: {$fileName} (from {$folderName})");
                Log::warning("SFTP Scanner: Duplicate file skipped — {$fileName}");

                // Still move it out of source folder to prevent infinite loop
                $sftpService->moveToUploaded($remoteFilePath);
                $skipped++;
                continue;
            }

            // Step 1: Download to local landing directory
            $localPath = $sftpService->downloadToLocal($remoteFilePath);

            if (!$localPath) {
                $this->error("Failed to download: {$fileName} from {$folderName}");
                continue;
            }

            // Step 2: Channel Identification & Defense-in-Depth Cross-Validation
            $filenameChannel = BkashExcelParserService::detectChannelType($fileName);
            $channelType = $folderHint;

            if ($folderHint === 'AUTO') {
                // Legacy root source: determine directly from filename
                $channelType = $filenameChannel !== 'UNKNOWN' ? $filenameChannel : 'A2A';
            } else {
                // Subfolder source: use folder hint as primary, cross-validate with filename
                if ($filenameChannel !== 'UNKNOWN' && $filenameChannel !== $folderHint) {
                    $this->warn("Channel mismatch for {$fileName}: Folder is [{$folderHint}] but filename pattern is [{$filenameChannel}]. Prioritizing filename regex for safety.");
                    Log::warning("SFTP Scanner: Channel mismatch for {$fileName}. Folder: [{$folderHint}], Filename: [{$filenameChannel}]. Using {$filenameChannel}.");
                    $channelType = $filenameChannel;
                } else {
                    $channelType = $folderHint;
                }
            }

            // Step 3: Process file immediately into database
            ProcessBkashFileJob::dispatchSync($localPath, $channelType, 'SFTP_CRON');

            $this->info("Dispatched job for: {$fileName} [Channel: {$channelType}] (Source Folder: {$folderName})");

            // Step 4: Move file on SFTP to uploaded folder
            $sftpService->moveToUploaded($remoteFilePath);

            $processed++;
        }

        $this->info("Scan complete. Processed: {$processed}, Skipped: {$skipped}");
        Log::info("SFTP Scanner: Cycle complete. Processed: {$processed}, Skipped: {$skipped}");

        return Command::SUCCESS;
    }
}
