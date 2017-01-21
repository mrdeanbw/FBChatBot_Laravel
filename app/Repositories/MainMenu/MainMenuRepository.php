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

    /**
     * Create main menu.
     * @param $page
     * @return MainMenu
     */
    public function create(Page $page);

    /**
     * Return a fresh instance of the main menu.
     * @param MainMenu $mainMenu
     * @return MainMenu
     */
    public function fresh(MainMenu $mainMenu);

}
