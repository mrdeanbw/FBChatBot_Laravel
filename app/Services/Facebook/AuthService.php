<?php

namespace App\Services\Facebook;

class AuthService extends API
{

    /**
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
     * @param string $accessToken
     * @return array
     */
    public function getUser($accessToken)
    {
        $url = $this->url('me', ['access_token' => $accessToken, 'fields' => 'name,first_name,last_name,gender,email,picture{url}']);

        $response = $this->guzzle->get($url, $this->requestOptions());

        return json_decode($response->getBody(), true);
    }


    /**
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

    /**
     * @param $accessToken
     * @param $clientId
     * @return bool
     */
    public function isValidToken($accessToken, $clientId)
    {
        $url = $this->url('debug_token', ['access_token' => $accessToken, 'input_token' => $accessToken]);

        $response = $this->guzzle->get($url, $this->requestOptions());
        
        $response = json_decode($response->getBody(), true);

        return array_get($response, 'data.app_id', false) === $clientId;
    }

}