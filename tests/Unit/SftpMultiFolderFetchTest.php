<?php

namespace Tests\Unit;

use App\Services\BkashExcelParserService;
use App\Services\SftpFileTransferService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SftpMultiFolderFetchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('bkash_sftp');
        Storage::fake('public');
    }

    public function test_config_contains_all_three_subfolder_keys(): void
    {
        $this->assertArrayHasKey('sftp_a2a_path', config('bkash'));
        $this->assertArrayHasKey('sftp_beftn_path', config('bkash'));
        $this->assertArrayHasKey('sftp_rtgs_path', config('bkash'));
        $this->assertArrayHasKey('sftp_source_path', config('bkash'));
    }

    public function test_sftp_service_resolves_subfolder_paths_correctly(): void
    {
        Config::set('bkash.sftp_source_path', '/var/sftp/bkash');
        Config::set('bkash.sftp_a2a_path', '/custom/a2a');
        Config::set('bkash.sftp_beftn_path', null); // default fallback
        Config::set('bkash.sftp_rtgs_path', null); // default fallback

        $service = new SftpFileTransferService();

        $this->assertEquals('/custom/a2a', $service->getRemoteA2aPath());
        $this->assertEquals('/var/sftp/bkash/BEFTN', $service->getRemoteBeftnPath());
        $this->assertEquals('/var/sftp/bkash/RTGS', $service->getRemoteRtgsPath());
    }

    public function test_sftp_service_fetches_files_across_all_three_subfolders_and_legacy_root(): void
    {
        Config::set('bkash.sftp_source_path', 'bkash_root');
        Config::set('bkash.sftp_a2a_path', 'bkash_root/Account-to-Account');
        Config::set('bkash.sftp_beftn_path', 'bkash_root/BEFTN');
        Config::set('bkash.sftp_rtgs_path', 'bkash_root/RTGS');

        // Place test files in each subfolder
        Storage::disk('bkash_sftp')->put('bkash_root/Account-to-Account/JANATA_BANK_2026_08_23_1Slot1.xlsx', 'dummy-a2a');
        Storage::disk('bkash_sftp')->put('bkash_root/BEFTN/BEFTN_JANATA_BANK_2026_08_23_1Slot1.xlsx', 'dummy-beftn');
        Storage::disk('bkash_sftp')->put('bkash_root/RTGS/RTGS_JANATA_BANK_2026_08_23_1Slot1.xlsx', 'dummy-rtgs');
        
        // Place a legacy file in the root folder
        Storage::disk('bkash_sftp')->put('bkash_root/JANATA_BANK_2026_08_23_1Slot2.xlsx', 'dummy-legacy');

        $service = new SftpFileTransferService();
        $fetched = $service->fetchNewFiles();

        $this->assertCount(4, $fetched);

        $channels = $fetched->pluck('channel_hint')->toArray();
        $this->assertContains('A2A', $channels);
        $this->assertContains('BEFTN', $channels);
        $this->assertContains('RTGS', $channels);
        $this->assertContains('AUTO', $channels);
    }

    public function test_channel_detection_and_filename_validation(): void
    {
        $this->assertEquals('A2A', BkashExcelParserService::detectChannelType('JANATA_BANK_2026_08_23_1Slot1.xlsx'));
        $this->assertEquals('BEFTN', BkashExcelParserService::detectChannelType('BEFTN_JANATA_BANK_2026_08_23_1Slot1.xlsx'));
        $this->assertEquals('RTGS', BkashExcelParserService::detectChannelType('RTGS_JANATA_BANK_2026_08_23_1Slot1.xlsx'));
        $this->assertEquals('UNKNOWN', BkashExcelParserService::detectChannelType('OTHER_FILE.xlsx'));

        $this->assertTrue(BkashExcelParserService::validateFileName('JANATA_BANK_2026_08_23_1Slot1.xlsx', 'A2A'));
        $this->assertTrue(BkashExcelParserService::validateFileName('BEFTN_JANATA_BANK_2026_08_23_1Slot1.xlsx', 'BEFTN'));
        $this->assertTrue(BkashExcelParserService::validateFileName('RTGS_JANATA_BANK_2026_08_23_1Slot1.xlsx', 'RTGS'));
    }

    public function test_single_debit_account_file_validation_passes(): void
    {
        $headers = ['Ref No', 'Debit Account', 'Amount', 'Bank Account Name', 'Bank Account No'];
        $rows = [
            $headers,
            ['REF001', '0100202707747', '500', 'Customer 1', '4512442413566'],
            ['REF002', '0100202707747', '1000', 'Customer 2', '4512442413567'],
        ];

        $result = BkashExcelParserService::validateFileLevelDebitAccounts($rows, $headers);

        $this->assertTrue($result['is_valid']);
        $this->assertEquals(['0100202707747'], $result['debit_accounts']);
        $this->assertNull($result['error_message']);
    }

    public function test_multi_debit_account_file_validation_fails(): void
    {
        $headers = ['Ref No', 'Debit Account', 'Amount', 'Bank Account Name', 'Bank Account No'];
        $rows = [
            $headers,
            ['REF001', '0100202707747', '500', 'Customer 1', '4512442413566'],
            ['REF002', '0100224107522', '1000', 'Customer 2', '4512442413567'], // Different debit account!
        ];

        $result = BkashExcelParserService::validateFileLevelDebitAccounts($rows, $headers);

        $this->assertFalse($result['is_valid']);
        $this->assertCount(2, $result['debit_accounts']);
        $this->assertStringContainsString('File contains multiple debit accounts', $result['error_message']);
        $this->assertStringContainsString('0100202707747', $result['error_message']);
        $this->assertStringContainsString('0100224107522', $result['error_message']);
    }
}
