<?php

namespace Tests\Unit;

use App\Filament\Pages\Dashboard;
use App\Models\Mt940DeliveryLog;
use App\Models\NotificationOutbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_activities_returns_empty_when_no_real_records_exist(): void
    {
        $dashboard = new Dashboard();
        $activities = $dashboard->getRecentActivities();

        // Must not contain any dummy fallback data
        $this->assertIsArray($activities);
        $this->assertEmpty($activities);
    }

    public function test_recent_activities_returns_real_notification_records(): void
    {
        NotificationOutbox::create([
            'event_type'      => 'STAGE_1_SFTP',
            'file_name'       => 'bKash_Test_File_2026.xlsx',
            'total_trn'       => 50,
            'total_amount'    => 50000.00,
            'actor_name'      => 'System Ingestion',
            'recipient_group' => 'CHECKER',
            'status'          => 'SENT',
        ]);

        $dashboard = new Dashboard();
        $activities = $dashboard->getRecentActivities();

        $this->assertCount(1, $activities);
        $this->assertStringContainsString('bKash_Test_File_2026.xlsx', $activities[0]['title']);
    }

    public function test_mt940_status_returns_pending_when_no_delivery_log_exists(): void
    {
        $dashboard = new Dashboard();
        $mt940Statuses = $dashboard->getMt940Status();

        $this->assertCount(2, $mt940Statuses);
        $this->assertEquals('Pending first delivery', $mt940Statuses[0]['status']);
        $this->assertFalse($mt940Statuses[0]['is_ok']);
        $this->assertEquals('Pending first delivery', $mt940Statuses[1]['status']);
        $this->assertFalse($mt940Statuses[1]['is_ok']);
    }

    public function test_mt940_status_returns_actual_delivery_log_data(): void
    {
        Mt940DeliveryLog::create([
            'account_no'     => '0100202707747',
            'statement_date' => now()->format('Y-m-d'),
            'file_name'      => 'MT940_0100202707747_20260825.sta',
            'status'         => 'Delivered to SFTP',
            'is_ok'          => true,
            'delivered_at'   => now(),
        ]);

        $dashboard = new Dashboard();
        $mt940Statuses = $dashboard->getMt940Status();

        $tcsaStatus = collect($mt940Statuses)->firstWhere('account', '0100202707747 (TCSA)');
        $this->assertNotNull($tcsaStatus);
        $this->assertEquals('Delivered to SFTP', $tcsaStatus['status']);
        $this->assertTrue($tcsaStatus['is_ok']);
    }
}
