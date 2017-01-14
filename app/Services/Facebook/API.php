<?php

namespace App\Services\Facebook;

use GuzzleHttp\Client;

abstract class API
{

    /**
     * The base Facebook Graph URL.
     *
     * @var string
     */
    protected $graphUrl = 'https://graph.facebook.com';

    /**
     * The Graph API version for the request.
     *
     * @var string
     */
    protected $version = 'v2.6';

    /**
     * @type Client
     */
    protected $guzzle;

    public function __construct()
    {
        $this->guzzle = new Client(['http_errors' => false]);
    }

    protected function url($path, $params = [])
    {
        return $this->graphUrl . '/' . ltrim($path, '/') . '?' . http_build_query($params);
    }

    protected function requestOptions()
    {
        return [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
    }
}