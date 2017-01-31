<?php

use Laravel\Lumen\Application;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__ . '/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(realpath(__DIR__ . '/../'));

$app->withFacades(true, [
    'Illuminate\Support\Facades\Redirect' => 'Redirect'
]);
$app->bind('redirect', 'Laravel\Lumen\Http\Redirector');

$app->register(Jenssegers\Mongodb\MongodbServiceProvider::class);
$app->register(Jenssegers\Mongodb\MongodbQueueServiceProvider::class);
$app->withEloquent();

$app->configure('app');
$app->configure('jwt');
$app->configure('queue');
$app->configure('services');

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(Illuminate\Contracts\Debug\ExceptionHandler::class, App\Exceptions\Handler::class);

$app->singleton(Illuminate\Contracts\Console\Kernel::class, App\Console\Kernel::class);

$app->singleton(Illuminate\Contracts\Routing\ResponseFactory::class, Illuminate\Routing\ResponseFactory::class);

$app->singleton(Illuminate\Auth\AuthManager::class, function (Application $app) {
    return $app->make('auth');
});

$app->singleton(Illuminate\Cache\CacheManager::class, function (Application $app) {
    return $app->make('cache');
});

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

// $app->middleware([
//    App\Http\Middleware\ExampleMiddleware::class
// ]);

$app->routeMiddleware([
    'fb.webhook.verify' => App\Http\Middleware\FacebookWebhookMiddleware::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

$app->register(App\Providers\FractalServiceProvider::class);

$app->register(Dingo\Api\Provider\LumenServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
$app->register(Laravel\Cashier\CashierServiceProvider::class);

$app->register(App\Providers\AppServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(App\Providers\RegisterAPITransformersProvider::class);
$app->register(App\Providers\CatchAllOptionsRequestsProvider::class);
$app->register(App\Providers\RepositoryServiceProvider::class);
$app->register(App\Providers\PusherServiceProvider::class);
$app->register(Rap2hpoutre\LaravelLogViewer\LaravelLogViewerServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);

$app->make(Dingo\Api\Auth\Auth::class)->extend('jwt', function (Application $app) {
    return new Dingo\Api\Auth\Provider\JWT($app->make(Tymon\JWTAuth\JWTAuth::class));
});

app('Dingo\Api\Transformer\Factory')->setAdapter(function ($app) {
    return new Dingo\Api\Transformer\Adapter\Fractal(
        app(League\Fractal\Manager::class), 'include', ',', false
    );
});


use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;

$app->configureMonologUsing(function ($monolog) {
    $maxFiles = 7;

    $rotatingLogHandler = (new RotatingFileHandler(storage_path('logs/lumen.log'), $maxFiles))->setFormatter(new LineFormatter(null, null, true, true));

    $monolog->setHandlers([$rotatingLogHandler]);

    return $monolog;
});

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/
$app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
    require __DIR__ . '/../app/Http/routes.php';
});


$app->group(['namespace' => '\Rap2hpoutre\LaravelLogViewer'], function () use ($app) {
    $app->get('logs', 'LogViewerController@index');
});


return $app;
