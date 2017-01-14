<?php

namespace App\Providers;

use App\Models;
use App\Transformers;
use Dingo\Api\Transformer\Factory;
use Illuminate\Support\ServiceProvider;

class RegisterAPITransformersProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /** @type Factory $factory */
        $factory = app(Factory::class);
        $factory->register(Models\PaymentPlan::class, Transformers\PaymentPlanTransformer::class);
        $factory->register(Models\Page::class, Transformers\PageTransformer::class);
    }
}
