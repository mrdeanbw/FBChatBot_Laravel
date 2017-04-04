<?php

use Laravel\Lumen\Application;

$app->group(['namespace' => 'App\Http\Controllers'], function (Application $app) {

    $app->group(['prefix' => 'callback'], function (Application $app) {
        $app->get('facebook/web-hook', 'FacebookWebhookController@verify');
        $app->post('facebook/web-hook', ['uses' => 'FacebookWebhookController@handle', 'middleware' => 'fb.webhook.verify']);
        $app->post('facebook/de-authorize', 'FacebookWebhookController@deauthorize');
        $app->post('stripe/web-hook', '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook');
    });

    $app->group(['middleware' => [Common\Http\Middleware\RedirectCrawlers::class]], function (Application $app) {
        $app->get('/c/c/{payload}', 'ClickHandlingController@card');
        $app->get('/c/tb/{payload}', 'ClickHandlingController@textButton');
        $app->get('/c/cb/{payload}', 'ClickHandlingController@cardButton');
        $app->get('/c/mb/{payload}', 'ClickHandlingController@menuButton');
    });
});
