<?php namespace App\Providers;

use DB;
use MongoDB\BSON\ObjectID;
use Validator;
use Illuminate\Support\ServiceProvider;
use App\Services\Validation\MessageValidator;

class AppServiceProvider extends ServiceProvider
{

    protected $developmentServiceProviders
        = [
            \Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
        ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCustomValidation();

        if ($this->app->environment('local')) {
            $this->registerLocalServiceProviders();
        }

    }

    private function registerCustomValidation()
    {
        Validator::extendImplicit('message', function ($attribute, $value, $parameters, $validator) {
            return MessageValidator::FromInstance($validator)->validateMessage($attribute, $value, $parameters);
        });

        Validator::extend('ci_unique', function ($attribute, $value, $parameters, $validator) {
            if (starts_with($parameters[5], 'oi:')) {
                $parameters[5] = new ObjectID(substr($parameters[5], 3));
            }
            $count = DB::collection($parameters[0])
                ->where($parameters[1], 'regexp', "/^{$value}$/i")
                ->where($parameters[2], '!=', $parameters[3])
                ->where($parameters[4], $parameters[5])->count();

            return ! $count;
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
