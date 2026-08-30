<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard as CustomDashboard;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SidebarAccordionNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->user = User::create([
            'name'         => 'Test Admin User',
            'email'        => 'admin@jb.com',
            'mobile_no'    => '01711009999',
            'organization' => 'Janata Bank PLC.',
            'password'     => bcrypt('Secret123'),
        ]);
        $this->user->assignRole('super_admin');
    }

    public function test_all_navigation_groups_are_collapsible_and_collapsed_by_default(): void
    {
        $panel = Filament::getPanel('admin');
        $navigationGroups = $panel->getNavigationGroups();

        $this->assertNotEmpty($navigationGroups);

        foreach ($navigationGroups as $group) {
            $this->assertInstanceOf(NavigationGroup::class, $group);
            $this->assertTrue($group->isCollapsible(), "Navigation group [{$group->getLabel()}] should be collapsible.");
            $this->assertTrue($group->isCollapsed(), "Navigation group [{$group->getLabel()}] should be collapsed by default.");
            $this->assertNotEmpty($group->getIcon(), "Navigation group [{$group->getLabel()}] should have an icon for collapsed dropdown mode.");
        }
    }

    public function test_sidebar_accordion_script_is_present_in_custom_styles_view(): void
    {
        $viewContent = view('filament.custom-styles')->render();

        $this->assertStringContainsString('setupSidebarAccordion', $viewContent);
        $this->assertStringContainsString('toggleCollapsedGroup', $viewContent);
        $this->assertStringContainsString('livewire:navigated', $viewContent);
    }

    public function test_dashboard_renders_successfully_for_authenticated_admin(): void
    {
        Livewire::actingAs($this->user)
            ->test(CustomDashboard::class)
            ->assertStatus(200);
    }
}