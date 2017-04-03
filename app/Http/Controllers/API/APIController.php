<?php namespace App\Http\Controllers\API;

use Common\Models\Bot;
use Common\Services\Validation\BotValidator;
use MongoDB\BSON\ObjectID;
use Illuminate\Http\Request;
use Common\Services\BotService;
use Common\Http\Controllers\APIController as BaseAPIController;

abstract class APIController extends BaseAPIController
{

    /**
     * @type Bot
     */
    protected $bot;

    /**
     * @type Bot
     */
    protected $enabledBot;

    /**
     * @type Bot
     */
    protected $disabledBot;

    /**
     * Parses the request for the bot id, and fetches the bot from the database.
     * @param bool $enabled
     * @return Bot|null
     */
    protected function fetchBot($enabled = null)
    {
        $request = app('request');

        $botId = $this->getBotIdFromUrlParameters($request);

        if (! $botId) {
            $this->response->errorBadRequest("Bot Not Specified.");
        }

        /** @type BotService $botService */
        $botService = app(BotService::class);

        $bot = $botService->findByIdAndStatusForUser($botId, $this->user(), $enabled);

        if ($bot && config('sentry.dsn')) {
            app('sentry')->user_context([
                'bot_id' => $bot->id
            ]);
        }

        return $bot;
    }

    /**
     * @return Bot
     */
    protected function bot()
    {
        // If the bot has been already fetched, return it.
        if ($this->bot) {
            return $this->bot;
        }

        if ($bot = $this->fetchBot()) {
            return $this->bot = $bot;
        }

        $this->response->errorNotFound();
    }

    /**
     * @return Bot
     */
    protected function enabledBot()
    {
        // If the bot has been already fetched, return it.
        if ($this->enabledBot) {
            return $this->enabledBot;
        }

        if ($bot = $this->fetchBot(true)) {
            return $this->enabledBot = $bot;
        }

        $this->response->errorNotFound();
    }

    /**
     * @return Bot
     */
    protected function disabledBot()
    {
        // If the bot has been already fetched, return it.
        if ($this->disabledBot) {
            return $this->disabledBot;
        }

        if ($bot = $this->fetchBot(false)) {
            return $this->disabledBot = $bot;
        }

        $this->response->errorNotFound();
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
        $botId = $botId?: array_get($routeParameters, 'id');

        return new ObjectID($botId);
    }


    /**
     * A helper method to make the Validator.
     * @param Bot     $bot
     * @param Request $request
     * @param array   $rules
     * @param bool    $allowButtonMessages
     */
    public function validateForBot(Bot $bot, Request $request, array $rules, $allowButtonMessages = false)
    {
        $input = $request->all();
        $validator = BotValidator::factory($bot, \Validator::make($input, $rules, [], []), $allowButtonMessages);
        //If the validation fails, terminate the request and return the error messages.
        if ($validator->fails()) {
            $this->errorsResponse($validator->errors());
        }
    }
}