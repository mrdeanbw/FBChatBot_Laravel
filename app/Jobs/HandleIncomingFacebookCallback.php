<?php namespace App\Jobs;

use App\Services\Facebook;
use App\Services\FacebookWebhookReceiver;

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
        $FacebookReceiver->setData($this->data);
        $FacebookReceiver->handle();
    }
}