<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Validation\MessageBlockValidator;
use Validator;

class AppServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Validator::extendImplicit('message_block', function ($attribute, $value, $parameters, $validator) {
            return MessageBlockValidator::FromInstance($validator)->validateMessageBlock($attribute, $value, $parameters);
        });
    }
}
