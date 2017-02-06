<?php namespace App\Jobs;

use App\Models\Bot;
use App\Services\Facebook;

class InitializeBotForFacebookPage extends BaseJob
{

    /**
     * @type Bot
     */
    private $bot;

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
     * @param Facebook\MessengerThread $MessengerThreads
     */
    public function handle(Facebook\Subscription $FacebookSubscriptions, Facebook\MessengerThread $MessengerThreads)
    {
        $FacebookSubscriptions->subscribe($this->bot->page->access_token);

        $MessengerThreads->addGetStartedButton($this->bot->page->access_token);

        dispatch(new UpdateMainMenuOnFacebook($this->bot, $this->userId));

        dispatch(new UpdateGreetingTextOnFacebook($this->bot, $this->userId));
    }
}