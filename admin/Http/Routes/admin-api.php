<?php

use Admin\Repositories\MongoDatabase\MongoDatabaseRepositoryInterface;
use Dingo\Api\Routing\Router;

/** @type Router $api */
$api = app(Router::class);

$options = [
    'middleware' => [
        Common\Http\Middleware\CorsMiddleware::class,
        'api.throttle'
    ],
    'limit'      => config('api.throttle.limit'),
    'expires'    => config('api.throttle.expires'),
    'namespace'  => 'Admin\Http\Controllers\API',
];

$api->version('v1', $options, function (Router $api) {

    $api->group(['prefix' => 'admin'], function (Router $api) {

        $api->get('/test', function (MongoDatabaseRepositoryInterface $repo) {
        });

        $api->group(['middleware' => Admin\Http\Middlewares\AdminAuthMiddleware::class], function (Router $api) {
            $api->group(['prefix' => 'monitor'], function (Router $api) {
                $api->get('/', 'Monitor\MonitorController@index');
                $api->get('/database', 'Monitor\DatabaseMonitorController@index');
                $api->get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
            });
        });

    });
});
