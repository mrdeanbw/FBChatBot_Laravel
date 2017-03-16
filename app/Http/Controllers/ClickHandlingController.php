<?php namespace App\Http\Controllers;

use Common\Services\WebAppAdapter;
use Common\Http\Controllers\Controller;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class ClickHandlingController extends Controller
{

    /**
     * @type WebAppAdapter
     */
    private $adapter;
    /**
     * @type CrawlerDetect
     */
    private $crawlerDetector;

    /**
     * ClickHandlingController constructor.
     * @param WebAppAdapter $webAppAdapter
     * @param CrawlerDetect $crawlerDetector
     */
    public function __construct(WebAppAdapter $webAppAdapter, CrawlerDetect $crawlerDetector)
    {
        $this->adapter = $webAppAdapter;
        $this->crawlerDetector = $crawlerDetector;
    }

    /**
     * @param string $payload
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function handle($payload)
    {
        if ($this->crawlerDetector->isCrawler()) {
            return response('');
        }

        if ($redirectTo = $this->adapter->handleUrlMessageClick(urldecode($payload))) {
            return redirect($redirectTo);
        }

        return redirect(config('app.invalid_button_url'));
    }

    /**
     * @param string $botId
     * @param string $buttonId
     * @param string $revisionId
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\Redirector|\Laravel\Lumen\Http\ResponseFactory
     */
    public function mainMenuButton($botId, $buttonId, $revisionId)
    {
        if ($this->crawlerDetector->isCrawler()) {
            return response('');
        }

        if ($redirectTo = $this->adapter->handleUrlMainMenuButtonClick($botId, $buttonId, $revisionId)) {
            return redirect($redirectTo);
        }

        return redirect(config('app.invalid_button_url'));
    }
}