<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Common\Services\MainMenuService;
use Common\Transformers\BaseTransformer;
use Common\Transformers\MainMenuTransformer;

class MainMenuController extends APIController
{

    /**
     * @type MainMenuService
     */
    private $mainMenu;

    public function __construct(MainMenuService $mainMenu)
    {
        $this->mainMenu = $mainMenu;
        parent::__construct();
    }

    /**
     * Update the main menu associated with the page
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request)
    {
        $bot = $this->enabledBot();
        $this->validateForBot($bot, $request, [
            'buttons'   => 'bail|required|array|max:5',
            'buttons.*' => 'bail|required|array|main_menu_button',
        ]);

        $mainMenu = $this->mainMenu->update($request->all(), $bot, $this->user());

        return $this->itemResponse($mainMenu);
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new MainMenuTransformer();
    }
}
