<?php namespace Common\Jobs;

use Common\Models\Bot;
use Common\Services\Facebook;

class SubscribeAppToFacebookPage extends BaseJob
{

    /**
     * @type Bot
     */
    private $bot;
    protected $pushErrorsToFrontendOnFail = true;
    protected $frontendFailMessageBody = "Failed to subscriber our app to your page!";

    /**
     * InitializeBotForFacebookPage constructor.
     * @param $bot
     * @param $userId
     */
    public function __construct($bot, $userId)
    {
        $this->bot = $bot;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     * @param Facebook\Subscription    $FacebookSubscriptions
     */
    public function handle(Facebook\Subscription $FacebookSubscriptions)
    {
        $FacebookSubscriptions->subscribe($this->bot->page->access_token);
    }
}