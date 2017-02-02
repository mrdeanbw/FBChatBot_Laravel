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

        $this->registerJWTApiRateLimiter();

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

    private function registerJWTApiRateLimiter ()
    {
        app('Dingo\Api\Http\RateLimit\Handler')->setRateLimiter(function ($app, $request) {
            $jwt =  $app['tymon.jwt.auth']->getToken();
            if(!$jwt) {
                //fallback to IP if for somereason jwt token couldn't be retrive
                return app('request')->ip();
            }
            return $jwt;
        });
    }
}
