<?php

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

        $api->get('/test', function () {
            return [];
        });

        $api->group(['middleware' => Admin\Http\Middlewares\AdminAuthMiddleware::class], function (Router $api) {

            $api->group(['prefix' => 'monitor'], function (Router $api) {
                $api->get('/servers', 'Monitor\ServerController@index');

                $api->get('/logs', 'Monitor\LogController@index');
                $api->get('/logs/{id}', 'Monitor\LogController@show');
                $api->delete('/logs/{id}', 'Monitor\LogController@destroy');
                $api->get('/logs/{id}/download', 'Monitor\LogController@download');

                $api->get('/databases/{id}', 'Monitor\Database\DatabaseInfoController@show');
                $api->get('/databases/{id}/collections', 'Monitor\Database\CollectionController@index');
                $api->get('/databases/{id}/queries/slow', 'Monitor\Database\QueryController@slow');
                $api->get('/databases/{id}/queries/latest', 'Monitor\Database\QueryController@latest');
            });

        });

    });
});
