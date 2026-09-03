<?php

namespace Tests\Unit;

use App\Filament\Livewire\CustomDatabaseNotifications;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

class CustomDatabaseNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email'        => 'test_notif@jb.com',
            'name'         => 'Test Notification User',
            'organization' => 'Janata Bank',
            'mobile_no'    => '01712345678',
        ]);
        Auth::login($this->user);
    }

    public function test_tabs_and_category_filtering_work_correctly(): void
    {
        // 1. Dispatch Checker notification
        $nChecker = Notification::make()
            ->title('New Settlement File for Checker')
            ->body('Pending verification')
            ->viewData(['category' => 'checker']);
        $this->user->notifyNow($nChecker->toDatabase());

        // 2. Dispatch Authorizer 1 notification
        $nAuth1 = Notification::make()
            ->title('1st Authorization Required')
            ->body('Checker approved')
            ->viewData(['category' => 'authorizer_1']);
        $this->user->notifyNow($nAuth1->toDatabase());

        // 3. Dispatch Authorizer 2 notification
        $nAuth2 = Notification::make()
            ->title('2nd Confirmation Settle')
            ->body('1st auth approved')
            ->viewData(['category' => 'authorizer_2']);
        $this->user->notifyNow($nAuth2->toDatabase());

        $component = Livewire::test(CustomDatabaseNotifications::class);

        $counts = $component->instance()->getTabCounts();
        $this->assertEquals(3, $counts['all']);
        $this->assertEquals(1, $counts['checker']);
        $this->assertEquals(1, $counts['auth1']);
        $this->assertEquals(1, $counts['auth2']);

        // Check query results on active tab
        $component->call('setTab', 'checker');
        $this->assertEquals('checker', $component->get('activeTab'));
        $this->assertCount(1, $component->instance()->getNotifications());

        $component->call('setTab', 'auth1');
        $this->assertEquals('auth1', $component->get('activeTab'));
        $this->assertCount(1, $component->instance()->getNotifications());

        $component->call('setTab', 'auth2');
        $this->assertEquals('auth2', $component->get('activeTab'));
        $this->assertCount(1, $component->instance()->getNotifications());

        $component->call('setTab', 'all');
        $this->assertCount(3, $component->instance()->getNotifications());
    }

    public function test_mark_all_as_read_updates_unread_count(): void
    {
        $n = Notification::make()
            ->title('Unread Notification')
            ->body('Body test')
            ->viewData(['category' => 'checker']);
        $this->user->notifyNow($n->toDatabase());

        $component = Livewire::test(CustomDatabaseNotifications::class);
        $this->assertEquals(1, $component->instance()->getUnreadNotificationsCount());

        $component->call('markAllNotificationsAsRead');
        $this->assertEquals(0, $component->instance()->getUnreadNotificationsCount());
    }
}