<?php namespace App\Http\Controllers;

use App\Services\WebAppAdapter;
use Common\Http\Controllers\Controller;

class ClickHandlingController extends Controller
{

    /**
     * @type WebAppAdapter
     */
    private $adapter;

    /**
     * ClickHandlingController constructor.
     * @param WebAppAdapter $webAppAdapter
     */
    public function __construct(WebAppAdapter $webAppAdapter)
    {
        $this->adapter = $webAppAdapter;
    }

    /**
     * @param $payload
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function handle($payload)
    {
        if ($redirectTo = $this->adapter->handleUrlMessageClick(urldecode($payload))) {
            return redirect($redirectTo);
        }

        return response("Oops", 200);
    }

    /**
     * @param string $botId
     * @param string $buttonId
     * @param string $revisionId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function mainMenuButton($botId, $buttonId, $revisionId)
    {
        $redirectTo = $this->adapter->handleUrlMainMenuButtonClick($botId, $buttonId, $revisionId);

        return is_null($redirectTo)? response("", 200) : redirect($redirectTo);
    }
}