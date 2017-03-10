<?php namespace Common\Services\Facebook;

class AuthService extends API
{

    /**
     * Return the list of permissions that has been granted to us.
     * @param string $accessToken
     * @return array
     */
    public function getGrantedPermissionList($accessToken)
    {
        $url = $this->url('me/permissions', ['access_token' => $accessToken]);

        $response = $this->guzzle->get($url, $this->requestOptions());

        $data = json_decode($response->getBody())->data;

        $filteredPermissions = array_filter($data, function ($record) {
            return $record->status == 'granted';
        });

        return array_map(function ($record) {
            return $record->permission;
        }, $filteredPermissions);
    }

    /**
     * Get Facebook user.
     * @param string $accessToken
     * @return array
     */
    public function getUser($accessToken)
    {
        $url = $this->url('me', [
            'access_token' => $accessToken,
            'fields'       => 'name,first_name,last_name,gender,email,picture{url}'
        ]);

        $response = $this->guzzle->get($url, $this->requestOptions());

        return json_decode($response->getBody(), true);
    }


    /**
     * Exchange the short-term access token, with a long-lived one.
     * @param $accessToken
     * @param $clientId
     * @param $clientSecret
     * @return mixed
     */
    public function getExtendedAccessToken($accessToken, $clientId, $clientSecret)
    {
        $url = $this->url('oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $clientId,
            'client_secret'     => $clientSecret,
            'fb_exchange_token' => $accessToken
        ]);

        $response = $this->guzzle->get($url, $this->requestOptions());

        parse_str($response->getBody(), $response);

        return $response;
    }
}