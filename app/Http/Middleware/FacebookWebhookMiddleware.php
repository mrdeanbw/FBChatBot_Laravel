<?php namespace App\Http\Middleware;

use Log;
use Closure;
use Illuminate\Http\Request;

class FacebookWebhookMiddleware
{

    /**
     * Verify the facebook callback
     *
     * @param         $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {

        $signature = $request->headers->get('X-Hub-Signature');
        /**
         * get the raw content
         * calculate against raw content to get escaped hex for utf characters if any
         * https://developers.facebook.com/docs/graph-api/webhooks#receiveupdates
         */
        $payload = $request->getContent();

        if ($signature === null || empty($signature)) {
            return response('Signature is missing.', 400);
        }

        //calculate sha1 hash & prefix with sha1=
        $hash = 'sha1=' . hash_hmac('sha1', $payload, config('services.facebook.client_secret'));

        if ($signature !== $hash) {
            return response('Invalid Signature', 400);
        }

        return $next($request);
    }
}