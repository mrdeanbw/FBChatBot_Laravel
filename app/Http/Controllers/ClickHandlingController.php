<?php namespace App\Http\Controllers;

use Common\Services\WebAppAdapter;
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
     * @param string $payload
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function card($payload)
    {
        if ($redirectTo = $this->adapter->handleCardClick($payload)) {
            return redirect($redirectTo);
        }

        return redirect(config('app.invalid_button_url'));
    }

    /**
     * @param string $payload
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function cardButton($payload)
    {
        if ($redirectTo = $this->adapter->handleCardUrlButtonClick($payload)) {
            return redirect($redirectTo);
        }

        return redirect(config('app.invalid_button_url'));
    }

    /**
     * @param string $payload
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function textButton($payload)
    {
        if ($redirectTo = $this->adapter->handleTextUrlButtonClick($payload)) {
            return redirect($redirectTo);
        }

        return redirect(config('app.invalid_button_url'));
    }

    /**
     * @param         $payload
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function menuButton($payload)
    {
        if ($redirectTo = $this->adapter->handleUrlMainMenuButtonClick($payload)) {
            return redirect($redirectTo);
        }

        return redirect(config('app.invalid_button_url'));
    }
}