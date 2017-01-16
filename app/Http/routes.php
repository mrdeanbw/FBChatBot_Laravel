<?php

use Dingo\Api\Routing\Router;

/** @type Router $api */
$api = app(Router::class);

$options = [
    'middleware' => [
        App\Http\Middleware\CorsMiddleware::class
    ],
    'namespace'  => 'App\Http\Controllers\API'
];

$api->version('v1', $options, function (Router $api) {

    $api->get('/test', function () {
    });

    $api->get('/subscription-plans', 'PaymentPlanController@index');
    $api->get('/auth/token', 'AuthController@refresh');
    $api->post('/auth', 'AuthController@login');

    $api->group(['middleware' => ['api.auth']], function (Router $api) {

        // Pages
        $api->get('/pages', 'PageController@index');
        $api->post('/pages', 'PageController@store');
        $api->get('/pages/{id}', 'PageController@show');
        $api->get('/pages/{id}/user-status', 'PageController@userStatus');
        $api->patch('/pages/{id}', 'PageController@update');
        $api->delete('/pages/{id}', 'PageController@disableBot');
        $api->post('/pages/{id}/subscription', 'PageController@subscribe');

        // Invoices
        $api->get('/pages/{pageId}/invoices', 'InvoiceController@index');
        $api->get('/pages/{pageId}/invoices/{id}', 'InvoiceController@show');

        // Tags
        $api->get('/pages/{pageId}/tags', 'TagController@index');

        // Message Trees
        $api->get('/pages/{pageId}/build/trees', 'Build\TreeController@index');
        $api->post('/pages/{pageId}/build/trees', 'Build\TreeController@store');
        $api->get('/pages/{pageId}/build/trees/{id}', 'Build\TreeController@show');
        $api->put('/pages/{pageId}/build/trees/{id}', 'Build\TreeController@update');

        // Main Menu
        $api->get('/pages/{pageId}/build/main-menu/{id}', 'Build\MainMenuController@show');
        $api->put('/pages/{pageId}/build/main-menu/{id}', 'Build\MainMenuController@update');

        // Greeting Text.
        $api->get('/pages/{pageId}/build/greeting-text/{id}', 'Build\GreetingTextController@show');
        $api->put('/pages/{pageId}/build/greeting-text/{id}', 'Build\GreetingTextController@update');

        // Welcome Message
        $api->get('/pages/{pageId}/build/welcome-message/{id}', 'Build\WelcomeMessageController@show');
        $api->put('/pages/{pageId}/build/welcome-message/{id}', 'Build\WelcomeMessageController@update');

        // Default Reply
        $api->get('/pages/{pageId}/build/default-reply/{id}', 'Build\DefaultReplyController@show');
        $api->put('/pages/{pageId}/build/default-reply/{id}', 'Build\DefaultReplyController@update');

        // Auto Reply Rules
        $api->get('/pages/{pageId}/build/ai-response/rules', 'Build\AutoReplyController@rules');
        $api->post('/pages/{pageId}/build/ai-response/rules', 'Build\AutoReplyController@createRule');
        $api->put('/pages/{pageId}/build/ai-response/rules/{id}', 'Build\AutoReplyController@updateRule');
        $api->delete('/pages/{pageId}/build/ai-response/rules/{id}', 'Build\AutoReplyController@deleteRule');

        // Message Previews
        $api->post('/pages/{pageId}/message-previews', 'MessagePreviewController@store');

        // Sequences
        $api->get('/pages/{pageId}/sequences', 'SequenceController@index');
        $api->get('/pages/{pageId}/sequences/{id}', 'SequenceController@show');
        $api->post('/pages/{pageId}/sequences', 'SequenceController@store');
        $api->delete('/pages/{pageId}/sequences/{id}', 'SequenceController@destroy');
        $api->put('/pages/{pageId}/sequences/{id}', 'SequenceController@update');
        // Sequence Messages
        $api->post('/pages/{pageId}/sequences/{sequenceId}/messages', 'SequenceMessageController@store');
        $api->put('/pages/{pageId}/sequences/{sequenceId}/messages/{id}', 'SequenceMessageController@update');
        $api->delete('/pages/{pageId}/sequences/{sequenceId}/messages/{id}', 'SequenceMessageController@destroy');

        // Broadcasts
        $api->get('/pages/{pageId}/broadcasts', 'BroadcastController@index');
        $api->post('/pages/{pageId}/broadcasts', 'BroadcastController@store');
        $api->get('/pages/{pageId}/broadcasts/{id}', 'BroadcastController@show');
        $api->put('/pages/{pageId}/broadcasts/{id}', 'BroadcastController@update');
        $api->delete('/pages/{pageId}/broadcasts/{id}', 'BroadcastController@destroy');

        // Widgets
        //        $api->get('/pages/{pageId}/widgets', 'WidgetController@index');
        //        $api->post('/pages/{pageId}/widgets', 'WidgetController@store');
        //        $api->get('/pages/{pageId}/widgets/{id}', 'WidgetController@show');
        //        $api->put('/pages/{pageId}/widgets/{id}', 'WidgetController@update');
        //        $api->delete('/pages/{pageId}/widgets/{id}', 'WidgetController@destroy');

        // Subscribers
        $api->get('/pages/{pageId}/subscribers', 'SubscriberController@index');
        $api->get('/pages/{pageId}/subscribers/{id}', 'SubscriberController@show');
        $api->put('/pages/{pageId}/subscribers/{id}', 'SubscriberController@update');
        $api->post('/pages/{pageId}/subscribers', 'SubscriberController@batchUpdate');

        // Stats & Metrics
        $api->get('/pages/{pageId}/stats', 'StatsController@index');
    });

});


$app->group(['prefix' => 'callback'], function () use ($app) {
    $app->get('facebook/web-hook', 'FacebookWebhookController@verify');
    $app->post('facebook/web-hook', 'FacebookWebhookController@handle');
    $app->get('facebook/de-authorize', 'FacebookWebhookController@deauthorize');
    $app->post('stripe/web-hook', '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook');
});

$app->get('/ba/{messageBlockHash}/{subscriberHash}', 'ButtonClickController@handle');
