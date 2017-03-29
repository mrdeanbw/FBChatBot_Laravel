<?php namespace Common\Jobs;

use Exception;
use Common\Models\Bot;
use Common\Services\FacebookAdapter;
use Common\Exceptions\DisallowedBotOperation;

class RemoveGetStartedButtonFromFacebook extends BaseJob
{

    /**
     * @type Bot
     */
    private $bot;

    /**
     * RemoveGetStartedButtonFromFacebook constructor.
     * @param $bot
     */
    public function __construct($bot)
    {
        $this->bot = $bot;
    }

    /**
     * Execute the job.
     * @param FacebookAdapter $FacebookAdapter
     * @throws Exception
     */
    public function handle(FacebookAdapter $FacebookAdapter)
    {
        $this->setSentryContext($this->bot->_id);
        try {
            $FacebookAdapter->removeGetStartedButton($this->bot);
        } catch (DisallowedBotOperation $e) {
        }
    }
}
