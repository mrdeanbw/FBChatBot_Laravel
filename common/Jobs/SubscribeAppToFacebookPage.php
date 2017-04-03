<?php namespace Common\Jobs;

use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
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
     * SubscribeAppToFacebookPage constructor.
     * @param Bot      $bot
     * @param ObjectID $userId
     */
    public function __construct(Bot $bot, ObjectID $userId)
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
        $this->setSentryContext($this->bot->_id);
        try {
            $FacebookAdapter->subscribeToPage($this->bot);
        } catch (DisallowedBotOperation $e) {
        }
    }
}