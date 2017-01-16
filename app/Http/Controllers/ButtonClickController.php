<?php namespace App\Http\Controllers;

use App\Services\WebAppAdapter;

class ButtonClickController extends Controller
{

    /**
     * @param string        $messageBlockHash
     * @param string        $subscriberHash
     * @param WebAppAdapter $WebAppAdapter
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function handle($messageBlockHash, $subscriberHash, WebAppAdapter $WebAppAdapter)
    {
        $redirectTo = $WebAppAdapter->messageBlockUrl($messageBlockHash, $subscriberHash);
        if (! $redirectTo) {
            return response("", 200);
        }

        return redirect($redirectTo);
    }
}