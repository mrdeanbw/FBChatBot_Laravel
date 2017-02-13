<?php

namespace Modules\Monitor;

use Illuminate\Support\ServiceProvider;

class MonitorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/views', 'modules-monitor');
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        
    }
}