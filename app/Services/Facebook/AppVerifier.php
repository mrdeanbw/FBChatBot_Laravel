<?php namespace App\Services\Facebook;

class AppVerifier
{
    private $params;
    
    private $verifyToken;

    /**
     * AppVerifier constructor.
     * @param $params
     * @param $verifyToken
     */
    public function __construct($params, $verifyToken)
    {
        $this->params = $params;
        $this->verifyToken = $verifyToken;
    }

    /**
     * Verify Facebook Webhook
     * @return bool
     */
    public function verify()
    {
        return (array_get($this->params, 'hub_mode') == 'subscribe' && array_get($this->params, 'hub_verify_token') == $this->verifyToken);
    }

    public function challenge()
    {
        return array_get($this->params, 'hub_challenge');
    }

}