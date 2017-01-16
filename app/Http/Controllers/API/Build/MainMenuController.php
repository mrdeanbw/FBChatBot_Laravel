<?php namespace App\Http\Controllers\API\Build;


use Illuminate\Http\Request;
use App\Services\MainMenuService;
use App\Transformers\BaseTransformer;
use App\Transformers\MainMenuTransformer;
use App\Http\Controllers\API\APIController;
use App\Services\Validation\MessageBlockRuleValidator;

class MainMenuController extends APIController
{

    use MessageBlockRuleValidator;

    /**
     * @type MainMenuService
     */
    private $mainMenu;

    public function __construct(MainMenuService $mainMenu)
    {
        $this->mainMenu = $mainMenu;
    }

    /**
     * Return the details of the page's persistent menu.
     * @return \Dingo\Api\Http\Response
     */
    public function show()
    {
        $page = $this->page();
        $mainMenu = $this->mainMenu->getOrFail($page);

        return $this->itemResponse($mainMenu);
    }


    /**
     * Update the main menu associated with the page
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request)
    {
        $page = $this->page();

        $validator = $this->makeMainMenuValidator($request->all(), $page);

        if ($validator->fails()) {
            $this->errorsResponse($validator->errors());
        }

        if (! $this->mainMenu->update($request->all(), $page)) {
            $this->errorsResponse(['Failed to add the menu to your facebook page. Try again later.']);
        }

        return $this->itemResponse($this->mainMenu->getOrFail($page));
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new MainMenuTransformer();
    }
}
