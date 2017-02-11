<?php namespace App\Services;

use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Sequence;
use App\Models\Subscriber;
use Illuminate\Pagination\Paginator;
use App\Services\Facebook\FacebookUser;
use App\Repositories\Bot\BotRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Repositories\Sequence\SequenceRepositoryInterface;
use App\Repositories\Subscriber\SubscriberRepositoryInterface;

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
        'last_contacted_at'   => 'last_contacted_at',
        'first_subscribed_at' => 'created_at',
    ];

    /**
     * @type FacebookUser
     */
    private $FacebookUsers;
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
     * AudienceService constructor.
     * @param SubscriberRepositoryInterface $subscriberRepo
     * @param SequenceRepositoryInterface   $sequenceRepo
     * @param FacebookUser                  $FacebookUsers
     * @param SequenceService               $sequences
     * @param BotRepositoryInterface        $botRepo
     */
    public function __construct(
        SubscriberRepositoryInterface $subscriberRepo,
        SequenceRepositoryInterface $sequenceRepo,
        FacebookUser $FacebookUsers,
        SequenceService $sequences,
        BotRepositoryInterface $botRepo
    ) {
        $this->FacebookUsers = $FacebookUsers;
        $this->subscriberRepo = $subscriberRepo;
        $this->sequenceRepo = $sequenceRepo;
        $this->sequences = $sequences;
        $this->botRepo = $botRepo;
    }

    /**
     * @param      $id
     * @return Subscriber
     */
    public function find($id)
    {
        return $this->subscriberRepo->findById($id);
    }

    /**
     * @param      $id
     * @param Bot  $bot
     * @return Subscriber
     */
    public function findForBotOrFail($id, Bot $bot)
    {
        if ($subscriber = $this->subscriberRepo->findByIdForBot($id, $bot)) {
            return $subscriber;
        }
        throw new ModelNotFoundException;
    }

    /**
     * @param      $id
     * @param Bot  $page
     * @return Subscriber|null
     */
    public function findByFacebookId($id, Bot $page)
    {
        return $this->subscriberRepo->findByFacebookIdForBot($id, $page);
    }

    /**
     * Get or create a new subscriber to a given page.
     * @param      $id
     * @param Bot  $bot
     * @param bool $isActive whether or not the user is actually an active subscriber or not.
     * @return Subscriber|null
     */
    public function getByFacebookIdOrCreate($id, Bot $bot, $isActive = false)
    {
        if ($subscriber = $this->findByFacebookId($id, $bot)) {
            return $subscriber;
        }

        $publicProfile = $this->FacebookUsers->publicProfile($id, $bot->page->access_token);

        $data = [
            'facebook_id'          => $id,
            'first_name'           => $publicProfile->first_name,
            'last_name'            => $publicProfile->last_name,
            'avatar_url'           => $publicProfile->profile_pic,
            'locale'               => $publicProfile->locale,
            'timezone'             => $publicProfile->timezone,
            'gender'               => $publicProfile->gender,
            'active'               => $isActive,
            'bot_id'               => $bot->_id,
            'last_subscribed_at'   => $isActive? Carbon::now() : null,
            'last_unsubscribed_at' => $isActive? Carbon::now() : null,
            'tags'                 => [],
            'sequences'            => [],
        ];

        return $this->subscriberRepo->create($data);
    }

    /**
     * Make a subscriber "active"
     * @param int $id the subscriber ID
     * @param Bot $page
     */
    public function resubscribe($id, Bot $page)
    {
        $subscriber = $this->findByFacebookId($id, $page);
        $this->subscriberRepo->resubscribe($subscriber);
    }

    /**
     * Make a subscriber inactive.
     * @param Subscriber $subscriber
     * @return Subscriber
     */
    public function unsubscribe(Subscriber $subscriber)
    {
        return $this->subscriberRepo->unsubscribe($subscriber);
    }

    /**
     * Return a list of filtered and sorted subscribers.
     * Subscribers may be filtered by simple attribute matching,
     * or by more complicated Filter Groups and Filter Rules (using logical and/or).
     * @param Bot   $bot
     * @param int   $page
     * @param array $filterBy
     * @param array $orderBy
     * @param int   $perPage
     * @return Paginator
     */
    public function paginate(Bot $bot, $page = 1, $filterBy = [], $orderBy = [], $perPage = 20)
    {
        return $this->subscriberRepo->paginateForBot(
            $bot,
            $page,
            $this->normalizeFilterBy($filterBy),
            $this->normalizeOrderBy($orderBy),
            $perPage
        );
    }


    /**
     * Normalize the filter groups by removing empty rules and empty groups.
     * @param $filter
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
     * @param $rules
     * @return array
     */
    private function removeRulesWithoutValues($rules)
    {
        return array_filter($rules, function ($rule) {
            return ! empty($rule['value']);
        });
    }

    /**
     * There are different "matching" techniques: exact matching, prefix, or date string (today, yesterday.. etc).
     * This method loops all over the filterBy array, make sure that the field is filterable, and return an array of filtering conditions.
     * A filtering condition has 3 parts:
     * 1. Type: [a]exact: exact match. [b]prefix: prefix match. [c]date: date lower & upper boundaries.
     * 2. Attribute: name of the attribute.
     * 3. Value: value to be matched against.
     * @param array $filterBy
     * @return array Array of the filtering conditions.
     */
    private function normalizeFilterBy(array $filterBy)
    {
        $ret = [];

        $filter = $this->normalizeFilter(array_get($filterBy, 'filter', []));

        if ($filter) {
            $ret[] = [
                'operator' => 'subscriber',
                'filter'   => $filter
            ];
        }

        foreach ($filterBy as $attribute => $value) {

            if (! $this->fieldIsFilterable($attribute) || ($value !== '0' && ! $value)) {
                continue;
            }

            $operator = '=';

            $attribute = $this->filterFieldsMap[$attribute];

            if (in_array($attribute, ['first_name', 'last_name'])) {
                $operator = 'prefix';
            }

            if (in_array($attribute, ['created_at', 'last_contacted_at'])) {
                $operator = 'date';
            }

            $ret[] = compact('operator', 'attribute', 'value');
        }

        $this->addActiveFilter($ret);

        return $ret;
    }

    /**
     * @param array $filterBy
     */
    private function addActiveFilter(array &$filterBy)
    {
        $filterBy[] = [
            'operator'  => '=',
            'attribute' => 'active',
            'value'     => true
        ];
    }

    /**
     * Return an associative array of order fields.
     * Every key is the attribute to be sorted by, and the value is either "asc" / "desc"
     * @param array $orderBy
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
     * @param array $input
     * @param int   $subscriberId
     * @param Bot   $bot
     */
    public function update(array $input, $subscriberId, Bot $bot)
    {
        $subscriber = $this->findForBotOrFail($subscriberId, $bot);
        $tags = $input['tags'];
        $this->botRepo->createTagsForBot($bot->_id, $tags);

        return $this->subscriberRepo->update($subscriber, compact('tags'));
    }

    /**
     * Batch update subscribers.
     * @param array $input
     * @param array $subscriberIds
     * @param Bot   $bot
     */
    public function batchUpdate(array $input, array $subscriberIds, Bot $bot)
    {
        if ($subscriberIds) {
            $this->botRepo->createTagsForBot($bot->_id, array_merge($input['add_tags'], $input['remove_tags']));
            $this->subscriberRepo->bulkUpdateForBot($bot, $subscriberIds, $input);
        }
    }

    /**
     * Determine whether or not a subscriber matches given filtration criteria.
     * @param Subscriber               $subscriber
     * @param HasFilterGroupsInterface $model
     * @param array                    $filterBy
     * @return bool
     */
    public function subscriberIsAmongActiveTargetAudience(Subscriber $subscriber, HasFilterGroupsInterface $model, array $filterBy = [])
    {
        $filterBy = $this->addActiveFilter($filterBy);

        $groups = $this->filterRepo->getFilterGroupsAndRulesForModel($model)->toArray();

        return $this->subscriberRepo->subscriberMatchesFilteringCriteria(
            $subscriber,
            $groups,
            $model->filter_type,
            $model->filter_enabled,
            $filterBy
        );
    }

    /**
     * Return the number of active subscribers for a certain page.
     * @param Bot $page
     * @return int
     */
    public function activeSubscribers(Bot $page)
    {
        return $this->subscriberRepo->activeSubscriberCountForPage($page);
    }

    /**
     * Return the total number of subscription actions in a given period of time.
     * Calculated as the difference between subscription and unsubscription actions.
     * @param Bot           $bot
     * @param Carbon|string $date
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
     * @param Bot           $page
     * @param Carbon|string $date
     * @return int
     */
    public function newSubscriptions(Bot $page, $date)
    {
        return $this->subscriberRepo->LastSubscribedAtCountForPage($date, $page);
    }

    /**
     * Count the number of subscribers who last unsubscribed in a given time period or on a specific date.
     * @param Bot           $page
     * @param Carbon|string $date
     * @return int
     */
    public function newUnsubscriptions(Bot $page, $date)
    {
        return $this->subscriberRepo->LastUnsubscribedAtCountForPage($date, $page);
    }


    /**
     * @param $attribute
     * @return bool
     */
    private function fieldIsFilterable($attribute)
    {
        $allowed = in_array($attribute, array_keys($this->filterFieldsMap));

        return $allowed;
    }

    /**
     * Sync subscriber tags with input.
     * @param Subscriber $subscriber
     * @param array      $tags
     * @param bool       $detaching
     */
    public function syncTags(Subscriber $subscriber, array $tags, $detaching = true)
    {
        $this->subscriberRepo->syncTags($subscriber, $tags, $detaching);
    }

    /**
     * Detach tags from a subscriber.
     * @param Subscriber $subscriber
     * @param array      $tags
     * @param bool       $touch
     */
    public function detachTags(Subscriber $subscriber, array $tags, $touch = true)
    {
        $this->subscriberRepo->detachTags($subscriber, $tags, $touch);
    }

    /**
     * Subscribe to a sequence, and schedule the first message in that sequence for sending.
     * @todo [Needs discussion] if resubscribing to sequence, should we send the sequence from the beginning, or we continue from where he unsubscribed.
     * @param Subscriber $subscriber
     * @param Sequence   $sequence
     */
    public function subscribeToSequence(Subscriber $subscriber, Sequence $sequence)
    {
        $this->subscriberRepo->attachSequences($subscriber, (array)$sequence);

        if ($message = $this->sequenceRepo->getFirstSequenceMessage($sequence)) {
            $this->sequences->scheduleMessage($message, $subscriber, Carbon::now());
        }
    }

    /**
     * Unsubscribe from a sequence. Delete any scheduled messages.
     * @param Subscriber $subscriber
     * @param Sequence   $sequence
     */
    public function unsubscribeFromSequence(Subscriber $subscriber, Sequence $sequence)
    {
        $this->sequenceRepo->deleteSequenceScheduledMessageForSubscriber($subscriber, $sequence);
        $this->subscriberRepo->detachSequences($subscriber, (array)$sequence);
    }
}