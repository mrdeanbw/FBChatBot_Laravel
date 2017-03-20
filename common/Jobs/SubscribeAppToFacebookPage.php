<?php namespace Common\Jobs;

use Common\Models\Bot;
use Common\Services\FacebookAdapter;
use Common\Exceptions\DisallowedBotOperation;

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
    public function __construct(Bot $bot, $userId)
    {
        $this->bot = $bot;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     * @param FacebookAdapter $FacebookAdapter
     */
    public function handle(FacebookAdapter $FacebookAdapter)
    {
        try {
            $FacebookAdapter->subscribeToPage($this->bot);
        } catch (DisallowedBotOperation $e) {
        }
    }
}