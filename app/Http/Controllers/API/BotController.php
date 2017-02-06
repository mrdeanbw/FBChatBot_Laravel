<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\BotService;
use App\Services\TimezoneService;
use App\Transformers\BotTransformer;

class BotController extends APIController
{

    /**
     * @type BotService
     */
    private $bots;
    /**
     * @type TimezoneService
     */
    private $timezones;

    /**
     * PageController constructor.
     *
     * @param BotService              $bots
     * @param TimezoneService         $timezones
     */
    public function __construct(BotService $bots, TimezoneService $timezones)
    {
        $this->bots = $bots;
        $this->timezones = $timezones;
    }

    /**
     * Return the list of pages.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->get('disabled')) {
            return $this->inactiveBots();
        }

        return $this->activeBots();
    }

    /**
     * List of Facebook Pages which have an active bot associated with them.
     * @return \Dingo\Api\Http\Response
     */
    private function activeBots()
    {
        $bots = $this->bots->enabledBots($this->user());

        return $this->collectionResponse($bots);
    }

    /**
     * List of Facebook Pages which have an inactive bot associated with them.
     * @return \Dingo\Api\Http\Response
     */
    private function inactiveBots()
    {
        $bots = $this->bots->disabledBots($this->user());

        return $this->collectionResponse($bots);
    }

    /**
     * Create a bot for Facebook page(s).
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $bots = $this->bots->createBotForPages($this->user(), $request->get('pageIds', []));

        return $this->collectionResponse($bots);
    }

    /**
     * Return the details of a page (with a bot).
     * @return \Dingo\Api\Http\Response
     */
    public function show()
    {
        $bot = $this->bot();
        $bot->current_user = $this->user();

        return $this->itemResponse($bot);
    }

    /**
     * enable a bot.
     * @return \Dingo\Api\Http\Response
     */
    public function enable()
    {
        $this->bots->enableBot($this->bot());

        return $this->response->accepted();
    }

    /**
     * Disable a bot.
     * @return \Dingo\Api\Http\Response
     */
    public function disable()
    {
        $this->bots->disableBot($this->bot());

        return $this->response->accepted();
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
        $bot = $this->bot();

        $this->validate($request, [
            'timezone'        => 'bail|required|max:255',
            'timezone_offset' => 'bail|required|numeric|in:' . implode(',', $this->timezones->utcOffsets())
        ]);
        
        $this->bots->updateTimezone($request->all(), $bot);

        return $this->response->accepted();
    }

    /**
     * @return BotTransformer
     */
    protected function transformer()
    {
        return new BotTransformer();
    }
}
