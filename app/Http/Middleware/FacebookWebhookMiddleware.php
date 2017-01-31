<?php
namespace App\Http\Middleware;

use Closure;
use Log;

class FacebookWebhookMiddleware
{
	/**
	 * Verify the facebook callback 
	 * @param type $request 
	 * @param Closure $next 
	 * @return Response
	 * @TODO: remove log after this feature be stable (to avoid log flooding)
	 */
    public function handle($request, Closure $next)
    {

    	$signature = $request->headers->get('X-Hub-Signature');
        /**
         * get the raw content
         * calculate against raw content to get escaped hex for utf characters if any
         * https://developers.facebook.com/docs/graph-api/webhooks#receiveupdates
         */ 
    	$payload = $request->getContent(); 

    	if($signature === null || empty($signature)){
    		Log::warning("request to callback with missing signature Payload: ".$payload);
    		return response('Signature is missing.',400);
    	}

    	//calculate sha1 hash & prefix with sha1=
    	$hash = 'sha1='.hash_hmac('sha1', $payload, config('services.facebook.client_secret'));
    	
    	if($signature !== $hash)
    	{
    		Log::warning("request to callback with invalid signature Payload: ".$payload);
    		return response('Invalid Signature',400);
    	}

        return $next($request);
    }
}