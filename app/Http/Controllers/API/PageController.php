<?php namespace App\Http\Controllers\API;

use Common\Models\User;
use Illuminate\Http\Request;
use Common\Services\PageService;
use Common\Services\TimezoneService;
use Common\Transformers\PageTransformer;

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
     * PageController constructor.
     *
     * @param PageService     $pages
     * @param TimezoneService $timezones
     */
    public function __construct(PageService $pages, TimezoneService $timezones)
    {
        $this->pages = $pages;
        $this->timezones = $timezones;
        parent::__construct();
    }

    /**
     * Return the list of pages.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        /** @type User $user */
        $user = $this->user();

        $pages = $request->get('notManagedByUser')? $this->pages->getUnmanagedPages($user) : $this->pages->getAllPages($user);

        return $this->collectionResponse($pages);
    }

    /**
     * @return PageTransformer
     */
    protected function transformer()
    {
        return new PageTransformer();
    }
}