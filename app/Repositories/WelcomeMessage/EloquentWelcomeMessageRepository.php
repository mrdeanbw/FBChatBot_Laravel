<?php namespace App\Repositories\WelcomeMessage;

use App\Models\Page;
use App\Models\WelcomeMessage;

class EloquentWelcomeMessageRepository implements WelcomeMessageRepository
{

    /**
     * Return the welcome message associated with a page.
     * @param Page $page
     * @return WelcomeMessage|null
     */
    public function getForPage(Page $page)
    {
        return $page->welcomeMessage;
    }

    /**
     * Create welcome message.
     * @param $page
     * @return WelcomeMessage
     */
    public function create(Page $page)
    {
        return $page->welcomeMessage()->create([]);
    }
}
