<?php namespace Common\Jobs;

use Common\Models\Broadcast;
use Common\Models\Subscriber;
use Common\Models\Template;
use Common\Services\FacebookMessageSender;

class SendTemplate extends BaseJob
{

    /**
     * @type Template
     */
    private $template;

    /**
     * @type Subscriber
     */
    private $subscriber;

    /**
     * SendBroadcast constructor.
     * @param Template   $template
     * @param Subscriber $subscriber
     */
    public function __construct(Template $template, Subscriber $subscriber)
    {
        $this->template = $template;
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
        $FacebookMessageSender->sendTemplate($this->template, $this->subscriber);
    }
}