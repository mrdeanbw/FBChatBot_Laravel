<?php

namespace App\Http\Controllers\API\Build;


use App\Http\Controllers\API\APIController;
use App\Services\MainMenuService;
use App\Services\Validation\MessageBlockRuleValidator;
use App\Transformers\BaseTransformer;
use App\Transformers\MainMenuTransformer;
use Illuminate\Http\Request;

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
     * @return \Dingo\Api\Http\Response
     */
    public function show()
    {
        $page = $this->page();

        return $this->itemResponse($this->mainMenu->get($page));
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory|void
     */
    public function update(Request $request)
    {
        $page = $this->page();

        $validator = $this->runMainMenuValidation($request->all(), $page);

        if ($validator->fails()) {
            return $this->errorsResponse($validator->errors());
        }

        if ($this->mainMenu->persist($request->all(), $page)) {
            return $this->itemResponse($this->mainMenu->get($page));
        }

        return $this->errorsResponse(['Failed to add the menu to your facebook page. Try again later.']);
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new MainMenuTransformer();
    }
}
