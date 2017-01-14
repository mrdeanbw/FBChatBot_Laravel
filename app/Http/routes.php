<?php

use App\Models\User;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Router;
use App\Services\PageService;
use App\Services\Facebook\Makana\Receiver;
use App\Services\Facebook\Makana\AppVerifier;
use App\Services\Facebook\Makana\WebAppAdapter;

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
    $api->get('/auth/token', 'AuthController@index');
    $api->post('/auth', 'AuthController@store');

    $api->group(['middleware' => ['api.auth']], function (Router $api) {

        $api->get('/pages', 'PageController@index');
        $api->post('/pages', 'PageController@store');
        $api->get('/pages/{id}', 'PageController@show');
        $api->get('/pages/{id}/user-status', 'PageController@userStatus');
        $api->patch('/pages/{id}', 'PageController@update');
        $api->delete('/pages/{id}', 'PageController@disableBot');
        $api->post('/pages/{id}/subscription', 'PageController@subscribe');

        $api->get('/pages/{pageId}/invoices', 'InvoiceController@index');
        $api->get('/pages/{pageId}/invoices/{id}', 'InvoiceController@show');

        $api->get('/pages/{pageId}/tags', 'TagController@index');

        $api->get('/pages/{pageId}/build/trees', 'Build\TreeController@index');
        $api->post('/pages/{pageId}/build/trees', 'Build\TreeController@store');
        $api->get('/pages/{pageId}/build/trees/{id}', 'Build\TreeController@show');
        $api->put('/pages/{pageId}/build/trees/{id}', 'Build\TreeController@update');

        $api->get('/pages/{pageId}/build/main-menu/{id}', 'Build\MainMenuController@show');
        $api->put('/pages/{pageId}/build/main-menu/{id}', 'Build\MainMenuController@update');

        $api->get('/pages/{pageId}/build/greeting-text/{id}', 'Build\GreetingTextController@show');
        $api->put('/pages/{pageId}/build/greeting-text/{id}', 'Build\GreetingTextController@update');

        $api->get('/pages/{pageId}/build/welcome-message/{id}', 'Build\WelcomeMessageController@show');
        $api->put('/pages/{pageId}/build/welcome-message/{id}', 'Build\WelcomeMessageController@update');

        $api->get('/pages/{pageId}/build/default-reply/{id}', 'Build\DefaultReplyController@show');
        $api->put('/pages/{pageId}/build/default-reply/{id}', 'Build\DefaultReplyController@update');

        $api->get('/pages/{pageId}/build/ai-response/rules', 'Build\AIResponseController@rules');
        $api->post('/pages/{pageId}/build/ai-response/rules', 'Build\AIResponseController@createRule');
        $api->put('/pages/{pageId}/build/ai-response/rules/{id}', 'Build\AIResponseController@updateRule');
        $api->delete('/pages/{pageId}/build/ai-response/rules/{id}', 'Build\AIResponseController@deleteRule');

        $api->get('/pages/{pageId}/sequences', 'SequenceController@index');
        $api->get('/pages/{pageId}/sequences/{id}', 'SequenceController@show');
        $api->post('/pages/{pageId}/sequences', 'SequenceController@store');
        $api->delete('/pages/{pageId}/sequences/{id}', 'SequenceController@destroy');
        $api->put('/pages/{pageId}/sequences/{id}', 'SequenceController@update');
        $api->post('/pages/{pageId}/sequences/{sequenceId}/messages', 'SequenceMessageController@store');
        $api->put('/pages/{pageId}/sequences/{sequenceId}/messages/{id}', 'SequenceMessageController@update');
        $api->delete('/pages/{pageId}/sequences/{sequenceId}/messages/{id}', 'SequenceMessageController@destroy');

        $api->get('/pages/{pageId}/broadcasts', 'BroadcastController@index');
        $api->post('/pages/{pageId}/broadcasts', 'BroadcastController@store');
        $api->get('/pages/{pageId}/broadcasts/{id}', 'BroadcastController@show');
        $api->put('/pages/{pageId}/broadcasts/{id}', 'BroadcastController@update');
        $api->delete('/pages/{pageId}/broadcasts/{id}', 'BroadcastController@destroy');

        //        $api->get('/pages/{pageId}/widgets', 'WidgetController@index');
        //        $api->post('/pages/{pageId}/widgets', 'WidgetController@store');
        //        $api->get('/pages/{pageId}/widgets/{id}', 'WidgetController@show');
        //        $api->put('/pages/{pageId}/widgets/{id}', 'WidgetController@update');
        //        $api->delete('/pages/{pageId}/widgets/{id}', 'WidgetController@destroy');

        $api->get('/pages/{pageId}/subscribers', 'SubscriberController@index');
        $api->get('/pages/{pageId}/subscribers/{id}', 'SubscriberController@show');
        $api->put('/pages/{pageId}/subscribers/{id}', 'SubscriberController@update');
        $api->post('/pages/{pageId}/subscribers', 'SubscriberController@batchUpdate');

        $api->get('/pages/{pageId}/stats', 'StatsController@index');

        $api->post('/pages/{pageId}/message-previews', 'MessagePreviewController@store');
    });

});



$app->get('/ba/{messageBlockHash}/{subscriberHash}', function ($messageBlockHash, $subscriberHash, WebAppAdapter $MakanaAdapter) {

    $redirectTo = $MakanaAdapter->messageBlockUrl($messageBlockHash, $subscriberHash);
    if (! $redirectTo) {
        return response("", 200);
    }

    return redirect($redirectTo);
});


$app->group(['prefix' => 'callback'], function () use ($app) {

    $app->post('stripe/web-hook', '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook');

    $app->group(['prefix' => 'facebook'], function () use ($app) {

        $app->get('web-hook', function (Request $request) {
            $MakanaVerifier = new AppVerifier($request->all(), config('services.facebook.verify_token'));
            if ($MakanaVerifier->verify()) {
                return response($request->get('hub_challenge'), 200);
            }

            return response([], 500);
        });


        $app->post('web-hook', function (Request $request, Receiver $MakanaReceiver) {
            $MakanaReceiver->setData($request->all());
            $MakanaReceiver->handle();

            return response([]);
        });

        $app->post('de-authorize', function (Request $request, PageService $pages) {
            $id = array_get(parse_signed_request($request->get('signed_request', '')), 'user_id');
            if (! $id) {
                return [];
            }

            $user = User::whereFacebookId($id)->first();
            if (! $user) {
                return [];
            }

            DB::beginTransaction();
            foreach ($user->pages as $page) {
                if (! $page->users()->where('id', '!=', $user->id)->count()) {
                    $pages->disableBot($page);
                }
            }
            $user->delete();
            DB::commit();

            return [];
        });
        
    });

});