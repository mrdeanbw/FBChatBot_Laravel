<?php namespace Common\Services;

use Carbon\Carbon;
use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
use Common\Models\Subscriber;
use Common\Models\AudienceFilter;
use Illuminate\Pagination\Paginator;
use Common\Repositories\Bot\BotRepositoryInterface;
use Common\Repositories\Sequence\SequenceRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Common\Repositories\Subscriber\SubscriberRepositoryInterface;
use Common\Repositories\SentMessage\SentMessageRepositoryInterface;
use Common\Repositories\Sequence\SequenceScheduleRepositoryInterface;

class SubscriberService
{

    /**
     * @type array
     */
    protected $filterFieldsMap = [
        'first_name'          => 'first_name',
        'last_name'           => 'last_name',
        'active'              => 'active',
        'gender'              => 'gender',
        'created_at'          => 'created_at',
        'last_interaction_at' => 'last_interaction_at',
    ];

    /**
     * @type SubscriberRepositoryInterface
     */
    private $subscriberRepo;
    /**
     * @type SequenceRepositoryInterface
     */
    private $sequenceRepo;
    /**
     * @type SequenceService
     */
    private $sequences;
    /**
     * @type BotRepositoryInterface
     */
    private $botRepo;
    /**
     * @var SequenceScheduleRepositoryInterface
     */
    private $sequenceScheduleRepo;
    /**
     * @type FacebookAdapter
     */
    private $FacebookAdapter;
    /**
     * @var SentMessageRepositoryInterface
     */
    private $sentMessageRepo;

    /**
     * AudienceService constructor.
     *
     * @param SequenceService                     $sequences
     * @param BotRepositoryInterface              $botRepo
     * @param FacebookAdapter                     $FacebookAdapter
     * @param SequenceRepositoryInterface         $sequenceRepo
     * @param SubscriberRepositoryInterface       $subscriberRepo
     * @param SentMessageRepositoryInterface      $sentMessageRepo
     * @param SequenceScheduleRepositoryInterface $sequenceScheduleRepo
     * @internal param FacebookUser $FacebookUsers
     */
    public function __construct(
        SequenceService $sequences,
        BotRepositoryInterface $botRepo,
        FacebookAdapter $FacebookAdapter,
        SequenceRepositoryInterface $sequenceRepo,
        SubscriberRepositoryInterface $subscriberRepo,
        SentMessageRepositoryInterface $sentMessageRepo,
        SequenceScheduleRepositoryInterface $sequenceScheduleRepo
    ) {
        $this->botRepo = $botRepo;
        $this->sequences = $sequences;
        $this->sequenceRepo = $sequenceRepo;
        $this->subscriberRepo = $subscriberRepo;
        $this->FacebookAdapter = $FacebookAdapter;
        $this->sentMessageRepo = $sentMessageRepo;
        $this->sequenceScheduleRepo = $sequenceScheduleRepo;
    }

    /**
     * @param      $id
     * @return \Common\Models\BaseModel|Subscriber
     */
    public function find($id)
    {
        return $this->subscriberRepo->findById($id);
    }

    /**
     * @param ObjectID $id
     * @param Bot      $bot
     * @return \Common\Models\BaseModel|Subscriber
     */
    public function findForBotOrFail(ObjectID $id, Bot $bot)
    {
        if ($subscriber = $this->subscriberRepo->findByIdForBot($id, $bot->_id)) {
            return $subscriber;
        }
        throw new NotFoundHttpException;
    }

    /**
     * Get or create a new subscriber to a given page.
     * @param string $id
     * @param Bot    $bot
     * @param bool   $isActive whether or not the user is actually an active subscriber or not.
     * @return Subscriber|null
     */
    public function getByFacebookIdOrCreate($id, Bot $bot, $isActive = false)
    {
        if ($subscriber = $this->subscriberRepo->findByFacebookIdForBot($id, $bot)) {
            return $subscriber;
        }

        $publicProfile = $this->FacebookAdapter->publicUserProfile($bot, $id);

        $data = [
            'facebook_id' => $id,
            'first_name'  => $publicProfile->first_name,
            'last_name'   => $publicProfile->last_name,
            'avatar_url'  => $publicProfile->profile_pic,
            'locale'      => $publicProfile->locale,
            'timezone'    => $publicProfile->timezone,
            'bot_id'      => $bot->_id,
            'tags'        => ['new'],
        ];

        if ($gender = object_get($publicProfile, 'gender')) {
            $data['gender'] = $gender;
        }

        if ($isActive) {
            $data['active'] = $isActive;
            $data['last_subscribed_at'] = Carbon::now();
        }
        /** @type Subscriber $subscriber */
        $subscriber = $this->subscriberRepo->create($data);

        return $subscriber;
    }

    /**
     * Make a subscriber "active"
     *
     * @param int $id the subscriber ID
     * @param Bot $bot
     */
    public function resubscribe($id, Bot $bot)
    {
        $subscriber = $this->subscriberRepo->findByFacebookIdForBot($id, $bot);
        $this->subscriberRepo->resubscribe($subscriber);
    }

    /**
     * Make a subscriber inactive.
     * @param Subscriber $subscriber
     * @return bool
     */
    public function unsubscribe(Subscriber $subscriber)
    {
        return $this->subscriberRepo->unsubscribe($subscriber);
    }

    /**
     * Return a list of filtered and sorted subscribers.
     * Subscribers may be filtered by simple attribute matching,
     * or by more complicated Filter Groups and Filter Rules (using logical and/or).
     *
     * @param Bot   $bot
     * @param int   $page
     * @param array $filterBy
     * @param array $orderBy
     * @param int   $perPage
     * @return Paginator
     */
    public function paginate(Bot $bot, $page = 1, $filterBy = [], $orderBy = [], $perPage = 20)
    {
        $ret = $this->subscriberRepo->paginateForBot(
            $bot,
            $page,
            $this->normalizeFilterBy($filterBy),
            $this->normalizeOrderBy($orderBy),
            $perPage
        );

        return $ret;
    }

    /**
     * @param Bot   $bot
     * @param array $filterBy
     * @return int
     */
    public function count(Bot $bot, $filterBy = [])
    {
        $followUp = array_get($filterBy, 'follow_up');
        $normalizedFilters = $this->normalizeFilterBy($filterBy);
        if ($followUp) {
            $filterBy = $this->normalizeFilterBy($filterBy);
            $subscribers = $this->subscriberRepo->getAllForBot($bot, $filterBy);

            return count($this->sentMessageRepo->followupFilter($subscribers));
        }

        return $this->subscriberRepo->countForBot($bot, $normalizedFilters);
    }

    /**
     * There are different "matching" techniques: exact matching, prefix, or date string (today, yesterday.. etc).
     * This method loops all over the filterBy array, make sure that the field is filterable, and return an array of filtering conditions.
     * A filtering condition has 3 parts:
     * 1. Type: [a]exact: exact match. [b]prefix: prefix match. [c]date: date lower & upper boundaries.
     * 2. Attribute: name of the attribute.
     * 3. Value: value to be matched against.
     *
     * @param array $filterBy
     *
     * @return array Array of the filtering conditions.
     */
    private function normalizeFilterBy(array $filterBy)
    {
        $ret = [];

        if ($filter = array_get($filterBy, 'filter', [])) {
            $ret[] = [
                'operator' => 'subscriber',
                'filter'   => new AudienceFilter($this->normalizeFilter($filter))
            ];
            $this->addActiveFilter($ret);
        }

        foreach ($filterBy as $key => $value) {

            if (! $this->fieldIsFilterable($key) || ($value !== '0' && ! $value)) {
                continue;
            }

            $operator = '=';

            $key = $this->filterFieldsMap[$key];

            if (in_array($key, ['first_name', 'last_name'])) {
                $operator = 'prefix';
            }

            if (in_array($key, ['created_at', 'last_interaction_at'])) {
                $operator = 'date';
            }

            if ($key === 'active') {
                $value = (bool)$value;
            }

            $ret[] = compact('operator', 'key', 'value');
        }

        return $ret;
    }

    /**
     * Normalize the filter groups by removing empty rules and empty groups.
     *
     * @param $filter
     *
     * @return mixed
     */
    private function normalizeFilter(array $filter)
    {
        if (! $filter) {
            return $filter;
        }

        if (! ($filter['enabled'] = (bool)array_get($filter, 'enabled', false))) {
            return array_only($filter, 'enabled');
        }

        $filter['groups'] = array_get($filter, 'groups', []);

        foreach ($filter['groups'] as $i => $group) {
            $filter['groups'][$i]['rules'] = $this->removeRulesWithoutValues($group['rules']);
            if ($filter['groups'][$i]['rules']) {
                $filter['groups'][$i]['join_type'] = array_get($filter['groups'][$i], 'join_type', 'and');
            }
        }

        // If a group is empty (has no rules), remove it.
        $filter['groups'] = array_filter($filter['groups'], function ($group) {
            return ! empty($group['rules']);
        });

        if ($filter['groups']) {
            $filter['join_type'] = array_get($filter, 'join_type', 'and');
        }

        return $filter;
    }

    /**
     * If a rule has no value, then remove it from the filter groups.
     *
     * @param $rules
     *
     * @return array
     */
    private function removeRulesWithoutValues($rules)
    {
        return array_filter($rules, function ($rule) {
            return ! empty($rule['value']);
        });
    }

    /**
     * @param array $filterBy
     */
    private function addActiveFilter(array &$filterBy)
    {
        $filterBy[] = [
            'operator' => '=',
            'key'      => 'active',
            'value'    => true
        ];
    }

    /**
     * Return an associative array of order fields.
     * Every key is the attribute to be sorted by, and the value is either "asc" / "desc"
     *
     * @param array $orderBy
     *
     * @return array
     */
    private function normalizeOrderBy(array $orderBy)
    {
        $ret = [];
        foreach ($orderBy as $attribute => $order) {
            if ($this->fieldIsFilterable($attribute)) {
                $attribute = $this->filterFieldsMap[$attribute];
                $ret[$attribute] = strtolower($order) == 'desc'? 'desc' : 'asc';
            }
        }

        return $ret;
    }

    /**
     * Update a subscriber.
     * @param array    $input
     * @param ObjectID $subscriberId
     * @param Bot      $bot
     * @return Subscriber
     */
    public function update(array $input, ObjectID $subscriberId, Bot $bot)
    {
        $subscriber = $this->findForBotOrFail($subscriberId, $bot);
        $this->subscriberRepo->update($subscriber, array_only($input, 'tags'));

        return $subscriber;
    }

    /**
     * Batch update subscribers.
     *
     * @param array $input
     * @param Bot   $bot
     */
    public function batchUpdate(array $input, Bot $bot)
    {
        $subscriberIds = array_map(function ($subscriber) {
            return new ObjectID($subscriber['id']);
        }, $input['subscribers']);

        if ($subscriberIds) {
            $this->subscriberRepo->bulkAddRemoveTagsAndSequences($bot, $subscriberIds, $input);
        }
    }

    /**
     * Return the number of active subscribers for a certain page.
     * @param Bot $bot
     * @return int
     */
    public function activeSubscribers(Bot $bot)
    {
        return $this->subscriberRepo->activeSubscriberCountForBot($bot);
    }

    /**
     * Return the total number of subscription actions in a given period of time.
     * Calculated as the difference between subscription and unsubscription actions.
     *
     * @param Bot           $bot
     * @param Carbon|string $date
     *
     * @return integer
     */
    public function totalSubscriptions(Bot $bot, $date)
    {
        $subscriptions = $this->subscriberRepo->subscriptionCountForBot($bot, $date);
        $unsubscriptions = $this->subscriberRepo->unsubscriptionCountForBot($bot, $date);

        return $subscriptions - $unsubscriptions;
    }

    /**
     * Count the number of subscribers who last unsubscribed in a given time period or on a specific date.
     *
     * @param Bot           $page
     * @param Carbon|string $date
     *
     * @return int
     */
    public function newSubscriptions(Bot $page, $date)
    {
        return $this->subscriberRepo->LastSubscribedAtCountForBot($date, $page);
    }

    /**
     * Count the number of subscribers who last unsubscribed in a given time period or on a specific date.
     *
     * @param Bot           $page
     * @param Carbon|string $date
     *
     * @return int
     */
    public function newUnsubscriptions(Bot $page, $date)
    {
        return $this->subscriberRepo->LastUnsubscribedAtCountForBot($date, $page);
    }

    /**
     * @param $attribute
     *
     * @return bool
     */
    private function fieldIsFilterable($attribute)
    {
        $allowed = in_array($attribute, array_keys($this->filterFieldsMap));

        return $allowed;
    }
}