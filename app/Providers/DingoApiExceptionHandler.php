<?php namespace App\Providers;

use Dingo\Api\Http\Response;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DingoApiExceptionHandler extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        app('Dingo\Api\Exception\Handler')->register(function (ModelNotFoundException $exception) {
            return new Response(['error' => $exception->getMessage()], 404);
        });
    }
}
