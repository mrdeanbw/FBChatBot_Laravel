<?php namespace Common\Services\Facebook;

class PageService extends API
{

    /**
     * Return the list of managed Facebook pages.
     * @param     $accessToken
     * @param int $limit
     * @return mixed
     */
    public function getManagePageList($accessToken, $limit = 100)
    {
        $url = $this->url('/me/accounts', [
            'access_token' => $accessToken,
            'fields'       => 'id,name,picture{url},link,access_token',
            'limit'        => $limit
        ]);

        $response = $this->guzzle->get($url, $this->requestOptions());

        return json_decode($response->getBody())->data;
    }
}