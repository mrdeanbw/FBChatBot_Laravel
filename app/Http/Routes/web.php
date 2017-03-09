<?php

use Laravel\Lumen\Application;

$app->group(['namespace' => 'App\Http\Controllers'], function (Application $app) {

    $app->group(['prefix' => 'callback'], function (Application $app) {
        $app->get('facebook/web-hook', 'FacebookWebhookController@verify');
        $app->post('facebook/web-hook', ['uses' => 'FacebookWebhookController@handle', 'middleware' => 'fb.webhook.verify']);
        $app->get('facebook/de-authorize', 'FacebookWebhookController@deauthorize');
        $app->post('stripe/web-hook', '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook');
    });

    $app->get('/mb/{botId}/{buttonId}/{revisionId}', 'ClickHandlingController@mainMenuButton');
    $app->get('/ba/{payload}', 'ClickHandlingController@handle');
});