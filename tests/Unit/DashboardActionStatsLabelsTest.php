<?php

namespace Tests\Unit;

use App\Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActionStatsLabelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_action_stats_provides_unique_and_stage_specific_empty_and_action_labels(): void
    {
        $dashboard = new Dashboard();
        $stats = $dashboard->getActionStats();

        // 1. Verify structure
        $this->assertArrayHasKey('pending_checker', $stats);
        $this->assertArrayHasKey('pending_auth1', $stats);
        $this->assertArrayHasKey('pending_auth2', $stats);

        $checker = $stats['pending_checker'];
        $auth1   = $stats['pending_auth1'];
        $auth2   = $stats['pending_auth2'];

        $this->assertArrayHasKey('empty_label', $checker);
        $this->assertArrayHasKey('action_label', $checker);
        $this->assertArrayHasKey('empty_label', $auth1);
        $this->assertArrayHasKey('action_label', $auth1);
        $this->assertArrayHasKey('empty_label', $auth2);
        $this->assertArrayHasKey('action_label', $auth2);

        // 2. Verify all empty labels are mutually distinct (no copy-paste cross contamination)
        $this->assertNotEquals($checker['empty_label'], $auth1['empty_label'], 'Checker and Auth 1 empty labels must be distinct');
        $this->assertNotEquals($auth1['empty_label'], $auth2['empty_label'], 'Auth 1 and Auth 2 empty labels must be distinct');
        $this->assertNotEquals($checker['empty_label'], $auth2['empty_label'], 'Checker and Auth 2 empty labels must be distinct');

        // 3. Verify all action labels are mutually distinct
        $this->assertNotEquals($checker['action_label'], $auth1['action_label'], 'Checker and Auth 1 action labels must be distinct');
        $this->assertNotEquals($auth1['action_label'], $auth2['action_label'], 'Auth 1 and Auth 2 action labels must be distinct');
        $this->assertNotEquals($checker['action_label'], $auth2['action_label'], 'Checker and Auth 2 action labels must be distinct');

        // 4. Verify stage-specific keywords in empty labels
        $this->assertStringContainsString('check', strtolower($checker['empty_label']));
        $this->assertStringContainsString('1st auth', strtolower($auth1['empty_label']));
        $this->assertStringContainsString('final auth', strtolower($auth2['empty_label']));

        // 5. Verify stage-specific keywords in action labels
        $this->assertStringContainsString('check', strtolower($checker['action_label']));
        $this->assertStringContainsString('1st', strtolower($auth1['action_label']));
        $this->assertStringContainsString('final', strtolower($auth2['action_label']));
    }
}