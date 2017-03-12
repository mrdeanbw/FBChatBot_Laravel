<?php namespace App\Http\Controllers\API;

use Common\Models\Bot;
use Common\Services\SubscriberService;
use Common\Repositories\SentMessage\SentMessageRepositoryInterface;
use Illuminate\Http\Request;

class StatsController extends APIController
{

    /**
     * @type SubscriberService
     */
    private $subscribers;

    protected $supportedMetrics = [
        'summary',
        'subscriber_count',
        'subscription_timeline',
        'click_count',
        'message_count'
    ];
    /**
     * @type SentMessageRepositoryInterface
     */
    private $sentMessageRepo;

    /**
     * StatsController constructor.
     *
     * @param SubscriberService              $audience
     * @param SentMessageRepositoryInterface $sentMessageRepo
     */
    public function __construct(SubscriberService $audience, SentMessageRepositoryInterface $sentMessageRepo)
    {
        $this->subscribers = $audience;
        $this->sentMessageRepo = $sentMessageRepo;
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
                'plus'     => $this->subscribers->newSubscriptions($page, 'today'),
                'negative' => $this->subscribers->newUnsubscriptions($page, 'today'),
            ],
        ];
    }

    /**
     * Return the day-by-day number of new and total subscription actions in a given period of time
     * @param Bot    $bot
     * @param string $dateString
     * @return array
     */
    private function subscriptionTimeline(Bot $bot, $dateString)
    {
        $boundaries = date_boundaries($dateString);

        $dates = [];

        /**
         * For every day in the specified time period, calculate
         * the number of new subscription as well as total subscription actions.
         */
        for ($date = $boundaries[0]; $date->lt($boundaries[1]); $date->addDay()) {
            $dates[$date->format('Y-m-d')] = [
                'plus'  => $this->subscribers->newSubscriptions($bot, $date),
                'total' => $this->subscribers->totalSubscriptions($bot, $date),
            ];
        }

        /**
         * Total number of subscription actions in the given time period.
         */
        $total = $this->subscribers->totalSubscriptions($bot, $dateString);

        return compact('total', 'dates');
    }

    /**
     * Total number of active subscribers.
     * @param Bot $bot
     * @return array
     */
    private function subscriberCount(Bot $bot)
    {
        return $this->subscribers->activeSubscribers($bot);
    }

    /**
     * Total number of clicks on relevant message blocks in a specific time period.
     * @param Bot  $bot
     * @param      $dateString
     * @return array
     */
    private function clickCount(Bot $bot, $dateString)
    {
        $dateBoundaries = date_boundaries($dateString);

        return [
            'total'          => $this->sentMessageRepo->totalMessageClicksForBot($bot, $dateBoundaries[0], $dateBoundaries[1]),
            'per_subscriber' => $this->sentMessageRepo->perSubscriberMessageClicksForBot($bot, $dateBoundaries[0], $dateBoundaries[1]),
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
        $dateBoundaries = date_boundaries($dateString);

        return $this->sentMessageRepo->totalSentForBot($page, $dateBoundaries[0], $dateBoundaries[1]);
    }

    /**
     * @return null
     */
    protected function transformer()
    {
        return null;
    }
}
