<?php
namespace App\Providers;
use App\Services\CustomFractalSerializer;
use Dingo\Api\Transformer\Adapter\Fractal;
use Illuminate\Support\ServiceProvider;
use League\Fractal\Manager;

class FractalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('League\Fractal\Manager', function() {
            $fractal = new Manager;

            $serializer = new CustomFractalSerializer();

            $fractal->setSerializer($serializer);
            
            return $fractal;
        });

        $this->app->bind('Dingo\Api\Transformer\Adapter\Fractal', function($app) {
            $fractal = $app->make('\League\Fractal\Manager');

            return new Fractal($fractal);
        });
    }
}
