<?php namespace App\Http\Controllers\API;

use App\Transformers\PageTransformer;
use Illuminate\Http\Request;
use App\Services\PageService;
use App\Services\UserService;
use App\Services\TimezoneService;

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
     * @type UserService
     */
    private $users;

    /**
     * PageController constructor.
     *
     * @param UserService             $users
     * @param PageService             $pages
     * @param TimezoneService         $timezones
     */
    public function __construct(UserService $users, PageService $pages, TimezoneService $timezones)
    {
        $this->users = $users;
        $this->pages = $pages;
        $this->timezones = $timezones;
    }

    /**
     * Return the list of pages.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        $user = $this->user();

        if (! $this->users->hasAllManagingPagePermissions($user)) {
            $this->response->error("missing_permissions", 403);
        }

        $pages = $request->get('notManagedByUser')? $this->pages->getUnmanagedPages($user) : $this->pages->getAllPages($user);

        return $this->collectionResponse($pages);
    }
    
    protected function transformer()
    {
        return new PageTransformer();
    }
}