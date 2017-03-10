<?php namespace Common\Services\Facebook;


class Subscription extends Base
{

    public function subscribe($accessToken)
    {
        $url = $this->url('me/subscribed_apps', ['access_token' => $accessToken]);
        
        $response = $this->guzzle->post($url, $this->requestOptions());
        
        return json_decode($response->getBody());
    }


}