<?php

$app->group(['prefix' => 'callback'], function () use ($app) {
    $app->get('facebook/web-hook', 'FacebookWebhookController@verify');
    $app->post('facebook/web-hook', ['uses' => 'FacebookWebhookController@handle', 'middleware' => 'fb.webhook.verify']);
    $app->get('facebook/de-authorize', 'FacebookWebhookController@deauthorize');
    $app->post('stripe/web-hook', '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook');
});

$app->get('/mb/{botId}/{buttonId}', 'ClickHandlingController@mainMenuButton');
$app->get('/ba/{messageBlockHash}/{subscriberHash}', 'ClickHandlingController@handle');

