<?php namespace App\Jobs;

use App\Models\Bot;
use App\Services\FacebookAPIAdapter;
use App\Services\Facebook\MessengerThread;
use App\Services\FacebookMessageMapper;

class UpdateMainMenuOnFacebook extends BaseJob
{

    /**
     * @type Bot
     */
    private $bot;

    protected $pushErrorsOnFail = true;
    protected $failMessageBody = "Failed to update the main menu on Facebook. We are looking into it!";

    /**
     * UpdateMainMenuOnFacebook constructor.
     * @param Bot $bot
     * @param     $userId
     */
    public function __construct(Bot $bot, $userId)
    {
        $this->bot = $bot;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     *
     * @param MessengerThread $MessengerThreads
     * @throws \Exception
     */
    public function handle(MessengerThread $MessengerThreads)
    {
        $messages = (new FacebookMessageMapper($this->bot))->mapMainMenuButtons();

        $response = $MessengerThreads->setPersistentMenu($this->bot->page->access_token, $messages);

        $success = isset($response->result) && starts_with($response->result, "Successfully");

        if (! $success) {
            throw new \Exception("{$this->failMessageBody}. Facebook response: " . $response->result);
        }
    }
}