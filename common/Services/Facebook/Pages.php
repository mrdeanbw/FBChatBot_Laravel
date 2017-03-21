<?php namespace Common\Services\Facebook;

class Pages extends API
{

    /**
     * Return the list of managed Facebook pages.
     * @param string $accessToken
     * @param int    $limit
     * @return object
     */
    public function getManagedPageList($accessToken, $limit = 500)
    {
        $url = $this->url('/me/accounts', [
            'access_token' => $accessToken,
            'fields'       => 'id,name,picture{url},link,access_token,perms',
            'limit'        => $limit
        ]);

        $response = $this->guzzle->get($url, $this->requestOptions());

        return json_decode($response->getBody())->data;
    }

    /**
     * @param $accessToken
     * @return object
     */
    public function subscribeToPage($accessToken)
    {
        $url = $this->url('me/subscribed_apps', ['access_token' => $accessToken]);

        $response = $this->guzzle->post($url, $this->requestOptions());

        return json_decode($response->getBody());
    }

    /**
     * @param $accessToken
     * @return object
     */
    public function unsubscribeFromPage($accessToken)
    {
        $url = $this->url('me/subscribed_apps', ['access_token' => $accessToken]);

       $response = $this->guzzle->delete($url, $this->requestOptions());

        return json_decode($response->getBody());
    }
}
