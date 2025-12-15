<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Http\Livewire\ContactsManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Explicitly register Livewire components to ensure discovery.
        if (class_exists(Livewire::class)) {
            Livewire::component('contacts-manager', ContactsManager::class);
        }
    }
}
