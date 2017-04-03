<?php namespace App\Http\Controllers;

use Exception;
use MongoDB\BSON\ObjectID;
use Common\Services\WebAppAdapter;
use Common\Services\EncryptionService;
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

        dd('oops');

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
     * @param $payload
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function menuButton($payload)
    {
        try {
            $payload = EncryptionService::Instance()->decrypt($payload);
            $botId = substr($payload, 48, 12) . substr($payload, 24, 12);
            $buttonId = substr($payload, 36, 12) . substr($payload, 0, 12);
            $revisionId = substr($payload, 12, 12) . substr($payload, 60, 12);
            $botId = new ObjectID($botId);
            $buttonId = new ObjectID($buttonId);
            $revisionId = new ObjectID($revisionId);
        } catch (Exception $e) {
            return redirect(config('app.invalid_button_url'));
        }

        if ($redirectTo = $this->adapter->handleUrlMainMenuButtonClick($botId, $buttonId, $revisionId)) {
            return redirect($redirectTo);
        }

        return redirect(config('app.invalid_button_url'));
    }
}