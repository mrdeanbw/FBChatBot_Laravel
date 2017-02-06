<?php
namespace App\Http\Controllers\API;

use App\Models\Bot;
use App\Services\SubscriberService;
use Symfony\Component\HttpFoundation\Request;

class StatsController extends APIController
{

    /**
     * @type SubscriberService
     */
    private $audience;

    protected $supportedMetrics = [
        'summary',
        'subscriber_count',
        'subscription_timeline',
        'click_count',
        'message_count'
    ];

    /**
     * StatsController constructor.
     *
     * @param SubscriberService $audience
     */
    public function __construct(SubscriberService $audience)
    {
        $this->audience = $audience;
    }

    /**
     * List of all metrics and stats.
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function index(Request $request)
    {
        $page = $this->bot();
        $dateString = $request->get('graph_date', 'last_seven_days');

        $ret = [];

        foreach ($this->supportedMetrics as $metric) {
            $methodName = camel_case($metric);
            $ret[$metric] = $this->{$methodName}($page, $dateString);
        }

        return $this->arrayResponse($ret);
    }

    /**
     * Summary metrics include:
     * 1. Total number of active subscribers.
     * 2. New subscription actions today.
     * 3. New unsubscription actions today.
     * @param Bot $page
     * @return array
     */
    private function summary(Bot $page)
    {
        return [
            'total' => $this->subscriberCount($page),
            'today' => [
                'plus'     => $this->audience->newSubscriptions($page, 'today'),
                'negative' => $this->audience->newUnsubscriptions($page, 'today'),
            ],
        ];
    }

    /**
     * Return the day-by-day number of new and total subscription actions in a given period of time
     * @param Bot    $page
     * @param string $dateString
     * @return array
     */
    private function subscriptionTimeline(Bot $page, $dateString)
    {
        $boundaries = date_boundaries($dateString);

        $dates = [];

        /**
         * For every day in the specified time period, calculate
         * the number of new subscription as well as total subscription actions.
         */
        for ($date = $boundaries[0]; $date->lt($boundaries[1]); $date->addDay()) {
            $dates[$date->format('Y-m-d')] = [
                'plus'  => $this->audience->newSubscriptions($page, $date),
                'total' => $this->audience->totalSubscriptions($page, $date),
            ];
        }

        /**
         * Total number of subscription actions in the given time period.
         */
        $total = $this->audience->totalSubscriptions($page, $dateString);

        return compact('total', 'dates');
    }

    /**
     * Total number of active subscribers.
     * @param Bot $page
     * @return array
     */
    private function subscriberCount(Bot $page)
    {
        return $this->audience->activeSubscribers($page);
    }

    /**
     * Total number of clicks on relevant message blocks in a specific time period.
     * @param Bot  $page
     * @param      $dateString
     * @return array
     */
    private function clickCount(Bot $page, $dateString)
    {
        return [
            'total'  => $page->messageClicks()->date('message_instance_clicks.created_at', $dateString)->count(),
            'unique' => $page->messageClicks()->date('message_instance_clicks.created_at', $dateString)->groupBy('subscriber_id', 'message_block_id')->count(),
        ];
    }

    /**
     * Total number of messages sent in a specific time period
     * @param Bot  $page
     * @param      $dateString
     * @return mixed
     */
    private function messageCount(Bot $page, $dateString)
    {
        return $page->messageInstances()->date('created_at', $dateString)->count();
    }

    /**
     * @return null
     */
    protected function transformer()
    {
        return null;
    }
}
