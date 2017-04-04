<?php namespace Common\Jobs;

use Common\Exceptions\DisallowedBotOperation;
use Common\Models\Bot;
use Common\Models\Template;
use Common\Models\Subscriber;
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
     * @var Bot
     */
    private $bot;

    /**
     * SendBroadcast constructor.
     * @param Template   $template
     * @param Subscriber $subscriber
     * @param Bot        $bot
     */
    public function __construct(Template $template, Subscriber $subscriber, Bot $bot)
    {
        $this->bot = $bot;
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
        $this->setSentryContext($this->bot->_id);
        try {
            $FacebookMessageSender->sendTemplate($this->template, $this->subscriber, $this->bot);
        } catch (DisallowedBotOperation $e) {
            return;
        }
    }
}