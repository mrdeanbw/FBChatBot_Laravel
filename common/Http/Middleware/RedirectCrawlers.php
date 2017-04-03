<?php namespace Common\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class RedirectCrawlers
{

    /**
     * @var CrawlerDetect
     */
    private $crawlerDetector;

    /**
     * RedirectCrawlers constructor.
     * @param CrawlerDetect $crawlerDetector
     */
    public function __construct(CrawlerDetect $crawlerDetector)
    {
        $this->crawlerDetector = $crawlerDetector;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Lumen\Http\Redirector|mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->crawlerDetector->isCrawler()) {
            return redirect('https://www.facebook.com');
        }

        return $next($request);
    }

}