<?php namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Services\MainMenuService;
use App\Transformers\BaseTransformer;
use App\Transformers\MainMenuTransformer;
use App\Services\Validation\MessageValidationHelper;

class MainMenuController extends APIController
{

    use MessageValidationHelper;

    /**
     * @type MainMenuService
     */
    private $mainMenu;

    public function __construct(MainMenuService $mainMenu)
    {
        $this->mainMenu = $mainMenu;
    }

    /**
     * Update the main menu associated with the page
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function update(Request $request)
    {
        $rules = $this->validationRules();
        $this->validate($request, $rules);
        $mainMenu = $this->mainMenu->update($request->all(), $this->bot(), $this->user());

        return $this->itemResponse($mainMenu);
    }

    /** @return BaseTransformer */
    protected function transformer()
    {
        return new MainMenuTransformer();
    }

    /**
     * @return array
     */
    public function validationRules()
    {
        return [
            'buttons'   => 'bail|required|array|max:5',
            'buttons.*' => 'bail|required|message:button',
        ];
    }

}
