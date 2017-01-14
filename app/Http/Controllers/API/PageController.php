<?php

namespace App\Http\Controllers\API;

use App\Services\PageService;
use App\Services\PageSubscriptionService;
use App\Services\TimezoneService;
use App\Transformers\PageTransformer;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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

    public function index(Request $request)
    {
        if ($request->get('disabled')) {
            return $this->inActivePageList();
        }

        if ($request->get('remote')) {
            return $this->remotePageList();
        }

        return $this->activePageList();
    }

    /**
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function store(Request $request)
    {
        $this->pages->createBotForPages($this->user(), $request->get('pageIds'));

        return $this->response->created();
    }


    public function disableBot()
    {
        $this->pages->disableBot($this->page());

        return $this->response->accepted();
    }


    /**
     * @return \Dingo\Api\Http\Response
     */
    public function show()
    {
        $page = $this->page();

        return $this->itemResponse($page);
    }


    /**
     * @param Request $request
     *
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
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request)
    {
        $page = $this->page();

        if ($request->get('is_active')) {
            $page = $this->pages->enableBot($page);

            return $this->itemResponse($page);
        }

        $this->validate($request, [
            'bot_timezone_string' => 'bail|required|max:255',
            'bot_timezone'        => 'bail|required|numeric|in:' . implode(',', $this->timezones->utcOffsets())
        ]);

        $page->bot_timezone_string = $request->get('bot_timezone_string');
        $page->bot_timezone = $request->get('bot_timezone');
        $page->save();

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
     * @return \Dingo\Api\Http\Response
     */
    private function activePageList()
    {
        $pages = $this->pages->activePageList($this->user());

        return $this->collectionResponse($pages);
    }

    private function inActivePageList()
    {
        $pages = $this->pages->inActivePageList($this->user());

        return $this->collectionResponse($pages);
    }

    /**
     * @return \Dingo\Api\Http\Response
     */
    private function remotePageList()
    {
        if (! $this->user()->hasManagingPagePermissions()) {
            throw new AccessDeniedHttpException("missing_permissions");
        }

        $pages = $this->pages->getUnmanagedPages($this->user());

        return $this->collectionResponse($pages);
    }

    public function userStatus()
    {
        $user = $this->user();
        $page = $this->page();
        if ($user->subscriber($page)) {
            return $this->arrayResponse(['is_subscribed' => true, 'user_id' => $user->id]);
        }

        return $this->arrayResponse(['is_subscribed' => false, 'user_id' => $user->id]);
    }

}
