<?php

namespace App\Providers;

use App\Models\Link;
use App\Models\Admin;
use App\Observers\LinkObserver;
use App\Observers\AdminObserver;
use Illuminate\Support\ServiceProvider;

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
        Link::observe(LinkObserver::class);
        Admin::observe(AdminObserver::class);
    }
}
