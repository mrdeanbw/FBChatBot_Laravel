<?php namespace Common\Jobs;

use Common\Models\Broadcast;
use Common\Models\Subscriber;
use Common\Services\FacebookAPIAdapter;

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
     * @param FacebookAPIAdapter $FacebookAdapter
     * @throws \Exception
     */
    public function handle(FacebookAPIAdapter $FacebookAdapter)
    {
        $FacebookAdapter->sendBroadcastMessages($this->broadcast, $this->subscriber);
    }
}
