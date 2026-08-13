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
     * Get the remote source path from config.
     */
    public function getRemoteSourcePath(): string
    {
        return rtrim(config('bkash.sftp_source_path', '/var/www/html/beftn-bach-rtgs/storage/app/public/bkash'), '/');
    }

    /**
     * Get the remote uploaded (archive) path from config.
     */
    public function getRemoteUploadedPath(): string
    {
        return rtrim(config('bkash.sftp_uploaded_path', '/var/www/html/beftn-bach-rtgs/storage/app/public/bkash_uploaded'), '/');
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
     * Fetch list of new files from SFTP source directory.
     * Returns collection of remote file paths.
     */
    public function fetchNewFiles(): Collection
    {
        try {
            $sourcePath = $this->getRemoteSourcePath();
            $files = Storage::disk($this->sftpDisk)->files($sourcePath);

            return collect($files)->filter(function (string $filePath) {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                return in_array($ext, ['xls', 'xlsx', 'csv']);
            })->values();
        } catch (\Throwable $e) {
            Log::error('SFTP: Failed to list remote files: ' . $e->getMessage());
            return collect();
        }
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

            Log::info("SFTP: Downloaded {$fileName} to local storage.");

            return $localFullPath;
        } catch (\Throwable $e) {
            Log::error("SFTP: Failed to download {$remoteFilePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Move a file on the SFTP server from source to uploaded folder.
     * This clears the source folder after successful download.
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

            Log::info("SFTP: Moved {$fileName} to uploaded folder.");
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
