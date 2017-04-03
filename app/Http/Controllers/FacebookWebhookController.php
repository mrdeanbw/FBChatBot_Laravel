<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Common\Services\BotService;
use Common\Jobs\DeAuthorizeUser;
use Common\Http\Controllers\Controller;
use Common\Services\Facebook\AppVerifier;
use Common\Jobs\HandleIncomingFacebookCallback;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FacebookWebhookController extends Controller
{

    /**
     * Verify webhook URL for a Facebook app.
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function verify(Request $request)
    {
        $FacebookAppVerifier = new AppVerifier(
            $request->all(),
            config('services.facebook.verify_token')
        );

        if ($FacebookAppVerifier->verify()) {
            return response($FacebookAppVerifier->challenge(), 200);
        }

        throw new BadRequestHttpException("Invalid Request.");
    }

    /**
     * Handle a webhook callback.
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function handle(Request $request)
    {
        $job = (new HandleIncomingFacebookCallback($request->all()))->onQueue('onetry');
        dispatch($job);

        return response('');
    }

    /**
     * Handle when a Facebook user de-authorizes our app.
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function deauthorize(Request $request)
    {
        \Log::debug($request->method());
        \Log::debug(json_encode($request->all()));
        $signedRequest = $request->get('signed_request', '');
        $FacebookAppSecret = config('services.facebook.client_secret');

        $parsedRequest = parse_Facebook_signed_request($signedRequest, $FacebookAppSecret);
        $id = array_get($parsedRequest, 'user_id');
        if (! $id) {
            return response('');
        }

        dispatch(new DeAuthorizeUser($id));

        return response('');
    }
}