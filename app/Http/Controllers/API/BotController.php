<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Common\Services\BotService;
use Common\Transformers\BotTransformer;

class BotController extends APIController
{

    /**
     * @type BotService
     */
    private $bots;

    /**
     * PageController constructor.
     *
     * @param BotService $bots
     */
    public function __construct(BotService $bots)
    {
        $this->bots = $bots;
        parent::__construct();
    }

    /**
     * List of Facebook Pages which have an active bot associated with them.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function enabledBots(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $bots = $this->bots->enabledBots($this->user(), $page);

        return $this->paginatorResponse($bots);
    }

    /**
     * List of Facebook Pages which have an inactive bot associated with them.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function disabledBots(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $bots = $this->bots->disabledBots($this->user(), $page);

        return $this->paginatorResponse($bots);
    }

    /**
     * @return \Dingo\Api\Http\Response
     */
    public function countEnabled()
    {
        return $this->arrayResponse([
            'count' => $this->bots->countEnabledForUser($this->user())
        ]);
    }

    /**
     * @return \Dingo\Api\Http\Response
     */
    public function countDisabled()
    {
        return $this->arrayResponse([
            'count' => $this->bots->countDisabledForUser($this->user())
        ]);
    }

    /**
     * Create a bot for Facebook page(s).
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'pages'    => 'bail|required|array',
            'pages.*'  => 'bail|required|string',
            'timezone' => 'bail|required|timezone'
        ]);

        $bots = $this->bots->createBotForPages($this->user(), $request->all());

        return $this->collectionResponse($bots);
    }

    /**
     * Return the details of a page (with a bot).
     * @return \Dingo\Api\Http\Response
     */
    public function show()
    {
        $bot = $this->enabledBot();
        $bot->current_user = $this->user();

        return $this->itemResponse($bot);
    }

    /**
     * enable a bot.
     * @return \Dingo\Api\Http\Response
     */
    public function enable()
    {
        $this->bots->enableBot($this->disabledBot(), $this->user());

        return $this->response->accepted();
    }

    /**
     * Disable a bot.
     * @return \Dingo\Api\Http\Response
     */
    public function disable()
    {
        $this->bots->disableBot($this->enabledBot());

        return $this->response->accepted();
    }

    public function createTag(Request $request)
    {
        $bot = $this->enabledBot();

        $this->validate($request, [
            'tag' => 'bail|required|string|not_in:' . implode(',', $bot->tags)
        ]);

        $tags = $this->bots->createTag($bot, trim($request->get('tag')));

        return $this->arrayResponse(['tags' => $tags]);
    }

    /**
     * Patch-update a page.
     * Either enable the bot for the page or update its timezone.
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request)
    {
        $bot = $this->enabledBot();

        $this->validate($request, [
            'timezone' => 'bail|required|timezone',
        ]);

        $bot = $this->bots->updateTimezone($request->all(), $bot);

        return $this->itemResponse($bot);
    }

    /**
     * @return BotTransformer
     */
    protected function transformer()
    {
        return new BotTransformer();
    }
}
