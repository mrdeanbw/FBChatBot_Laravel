<?php namespace App\Providers;

use Pusher;
use Illuminate\Support\ServiceProvider;

class PusherServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('pusher', function () {
            $config = config('services.pusher');

            return new Pusher(
                $config['app_key'],
                $config['app_secret'],
                $config['app_id']
            );
        });
    }
}
