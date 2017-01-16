<?php namespace App\Http\Controllers\API;

use Exception;
use Illuminate\Http\Request;
use App\Services\PageService;
use App\Services\TimezoneService;
use App\Transformers\PageTransformer;
use App\Services\PageSubscriptionService;

class PageController extends APIController
{

    /**
     * @type PageService
     */
    private $pages;
    /**
     * @type TimezoneService
     */
    private $timezones;
    /**
     * @type PageSubscriptionService
     */
    private $subscriptions;

    /**
     * PageController constructor.
     *
     * @param PageService             $pages
     * @param TimezoneService         $timezones
     * @param PageSubscriptionService $subscriptions
     */
    public function __construct(PageService $pages, TimezoneService $timezones, PageSubscriptionService $subscriptions)
    {
        $this->pages = $pages;
        $this->timezones = $timezones;
        $this->subscriptions = $subscriptions;
    }

    /**
     * Return the list of pages.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->get('disabled')) {
            return $this->inactivePageList();
        }

        if ($request->get('remote')) {
            return $this->unmanagedPageList();
        }

        return $this->activePageList();
    }

    /**
     * Create a bot for Facebook page(s).
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $this->pages->createBotForPages($this->user(), $request->get('pageIds'));

        return $this->response->created();
    }


    /**
     * Disable a bot.
     * @return \Dingo\Api\Http\Response
     */
    public function disableBot()
    {
        $this->pages->disableBot($this->page());

        return $this->response->accepted();
    }

    /**
     * Return the details of a page (with a bot).
     * @return \Dingo\Api\Http\Response
     */
    public function show()
    {
        $page = $this->page();

        return $this->itemResponse($page);
    }


    /**
     * Subscribe a page to a payment plan.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function subscribe(Request $request)
    {
        $page = $this->page();

        $token = $request->get('stripeToken');

        try {
            $this->subscriptions->newSubscription($token, $page);
        } catch (Exception $e) {
            $this->response->errorBadRequest($e->getMessage());
        }

        return $this->response->created();
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
        $page = $this->page();

        if ($request->get('is_active')) {
            $page = $this->pages->enableBot($page);

            return $this->itemResponse($page);
        }

        $this->updateTimezone($request, $page);

        return $this->itemResponse($page);
    }

    /**
     * @return PageTransformer
     */
    protected function transformer()
    {
        return new PageTransformer();
    }

    /**
     * List of Facebook Pages which have an active bot associated with them.
     * @return \Dingo\Api\Http\Response
     */
    private function activePageList()
    {
        $pages = $this->pages->activePageList($this->user());

        return $this->collectionResponse($pages);
    }

    /**
     * List of Facebook Pages which have an inactive bot associated with them.
     * @return \Dingo\Api\Http\Response
     */
    private function inactivePageList()
    {
        $pages = $this->pages->inactivePageList($this->user());

        return $this->collectionResponse($pages);
    }

    /**
     * List of Facebook Pages which don't have an associated bot.
     * @return \Dingo\Api\Http\Response
     */
    private function unmanagedPageList()
    {
        if (! $this->user()->hasManagingPagePermissions()) {
            $this->response->error("missing_permissions", 403);
        }

        $pages = $this->pages->getUnmanagedPages($this->user());

        return $this->collectionResponse($pages);
    }

    /**
     * Return the user-page subscription status.
     * @return \Dingo\Api\Http\Response
     */
    public function userStatus()
    {
        $user = $this->user();
        $page = $this->page();

        if ($user->isSubscribedTo($page)) {
            return $this->arrayResponse(['is_subscribed' => true, 'user_id' => $user->id]);
        }

        return $this->arrayResponse(['is_subscribed' => false, 'user_id' => $user->id]);
    }

    /**
     * Update the page's timezone settings.
     * @param Request $request
     * @param         $page
     */
    private function updateTimezone(Request $request, $page)
    {
        $this->validate($request, [
            'bot_timezone_string' => 'bail|required|max:255',
            'bot_timezone'        => 'bail|required|numeric|in:' . implode(',', $this->timezones->utcOffsets())
        ]);

        $page->bot_timezone_string = $request->get('bot_timezone_string');
        $page->bot_timezone = $request->get('bot_timezone');
        $page->save();
    }

}
