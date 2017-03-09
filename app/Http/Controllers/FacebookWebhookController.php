<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BotService;
use App\Services\Facebook\AppVerifier;
use Common\Http\Controllers\Controller;
use App\Jobs\HandleIncomingFacebookCallback;
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
     * @param Request    $request
     * @param BotService $pages
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function deauthorize(Request $request, BotService $pages)
    {
//        $signedRequest = $request->get('signed_request', '');
//        $FacebookAppSecret = config('services.facebook.client_secret');
//
//        $parsedRequest = parse_Facebook_signed_request($signedRequest, $FacebookAppSecret);
//        $id = array_get($parsedRequest, 'user_id');
//        if (! $id) {
//            return response('');
//        }
//
//        $user = User::whereFacebookId($id)->first();
//        if (! $user) {
//            return response('');
//        }
//
//        DB::transaction(function () use ($user, $pages) {
//            foreach ($user->pages as $page) {
//                if (! $page->users()->where('id', '!=', $user->id)->count()) {
//                    $pages->disableBot($page);
//                }
//            }
//            $user->delete();
//        });

        return response('');
    }
}