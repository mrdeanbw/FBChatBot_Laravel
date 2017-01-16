<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Validation\MessageBlockValidator;
use Validator;

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
        Validator::extendImplicit('message_block', function ($attribute, $value, $parameters, $validator) {
            return MessageBlockValidator::FromInstance($validator)->validateMessageBlock($attribute, $value, $parameters);
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
