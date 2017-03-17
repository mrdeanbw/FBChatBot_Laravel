<?php namespace Common\Jobs;

use Common\Models\Broadcast;
use Common\Models\Subscriber;
use Common\Services\FacebookMessageSender;

class SendBroadcastToSubscriber extends BaseJob
{

    /**
     * @type Broadcast
     */
    private $broadcast;

    /**
     * @type Subscriber
     */
    private $subscriber;

    /**
     * SendBroadcast constructor.
     * @param Broadcast  $broadcast
     * @param Subscriber $subscriber
     */
    public function __construct(Broadcast $broadcast, Subscriber $subscriber)
    {
        $this->broadcast = $broadcast;
        $this->subscriber = $subscriber;
    }

    /**
     * Execute the job.
     *
     * @param FacebookMessageSender $FacebookMessageSender
     * @throws \Exception
     */
    public function handle(FacebookMessageSender $FacebookMessageSender)
    {
        $FacebookMessageSender->sendBroadcastMessages($this->broadcast, $this->subscriber);
    }
}
