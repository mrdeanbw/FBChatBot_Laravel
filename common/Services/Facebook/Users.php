<?php namespace Common\Services\Facebook;

class Users extends Base
{

    public function publicProfile($id, $accessToken)
    {
        $url = $this->url($id, [
            'field'        => 'first_name,last_name,profile_pic,locale,timezone,gender',
            'access_token' => $accessToken,
        ]);

        $response = $this->guzzle->get($url, $this->requestOptions());

        return json_decode($response->getBody());
    }
}
