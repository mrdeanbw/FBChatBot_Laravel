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
     * @param string $messageBlockHash
     * @param string $subscriberHash
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function handle($messageBlockHash, $subscriberHash)
    {
        $redirectTo = $this->adapter->getMessageBlockRedirectURL($messageBlockHash, $subscriberHash);
        if (! $redirectTo) {
            return response("", 200);
        }

        return redirect($redirectTo);
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