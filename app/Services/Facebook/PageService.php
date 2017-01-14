<?php

namespace App\Services\Facebook;

class PageService extends API
{

    public function getManagePageList($accessToken)
    {
        $url = $this->url('/me/accounts', [
            'access_token' => $accessToken,
            'fields'       => 'id,name,picture{url},link,access_token',
            'limit'        => 500
        ]);

        $response = $this->guzzle->get($url, $this->requestOptions());

        return json_decode($response->getBody())->data;
    }
}