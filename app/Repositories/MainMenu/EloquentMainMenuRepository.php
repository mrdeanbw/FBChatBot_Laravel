<?php namespace App\Repositories\MainMenu;

use App\Models\MainMenu;
use App\Models\Page;

class EloquentMainMenuRepository implements MainMenuRepository
{

    /**
     * Return the main menu associated with a page.
     * @param Page $page
     * @return MainMenu|null
     */
    public function getForPage(Page $page)
    {
        return $page->mainMenu;
    }
}
