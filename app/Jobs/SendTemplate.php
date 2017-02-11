<?php namespace App\Jobs;

use App\Models\Broadcast;
use App\Models\Subscriber;
use App\Models\Template;
use App\Services\FacebookAPIAdapter;

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