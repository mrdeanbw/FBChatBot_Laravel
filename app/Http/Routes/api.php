<?php

use Common\Models\Bot;
use Common\Models\SentMessage;
use Common\Models\Subscriber;
use Common\Models\User;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Repositories\SentMessage\DBSentMessageRepository;
use Common\Services\FacebookMessageSender;
use Dingo\Api\Routing\Router;
use Common\Jobs\UpdateMainMenuOnFacebook;
use Common\Http\Middleware\AuthorizedMiddleware;
use MongoDB\BSON\ObjectID;

/** @type Router $api */
$api = app(Router::class);

$options = [
    'middleware' => [
        Common\Http\Middleware\CorsMiddleware::class,
        Common\Http\Middleware\RedirectCrawlers::class,
        'api.throttle'
    ],
    'limit'      => config('api.throttle.limit'),
    'expires'    => config('api.throttle.expires'),
    'namespace'  => 'App\Http\Controllers\API'
];

$api->version('v1', $options, function (Router $api) {

    $api->get('/test', function (DBSentMessageRepository $repo) {
    });

    $api->get('/subscription-plans', 'PaymentPlanController@index');

    $api->post('/users/login', 'UserController@login');
    $api->post('/users/refresh-token', 'UserController@refreshToken');

    $api->group(['middleware' => ['api.auth', AuthorizedMiddleware::class]], function (Router $api) {

        $api->get('/users/current', 'UserController@current');

        // Page
        $api->get('/pages', 'PageController@index');

        // Bot
        // Enabled Bots
        $api->post('/bots/enabled', 'BotController@store');
        $api->get('/bots/enabled/count', 'BotController@countEnabled');
        $api->get('/bots/enabled/{id}', 'BotController@show');
        $api->patch('/bots/enabled/{id}', 'BotController@update');
        $api->get('/bots/enabled', 'BotController@enabledBots');
        $api->post('/bots/enabled/{id}/disable', 'BotController@disable');
        // Disabled Bots
        $api->get('/bots/disabled', 'BotController@disabledBots');
        $api->get('/bots/disabled/count', 'BotController@countDisabled');
        $api->post('/bots/disabled/{id}/enable', 'BotController@enable');

        // Tags
        $api->post('/bots/enabled/{id}/tags', 'BotController@createTag');

        // Greeting Text.
        $api->put('/bots/enabled/{botId}/greeting-text', 'GreetingTextController@update');

        // Default Reply
        $api->put('/bots/enabled/{botId}/default-reply', 'DefaultReplyController@update');

        // Welcome Message
        $api->put('/bots/enabled/{botId}/welcome-message/', 'WelcomeMessageController@update');

        // Main Menu
        $api->put('/bots/enabled/{botId}/main-menu', 'MainMenuController@update');

        // Auto Reply Rules
        $api->get('/bots/enabled/{botId}/auto-reply/rules', 'AutoReplyRuleController@index');
        $api->post('/bots/enabled/{botId}/auto-reply/rules', 'AutoReplyRuleController@create');
        $api->put('/bots/enabled/{botId}/auto-reply/rules/{id}', 'AutoReplyRuleController@update');
        $api->delete('/bots/enabled/{botId}/auto-reply/rules/{id}', 'AutoReplyRuleController@destroy');

        // Message Trees
        $api->get('/bots/enabled/{botId}/templates/explicit', 'TemplateController@index');
        $api->post('/bots/enabled/{botId}/templates/explicit', 'TemplateController@store');
        $api->get('/bots/enabled/{botId}/templates/explicit/{id}', 'TemplateController@show');
        $api->put('/bots/enabled/{botId}/templates/explicit/{id}', 'TemplateController@update');

        // Message Previews
        $api->post('/bots/enabled/{botId}/message-previews', 'MessagePreviewController@store');

        // Subscribers
        $api->get('/bots/enabled/{botId}/subscribers', 'SubscriberController@index');
        $api->get('/bots/enabled/{botId}/subscribers/count', 'SubscriberController@count');
        $api->get('/bots/enabled/{botId}/subscribers/{id}', 'SubscriberController@show');
        $api->patch('/bots/enabled/{botId}/subscribers/{id}', 'SubscriberController@update');
        $api->patch('/bots/enabled/{botId}/subscribers', 'SubscriberController@batchUpdate');

        //        // Sequences
        //        $api->get('/bots/enabled/{botId}/sequences', 'SequenceController@index');
        //        $api->get('/bots/enabled/{botId}/sequences/{id}', 'SequenceController@show');
        //        $api->post('/bots/enabled/{botId}/sequences', 'SequenceController@store');
        //        $api->delete('/bots/enabled/{botId}/sequences/{id}', 'SequenceController@destroy');
        //        $api->put('/bots/enabled/{botId}/sequences/{id}', 'SequenceController@update');
        //
        //        // Sequence Messages
        //        $api->get('/bots/enabled/{botId}/sequences/{sequenceId}/messages/{id}', 'SequenceMessageController@show');
        //        $api->post('/bots/enabled/{botId}/sequences/{sequenceId}/messages', 'SequenceMessageController@store');
        //        $api->put('/bots/enabled/{botId}/sequences/{sequenceId}/messages/{id}', 'SequenceMessageController@update');
        //        $api->put('/bots/enabled/{botId}/sequences/{sequenceId}/messages/{id}/conditions', 'SequenceMessageController@updateConditions');
        //        $api->delete('/bots/enabled/{botId}/sequences/{sequenceId}/messages/{id}', 'SequenceMessageController@destroy');

        // Broadcasts
        $api->get('/bots/enabled/{botId}/broadcasts/pending', 'BroadcastController@pending');
        $api->get('/bots/enabled/{botId}/broadcasts/non-pending', 'BroadcastController@nonPending');
        $api->post('/bots/enabled/{botId}/broadcasts', 'BroadcastController@store');
        $api->get('/bots/enabled/{botId}/broadcasts/{id}', 'BroadcastController@show');
        $api->put('/bots/enabled/{botId}/broadcasts/{id}', 'BroadcastController@update');
        $api->delete('/bots/enabled/{botId}/broadcasts/{id}', 'BroadcastController@destroy');

        // Stats & Metrics
        $api->get('/bots/enabled/{botId}/stats', 'StatsController@index');

        // Message Revisions
        $api->get('/bots/enabled/{botId}/messages/{messageId}/revisions', 'MessageRevisionController@index');
        $api->get('/bots/enabled/{botId}/main-menu-buttons/{buttonId}/revisions', 'MessageRevisionController@mainMenuButton');
    });

});
