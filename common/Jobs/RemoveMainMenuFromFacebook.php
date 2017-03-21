<?php namespace Common\Jobs;

use Exception;
use Common\Models\Bot;
use Common\Services\Facebook;
use Common\Services\FacebookAdapter;
use Common\Exceptions\DisallowedBotOperation;

class RemoveMainMenuFromFacebook extends BaseJob
{

    /**
     * @type Bot
     */
    private $bot;

    /**
     * RemoveMainMenuFromFacebook constructor.
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
        try {
            $FacebookAdapter->removePersistentMenu($this->bot);
        } catch (DisallowedBotOperation $e) {
        }
    }
}
