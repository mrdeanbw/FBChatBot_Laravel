<?php namespace App\Jobs;

use App\Models\Bot;
use App\Services\Facebook;
use App\Services\Facebook\MessengerThread;

class UpdateGreetingTextOnFacebook extends BaseJob
{

    protected $pushErrorsToFrontendOnFail = true;
    protected $frontendFailMessageBody = "Failed to update the greeting text on Facebook. We are looking into it!";

    /**
     * @type Bot
     */
    private $bot;

    /**
     * UpdateGreetingTextOnFacebook constructor.
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
        $text = $this->normaliseGreetingText($this->bot->greeting_text);

        $response = $MessengerThreads->addGreetingText($this->bot->page->access_token, $text);

        $success = isset($response->result) && starts_with($response->result, "Successfully");

        if (! $success) {
            throw new \Exception("{$this->frontendFailMessageBody}. Facebook response: " . $response->result);
        }
    }


    /**
     * Map our own name placeholder {{SHORT_CODE}} to Facebook processed placeholder.
     * @see https://developers.facebook.com/docs/messenger-platform/thread-settings/greeting-text#personalization
     * @param $greetingText
     * @return string
     */
    public function normaliseGreetingText($greetingText)
    {
        return str_replace(
            ['{{first_name}}', '{{last_name}}', '{{full_name}}'],
            ['{{user_first_name}}', '{{user_last_name}}', '{{user_full_name}}'],
            $greetingText->text
        );
    }
}