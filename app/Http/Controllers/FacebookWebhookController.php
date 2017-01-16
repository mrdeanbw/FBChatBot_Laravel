<?php namespace App\Http\Controllers;

use DB;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\PageService;
use App\Services\Facebook\AppVerifier;
use App\Services\FacebookWebhookReceiver;
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

        throw new BadRequestHttpException;
    }

    /**
     * Handle a webhook callback.
     * @param Request                 $request
     * @param FacebookWebhookReceiver $FacebookReceiver
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function handle(Request $request, FacebookWebhookReceiver $FacebookReceiver)
    {
        $FacebookReceiver->setData($request->all());
        $FacebookReceiver->handle();

        return response('');
    }

    /**
     * Handle when a Facebook user de-authorizes our app.
     * @param Request     $request
     * @param PageService $pages
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function deauthorize(Request $request, PageService $pages)
    {
        $signedRequest = $request->get('signed_request', '');
        $FacebookAppSecret = config('services.facebook.client_secret');

        $parsedRequest = parse_Facebook_signed_request($signedRequest, $FacebookAppSecret);
        $id = array_get($parsedRequest, 'user_id');
        if (! $id) {
            return response('');
        }

        $user = User::whereFacebookId($id)->first();
        if (! $user) {
            return response('');
        }

        DB::transaction(function () use ($user, $pages) {
            foreach ($user->pages as $page) {
                if (! $page->users()->where('id', '!=', $user->id)->count()) {
                    $pages->disableBot($page);
                }
            }
            $user->delete();
        });

        return response('');
    }
}