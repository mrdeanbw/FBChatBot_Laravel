<?php
namespace App\Http\Controllers\API;

use App\Models\Page;
use App\Services\AudienceService;
use Symfony\Component\HttpFoundation\Request;

class StatsController extends APIController
{

    /**
     * @type AudienceService
     */
    private $audience;

    /**
     * StatsController constructor.
     *
     * @param AudienceService $audience
     */
    public function __construct(AudienceService $audience)
    {
        $this->audience = $audience;
    }

    /**
     * @param Request $request
     *
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        $page = $this->page();

        $ret = [];

        foreach (['summary', 'subscriber_count', 'subscription_timeline', 'click_count', 'message_count'] as $metric) {
            $methodName = camel_case($metric);
            $ret[$metric] = $this->{$methodName}($page, $request->get('graph_date', 'last_seven_days'));
        }

        return $this->arrayResponse($ret);
    }

    /**
     * @param Page $page
     *
     * @return array
     */
    private function summary(Page $page, $dateString)
    {
        return [
            'total' => $this->audience->activeSubscribers($page),
            'today' => [
                'plus'     => $this->audience->newSubscriptions($page, 'today'),
                'negative' => $this->audience->newUnsubscriptions($page, 'today'),
            ],
        ];
    }

    private function subscriptionTimeline(Page $page, $dateString)
    {
        $total = $this->audience->deltaSubscriptions($page, $dateString);

        $boundaries = date_boundaries($dateString);

        $dates = [];

        for ($date = $boundaries[0]; $date->lt($boundaries[1]); $date->addDay()) {
            $dates[$date->format('Y-m-d')] = [
                'plus'  => $this->audience->newSubscriptions($page, $date),
                'total' => $this->audience->totalSubscriptions($page, $date),
            ];
        }

        return compact('total', 'dates');
    }

    /**
     * @param Page $page
     *
     * @return array
     */
    private function subscriberCount(Page $page, $dateString)
    {
        return $this->audience->activeSubscribers($page);
    }

    private function clickCount(Page $page, $dateString)
    {
        return [
            'total'  => $page->messageClicks()->date('message_instance_clicks.created_at', $dateString)->count(),
            'unique' => $page->messageClicks()->date('message_instance_clicks.created_at', $dateString)->groupBy('subscriber_id', 'message_block_id')->count(),
        ];
    }

    private function messageCount(Page $page, $dateString)
    {
        return $page->messageInstances()->date('created_at', $dateString)->count();
    }


    protected function transformer()
    {
    }
}
