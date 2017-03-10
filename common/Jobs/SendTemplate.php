<?php namespace Common\Jobs;

use Common\Models\Broadcast;
use Common\Models\Subscriber;
use Common\Models\Template;
use Common\Services\FacebookAPIAdapter;

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
     * @param FacebookAPIAdapter $FacebookAdapter
     * @throws \Exception
     */
    public function handle(FacebookAPIAdapter $FacebookAdapter)
    {
        $FacebookAdapter->sendTemplate($this->template, $this->subscriber);
    }
}