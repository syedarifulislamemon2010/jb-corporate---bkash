<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class SftpFileTransferService
{
    protected string $sftpDisk = 'bkash_sftp';
    protected string $localDisk = 'public';

    /**
     * Get the remote root source path from config.
     */
    public function getRemoteSourcePath(): string
    {
        return rtrim((string) config('bkash.sftp_source_path', '/var/www/html/beftn-bach-rtgs/storage/app/public/bkash'), '/');
    }

    /**
     * Get the remote uploaded (archive) path from config.
     */
    public function getRemoteUploadedPath(): string
    {
        return rtrim((string) config('bkash.sftp_uploaded_path', '/var/www/html/beftn-bach-rtgs/storage/app/public/bkash_uploaded'), '/');
    }

    /**
     * Get the remote Account-to-Account subfolder path.
     */
    public function getRemoteA2aPath(): string
    {
        if ($configured = config('bkash.sftp_a2a_path')) {
            return rtrim((string) $configured, '/');
        }
        return $this->getRemoteSourcePath() . '/Account to-Account';
    }

    /**
     * Get the remote BEFTN subfolder path.
     */
    public function getRemoteBeftnPath(): string
    {
        if ($configured = config('bkash.sftp_beftn_path')) {
            return rtrim((string) $configured, '/');
        }
        return $this->getRemoteSourcePath() . '/BEFTN';
    }

    /**
     * Get the remote RTGS subfolder path.
     */
    public function getRemoteRtgsPath(): string
    {
        if ($configured = config('bkash.sftp_rtgs_path')) {
            return rtrim((string) $configured, '/');
        }
        return $this->getRemoteSourcePath() . '/RTGS';
    }

    /**
     * Get the local landing directory name.
     */
    public function getLocalLandingDir(): string
    {
        return 'Bkash_Files';
    }

    /**
     * Get the local archive directory name.
     */
    public function getLocalArchiveDir(): string
    {
        return 'Bkash_Files_Uploaded';
    }

    /**
     * Fetch list of new files across all 3 dedicated subfolders (A2A, BEFTN, RTGS)
     * as well as the legacy flat source folder for 100% backward compatibility.
     *
     * Returns Collection of items:
     * [
     *    'remote_path'  => string,
     *    'channel_hint' => 'A2A'|'BEFTN'|'RTGS'|'AUTO',
     *    'folder'       => 'Account-to-Account'|'BEFTN'|'RTGS'|'ROOT',
     * ]
     */
    public function fetchNewFiles(): Collection
    {
        $allFiles = collect();
        $seenPaths = [];

        // Define folders to scan: [Folder Name => [Path, Channel Hint]]
        $targetFolders = [
            'Account-to-Account' => [$this->getRemoteA2aPath(), 'A2A'],
            'BEFTN'              => [$this->getRemoteBeftnPath(), 'BEFTN'],
            'RTGS'               => [$this->getRemoteRtgsPath(), 'RTGS'],
        ];

        // 1. Scan 3 dedicated subfolders
        foreach ($targetFolders as $folderName => [$folderPath, $channelHint]) {
            try {
                if (Storage::disk($this->sftpDisk)->exists($folderPath)) {
                    $files = Storage::disk($this->sftpDisk)->files($folderPath);
                    foreach ($files as $filePath) {
                        if ($this->isProcessableFile($filePath) && !isset($seenPaths[$filePath])) {
                            $seenPaths[$filePath] = true;
                            $allFiles->push([
                                'remote_path'  => $filePath,
                                'channel_hint' => $channelHint,
                                'folder'       => $folderName,
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("SFTP: Failed to scan subfolder [{$folderName}] at {$folderPath}: " . $e->getMessage());
            }
        }

        // 2. Scan legacy root source folder (Backward Compatibility fallback)
        try {
            $rootSource = $this->getRemoteSourcePath();
            if (Storage::disk($this->sftpDisk)->exists($rootSource)) {
                $rootFiles = Storage::disk($this->sftpDisk)->files($rootSource);
                foreach ($rootFiles as $filePath) {
                    // Only process files directly in root (not in already-scanned subdirectories)
                    if ($this->isProcessableFile($filePath) && !isset($seenPaths[$filePath])) {
                        $seenPaths[$filePath] = true;
                        $allFiles->push([
                            'remote_path'  => $filePath,
                            'channel_hint' => 'AUTO',
                            'folder'       => 'ROOT',
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("SFTP: Failed to scan legacy root source at {$rootSource}: " . $e->getMessage());
        }

        return $allFiles;
    }

    /**
     * Check if a file has a valid spreadsheet/CSV extension.
     */
    protected function isProcessableFile(string $filePath): bool
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($ext, ['xls', 'xlsx', 'csv']);
    }

    /**
     * Download a remote file to local storage.
     * Returns the full local file path or null on failure.
     */
    public function downloadToLocal(string $remoteFilePath): ?string
    {
        try {
            $fileName = basename($remoteFilePath);
            $localRelativePath = $this->getLocalLandingDir() . '/' . $fileName;

            // Ensure directory exists
            $localDir = storage_path('app/public/' . $this->getLocalLandingDir());
            if (!is_dir($localDir)) {
                mkdir($localDir, 0755, true);
            }

            $content = Storage::disk($this->sftpDisk)->get($remoteFilePath);

            if (empty($content)) {
                Log::warning("SFTP: Empty file skipped: {$fileName}");
                return null;
            }

            Storage::disk($this->localDisk)->put($localRelativePath, $content);

            $localFullPath = Storage::disk($this->localDisk)->path($localRelativePath);

            Log::info("SFTP: Downloaded {$fileName} to local storage from {$remoteFilePath}.");

            return $localFullPath;
        } catch (\Throwable $e) {
            Log::error("SFTP: Failed to download {$remoteFilePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Move a file on the SFTP server from source/subfolder to uploaded folder.
     * This clears the source location after successful download.
     */
    public function moveToUploaded(string $remoteFilePath): bool
    {
        try {
            $fileName = basename($remoteFilePath);
            $uploadedPath = $this->getRemoteUploadedPath() . '/' . $fileName;

            // Check if already exists in uploaded folder
            if (Storage::disk($this->sftpDisk)->exists($uploadedPath)) {
                Log::info("SFTP: File already in uploaded folder, deleting source: {$fileName}");
                Storage::disk($this->sftpDisk)->delete($remoteFilePath);
                return true;
            }

            Storage::disk($this->sftpDisk)->move($remoteFilePath, $uploadedPath);

            Log::info("SFTP: Moved {$fileName} to uploaded folder: {$uploadedPath}");
            return true;
        } catch (\Throwable $e) {
            Log::error("SFTP: Failed to move {$remoteFilePath} to uploaded: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Archive a locally processed file.
     * Moves from Bkash_Files/ to Bkash_Files_Uploaded/YYYY-MM-DD/
     */
    public function archiveLocalFile(string $localFullPath): ?string
    {
        try {
            $fileName = basename($localFullPath);
            $dateFolder = now()->format('Y-m-d');
            $archiveRelative = $this->getLocalArchiveDir() . '/' . $dateFolder . '/' . $fileName;

            // Ensure archive directory exists
            $archiveDir = storage_path('app/public/' . $this->getLocalArchiveDir() . '/' . $dateFolder);
            if (!is_dir($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }

            if (is_file($localFullPath)) {
                $content = file_get_contents($localFullPath);
                Storage::disk($this->localDisk)->put($archiveRelative, $content);
                unlink($localFullPath); // Remove from landing dir

                Log::info("Local: Archived {$fileName} to {$archiveRelative}");
                return $archiveRelative;
            }

            return null;
        } catch (\Throwable $e) {
            Log::error("Local: Failed to archive {$localFullPath}: " . $e->getMessage());
            return null;
        }
    }
}
