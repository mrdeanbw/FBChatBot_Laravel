<?php namespace Common\Jobs;

use Common\Services\Facebook;
use Common\Services\FacebookWebhookReceiver;
use Common\Exceptions\DisallowedBotOperation;

class HandleIncomingFacebookCallback extends BaseJob
{

    /**
     * @type array
     */
    private $data;

    /**
     * HandleIncomingFacebookCallback constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(FacebookWebhookReceiver $FacebookReceiver)
    {
        try {
            $FacebookReceiver->setData($this->data);
            $FacebookReceiver->handle();
        } catch (DisallowedBotOperation $e) {
            return;
        }
    }
}