<?php namespace App\Repositories\MainMenu;

use App\Models\MainMenu;
use App\Models\Page;

interface MainMenuRepository
{

    /**
     * Return the main menu associated with a page.
     * @param Page $page
     * @return MainMenu|null
     */
    public function getForPage(Page $page);
}
