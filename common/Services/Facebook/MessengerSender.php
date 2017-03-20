<?php namespace Common\Services\Facebook;

class MessengerSender extends Base
{

    /**
     * @param      $accessToken
     * @param      $data
     * @param bool $asyncMode
     * @return object | \GuzzleHttp\Promise\PromiseInterface
     */
    public function send($accessToken, $data, $asyncMode)
    {
        $url = $this->url('me/messages', ['access_token' => $accessToken]);

        if ($asyncMode) {
            return $this->guzzle->postAsync($url, array_merge($this->requestOptions(), ['form_params' => $data]));
        }

        $response = $this->guzzle->post($url, array_merge($this->requestOptions(), ['form_params' => $data]));

        return json_decode($response->getBody());
    }
}
