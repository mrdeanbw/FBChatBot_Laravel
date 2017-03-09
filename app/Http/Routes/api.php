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
    'namespace'  => 'App\Http\Controllers\API'
];

$api->version('v1', $options, function (Router $api) {

    $api->get('/test', function () {
    });

    $api->get('/subscription-plans', 'PaymentPlanController@index');

    $api->post('/users/login', 'UserController@login');
    $api->post('/users/refresh-token', 'UserController@refreshToken');

    $api->group(['middleware' => 'api.auth'], function (Router $api) {

        $api->get('/users/current', 'UserController@current');

        // Page
        $api->get('/pages', 'PageController@index');

        // Bot
        $api->get('/bots', 'BotController@index');
        $api->post('/bots', 'BotController@store');
        $api->get('/bots/{id}', 'BotController@show');
        $api->patch('/bots/{id}', 'BotController@update');
        $api->post('/bots/{id}/enable', 'BotController@enable');
        $api->delete('/bots/{id}/enable', 'BotController@disable');

        // Greeting Text.
        $api->put('/bots/{botId}/greeting-text', 'GreetingTextController@update');

        // Default Reply
        $api->put('/bots/{botId}/default-reply', 'DefaultReplyController@update');

        // Welcome Message
        $api->put('/bots/{botId}/welcome-message/', 'WelcomeMessageController@update');

        // Main Menu
        $api->put('/bots/{botId}/main-menu', 'MainMenuController@update');

        // Auto Reply Rules
        $api->get('/bots/{botId}/auto-reply/rules', 'AutoReplyRuleController@index');
        $api->post('/bots/{botId}/auto-reply/rules', 'AutoReplyRuleController@create');
        $api->put('/bots/{botId}/auto-reply/rules/{id}', 'AutoReplyRuleController@update');
        $api->delete('/bots/{botId}/auto-reply/rules/{id}', 'AutoReplyRuleController@destroy');

        // Message Trees
        $api->get('/bots/{botId}/templates/explicit', 'TemplateController@index');
        $api->post('/bots/{botId}/templates/explicit', 'TemplateController@store');
        $api->get('/bots/{botId}/templates/explicit/{id}', 'TemplateController@show');
        $api->put('/bots/{botId}/templates/explicit/{id}', 'TemplateController@update');

        // Message Previews
        $api->post('/bots/{botId}/message-previews', 'MessagePreviewController@store');

        // Subscribers
        $api->get('/bots/{botId}/subscribers', 'SubscriberController@index');
        $api->get('/bots/{botId}/subscribers/{id}', 'SubscriberController@show');
        $api->patch('/bots/{botId}/subscribers/{id}', 'SubscriberController@update');
        $api->patch('/bots/{botId}/subscribers', 'SubscriberController@batchUpdate');

        // Sequences
        $api->get('/bots/{botId}/sequences', 'SequenceController@index');
        $api->get('/bots/{botId}/sequences/{id}', 'SequenceController@show');
        $api->post('/bots/{botId}/sequences', 'SequenceController@store');
        $api->delete('/bots/{botId}/sequences/{id}', 'SequenceController@destroy');
        $api->put('/bots/{botId}/sequences/{id}', 'SequenceController@update');

        // Sequence Messages
        $api->get('/bots/{botId}/sequences/{sequenceId}/messages/{id}', 'SequenceMessageController@show');
        $api->post('/bots/{botId}/sequences/{sequenceId}/messages', 'SequenceMessageController@store');
        $api->put('/bots/{botId}/sequences/{sequenceId}/messages/{id}', 'SequenceMessageController@update');
        $api->put('/bots/{botId}/sequences/{sequenceId}/messages/{id}/conditions', 'SequenceMessageController@updateConditions');
        $api->delete('/bots/{botId}/sequences/{sequenceId}/messages/{id}', 'SequenceMessageController@destroy');

        // Broadcasts
        $api->get('/bots/{botId}/broadcasts', 'BroadcastController@index');
        $api->post('/bots/{botId}/broadcasts', 'BroadcastController@store');
        $api->get('/bots/{botId}/broadcasts/{id}', 'BroadcastController@show');
        $api->put('/bots/{botId}/broadcasts/{id}', 'BroadcastController@update');
        $api->delete('/bots/{botId}/broadcasts/{id}', 'BroadcastController@destroy');

        // Stats & Metrics
        $api->get('/bots/{botId}/stats', 'StatsController@index');

        // Message Revisions
        $api->get('/bots/{botId}/messages/{messageId}/revisions', 'MessageRevisionController@index');
    });

});
