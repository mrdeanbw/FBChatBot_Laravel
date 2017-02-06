<?php namespace App\Services\Facebook;

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
    protected $version = 'v2.8';

    /**
     * @type Client
     */
    protected $guzzle;

    /**
     * API constructor.
     */
    public function __construct()
    {
        $this->guzzle = new Client();
    }

    /**
     * Build the Facebook API url.
     * @param       $path
     * @param array $params
     * @return string
     */
    protected function url($path, $params = [])
    {
        return $this->graphUrl . '/' . ltrim($path, '/') . '?' . http_build_query($params);
    }

    /**
     * Default Guzzle Request Options.
     * @return array
     */
    protected function requestOptions()
    {
        return [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
    }
}