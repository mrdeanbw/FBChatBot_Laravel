<?php namespace App\Providers;

use Validator;
use Illuminate\Support\ServiceProvider;
use App\Services\Validation\MessageValidator;

class AppServiceProvider extends ServiceProvider
{

    protected $developmentServiceProviders = [
        \Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerMessageBlockValidation();

        if ($this->app->environment('local')) {
            $this->registerLocalServiceProviders();
        }

    }

    private function registerMessageBlockValidation()
    {
        Validator::extendImplicit('message', function ($attribute, $value, $parameters, $validator) {
            return MessageValidator::FromInstance($validator)->validateMessage($attribute, $value, $parameters);
        });
    }

    private function registerLocalServiceProviders()
    {
        foreach ($this->developmentServiceProviders as $serviceProvider) {
            if (class_exists($serviceProvider)) {
                $this->app->register($serviceProvider);
            }
        }
    }
}
