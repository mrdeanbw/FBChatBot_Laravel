<?php
namespace App\Services\Facebook\Makana;


use stdClass;

class Thread extends Base
{

    const GET_STARTED_PAYLOAD = 'GET_STARTED';

    /**
     * @param $accessToken
     * @param $text
     * @return mixed
     */
    public function addGreetingText($accessToken, $text)
    {
        $postData = [
            'setting_type' => 'greeting',
            'greeting'     => [
                'text' => $text
            ]
        ];

        $url = $this->url('me/thread_settings', ['access_token' => $accessToken]);

        $response = $this->guzzle->post($url, array_merge($this->requestOptions(), ['form_params' => $postData]));

        return json_decode($response->getBody());
    }

    /**
     * @param $accessToken
     * @return mixed
     */
    public function addGetStartedButton($accessToken)
    {
        $button = new stdClass();
        $button->payload = self::GET_STARTED_PAYLOAD;

        $postData = [
            'setting_type'    => 'call_to_actions',
            'thread_state'    => 'new_thread',
            'call_to_actions' => [$button]
        ];

        $url = $this->url('me/thread_settings', ['access_token' => $accessToken]);

        $response = $this->guzzle->post($url, array_merge($this->requestOptions(), ['form_params' => $postData]));

        return json_decode($response->getBody());
    }

    /**
     * @param $accessToken
     * @param $buttons
     * @return mixed
     */
    public function setPersistentMenu($accessToken, $buttons)
    {
        $postData = [
            'setting_type'    => 'call_to_actions',
            'thread_state'    => 'existing_thread',
            'call_to_actions' => $buttons
        ];

        $url = $this->url('/me/thread_settings', ['access_token' => $accessToken]);

        $response = $this->guzzle->post($url, array_merge($this->requestOptions(), ['form_params' => $postData]));

        return json_decode($response->getBody());
    }


}