<?php namespace App\Http\Controllers\API;

use Common\Models\Bot;
use Common\Services\BotService;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Common\Http\Controllers\APIController as BaseAPIController;

abstract class APIController extends BaseAPIController
{

    use Helpers;
    /**
     * @type Bot
     */
    protected $bot;

    /**
     * Parses the request for the bot id, and fetches the bot from the database.
     * @return Bot
     */
    protected function bot()
    {
        /**
         * If the bot has been already fetched, return it.
         */
        if ($this->bot) {
            return $this->bot;
        }

        $request = app('request');

        $botId = $this->getBotIdFromUrlParameters($request);

        if (! $botId) {
            $this->response->errorBadRequest("Bot Not Specified.");
        }

        /** @type BotService $botService */
        $botService = app(BotService::class);

        if ($bot = $botService->findByIdForUser($botId, $this->user())) {
            return $this->bot = $bot;
        }

        return $this->response->errorNotFound();
    }

    /**
     * The bot id is always provided either through a GET parameter called "botId".
     * Or through a route parameter called "id"
     * @param Request $request
     * @return mixed
     */
    protected function getBotIdFromUrlParameters(Request $request)
    {
        $routeParameters = $request->route()[2];

        $botId = array_get($routeParameters, 'botId');

        if (! $botId) {
            $botId = array_get($routeParameters, 'id');

            return $botId;
        }

        return $botId;
    }
}