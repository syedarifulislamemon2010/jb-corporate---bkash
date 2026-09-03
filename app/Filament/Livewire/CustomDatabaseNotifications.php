<?php

namespace App\Filament\Livewire;

use Filament\Enums\DatabaseNotificationsPosition;
use Filament\Facades\Filament;
use Filament\Notifications\Livewire\DatabaseNotifications as BaseComponent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Locked;

class CustomDatabaseNotifications extends BaseComponent
{
    #[Locked]
    public ?DatabaseNotificationsPosition $position = null;

    public string $activeTab = 'all';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage('database-notifications-page');
    }

    public function getUser(): Model | Authenticatable | null
    {
        return Filament::auth()->user();
    }

    public function getPollingInterval(): ?string
    {
        return Filament::getDatabaseNotificationsPollingInterval();
    }

    public function getTrigger(): ?View
    {
        return (($this->position ?? filament()->getDatabaseNotificationsPosition()) === DatabaseNotificationsPosition::Topbar)
            ? view('filament-panels::components.topbar.database-notifications-trigger')
            : view('filament-panels::components.sidebar.database-notifications-trigger');
    }

    public function getNotificationsQuery(): Builder | Relation
    {
        $query = parent::getNotificationsQuery();

        if ($this->activeTab === 'checker') {
            $query->where(function ($q) {
                $q->where('data->viewData->category', 'checker')
                  ->orWhere('data->category', 'checker')
                  ->orWhere(function ($sub) {
                      $sub->whereNull('data->viewData->category')
                          ->whereNull('data->category')
                          ->where(function ($k) {
                              $k->where('data->title', 'like', '%Checker%')
                                ->orWhere('data->body', 'like', '%pending for Checker%')
                                ->orWhere('data->title', 'like', '%Settlement File%');
                          });
                  });
            });
        } elseif ($this->activeTab === 'auth1') {
            $query->where(function ($q) {
                $q->where('data->viewData->category', 'authorizer_1')
                  ->orWhere('data->category', 'authorizer_1')
                  ->orWhere(function ($sub) {
                      $sub->whereNull('data->viewData->category')
                          ->whereNull('data->category')
                          ->where(function ($k) {
                              $k->where('data->title', 'like', '%1st Authoriz%')
                                ->orWhere('data->body', 'like', '%1st Authorizer%');
                          });
                  });
            });
        } elseif ($this->activeTab === 'auth2') {
            $query->where(function ($q) {
                $q->where('data->viewData->category', 'authorizer_2')
                  ->orWhere('data->category', 'authorizer_2')
                  ->orWhere(function ($sub) {
                      $sub->whereNull('data->viewData->category')
                          ->whereNull('data->category')
                          ->where(function ($k) {
                              $k->where('data->title', 'like', '%2nd%')
                                ->orWhere('data->title', 'like', '%Confirmation%')
                                ->orWhere('data->title', 'like', '%Settlement%')
                                ->orWhere('data->body', 'like', '%2nd Authorizer%');
                          });
                  });
            });
        }

        return $query;
    }

    public function getTabCounts(): array
    {
        $user = $this->getUser();
        if (!$user) {
            return ['all' => 0, 'checker' => 0, 'auth1' => 0, 'auth2' => 0];
        }

        $base = $user->notifications()->where('data->format', 'filament');

        $all = (clone $base)->count();
        $checker = (clone $base)->where(function ($q) {
            $q->where('data->viewData->category', 'checker')
              ->orWhere('data->category', 'checker')
              ->orWhere(function ($sub) {
                  $sub->whereNull('data->viewData->category')
                      ->whereNull('data->category')
                      ->where(function ($k) {
                          $k->where('data->title', 'like', '%Checker%')
                            ->orWhere('data->body', 'like', '%pending for Checker%')
                            ->orWhere('data->title', 'like', '%Settlement File%');
                      });
              });
        })->count();

        $auth1 = (clone $base)->where(function ($q) {
            $q->where('data->viewData->category', 'authorizer_1')
              ->orWhere('data->category', 'authorizer_1')
              ->orWhere(function ($sub) {
                  $sub->whereNull('data->viewData->category')
                      ->whereNull('data->category')
                      ->where(function ($k) {
                          $k->where('data->title', 'like', '%1st Authoriz%')
                            ->orWhere('data->body', 'like', '%1st Authorizer%');
                      });
              });
        })->count();

        $auth2 = (clone $base)->where(function ($q) {
            $q->where('data->viewData->category', 'authorizer_2')
              ->orWhere('data->category', 'authorizer_2')
              ->orWhere(function ($sub) {
                  $sub->whereNull('data->viewData->category')
                      ->whereNull('data->category')
                      ->where(function ($k) {
                          $k->where('data->title', 'like', '%2nd%')
                            ->orWhere('data->title', 'like', '%Confirmation%')
                            ->orWhere('data->title', 'like', '%Settlement%')
                            ->orWhere('data->body', 'like', '%2nd Authorizer%');
                      });
              });
        })->count();

        return [
            'all'     => $all,
            'checker' => $checker,
            'auth1'   => $auth1,
            'auth2'   => $auth2,
        ];
    }

    public function render(): View
    {
        return view('filament.components.database-notifications', [
            'tabCounts' => $this->getTabCounts(),
        ]);
    }
}