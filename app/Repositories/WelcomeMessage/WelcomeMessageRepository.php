<?php namespace App\Repositories\WelcomeMessage;

use App\Models\Page;
use App\Models\WelcomeMessage;

interface WelcomeMessageRepository
{

    /**
     * Return the welcome message associated with a page.
     * @param Page $page
     * @return WelcomeMessage|null
     */
    public function getForPage(Page $page);

    /**
     * Create welcome message.
     * @param $page
     * @return WelcomeMessage
     */
    public function create(Page $page);
}
