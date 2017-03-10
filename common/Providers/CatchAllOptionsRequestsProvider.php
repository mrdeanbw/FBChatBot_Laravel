<?php namespace Common\Providers;

use Illuminate\Support\ServiceProvider;

class CatchAllOptionsRequestsProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $request = app('request');

        if ($request->isMethod('OPTIONS')) {
            app()->options($request->path(), function () use ($request) {
                $response = response('', 200);
                if (method_exists($response, 'header')) {
                    $response->header('Access-Control-Allow-Methods', 'HEAD, GET, POST, PUT, PATCH, DELETE');
                    $response->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers'));
                    $response->header('Access-Control-Allow-Origin', '*');
                }

                return $response;
            });
        }
    }
}
