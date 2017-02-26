<?php namespace App\Http\Controllers;

use App\Services\WebAppAdapter;

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
        // @todo redirect to a frontend page that displays a user friendly error.
        // like "Button / Card cannot be found (it has been deleted or sth)."
        return response("Oops", 200);
    }

    /**
     * @param string $botId
     * @param string $buttonId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function mainMenuButton($botId, $buttonId)
    {
        $redirectTo = $this->adapter->getMainMenuButtonUrl($botId, $buttonId);

        // @todo redirect to the frontend with invalid button page.
        return is_null($redirectTo)? response("", 200) : redirect($redirectTo);
    }
}