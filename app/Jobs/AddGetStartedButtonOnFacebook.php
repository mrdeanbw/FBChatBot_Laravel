<?php namespace App\Jobs;

use Common\Models\Bot;
use App\Services\Facebook;

class AddGetStartedButtonOnFacebook extends BaseJob
{

    /**
     * @type Bot
     */
    private $bot;
    protected $pushErrorsToFrontendOnFail = true;
    protected $frontendFailMessageBody = "Failed to add Get Started Button!";

    /**
     * InitializeBotForFacebookPage constructor.
     * @param $bot
     * @param $userId
     */
    public function __construct($bot, $userId)
    {
        $this->bot = $bot;
    }

    /**
     * Execute the job.
     * @param Facebook\MessengerThread $MessengerThreads
     */
    public function handle(Facebook\MessengerThread $MessengerThreads)
    {
        $MessengerThreads->addGetStartedButton($this->bot->page->access_token);
    }
}