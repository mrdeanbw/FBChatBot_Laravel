<?php namespace App\Services;

use DB;
use Carbon\Carbon;
use App\Models\Page;
use App\Models\Sequence;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use App\Services\Facebook\FacebookUser;
use App\Models\HasFilterGroupsInterface;
use App\Repositories\Filter\FilterRepository;
use App\Repositories\Sequence\SequenceRepository;
use App\Repositories\Subscriber\SubscriberRepository;
use App\Repositories\Subscriber\SubscriberHistoryRepository;

class AudienceService
{

    /**
     * @type array
     */
    protected $filterFieldsMap = [
        'first_name'          => 'first_name',
        'last_name'           => 'last_name',
        'is_active'           => 'is_active',
        'gender'              => 'gender',
        'last_contacted_at'   => 'last_contacted_at',
        'first_subscribed_at' => 'created_at',
    ];

    /**
     * @type FacebookUser
     */
    private $FacebookUsers;
    /**
     * @type TagService
     */
    private $tags;
    /**
     * @type SubscriberRepository
     */
    private $subscriberRepo;
    /**
     * @type FilterRepository
     */
    private $filterRepo;
    /**
     * @type SubscriberHistoryRepository
     */
    private $subscriberHistoryRepo;
    /**
     * @type SequenceRepository
     */
    private $sequenceRepo;
    /**
     * @type SequenceService
     */
    private $sequences;

    /**
     * AudienceService constructor.
     * @param SubscriberRepository        $subscriberRepo
     * @param SubscriberHistoryRepository $subscriberHistoryRepo
     * @param SequenceRepository          $sequenceRepo
     * @param FilterRepository            $filterRepo
     * @param FacebookUser                $FacebookUsers
     * @param TagService                  $tags
     * @param SequenceService             $sequences
     */
    public function __construct(
        SubscriberRepository $subscriberRepo,
        SubscriberHistoryRepository $subscriberHistoryRepo,
        SequenceRepository $sequenceRepo,
        FilterRepository $filterRepo,
        FacebookUser $FacebookUsers,
        TagService $tags,
        SequenceService $sequences
    ) {
        $this->tags = $tags;
        $this->filterRepo = $filterRepo;
        $this->FacebookUsers = $FacebookUsers;
        $this->subscriberRepo = $subscriberRepo;
        $this->subscriberHistoryRepo = $subscriberHistoryRepo;
        $this->sequenceRepo = $sequenceRepo;
        $this->sequences = $sequences;
    }

    /**
     * @param      $id
     * @param Page $page
     * @return Subscriber
     */
    public function find($id, Page $page)
    {
        return $this->subscriberRepo->findByIdForPage($id, $page);
    }

    /**
     * @param      $id
     * @param Page $page
     * @return Subscriber|null
     */
    public function findByFacebookId($id, Page $page)
    {
        return $this->subscriberRepo->findByFacebookIdForPage($id, $page);
    }

    /**
     * Create a new subscriber to a given page.
     * @param      $id
     * @param Page $page
     * @param bool $isActive whether or not the user is actually an active subscriber or not.
     * @return Subscriber|null
     */
    public function persist($id, Page $page, $isActive = false)
    {
        if ($subscriber = $this->findByFacebookId($id, $page)) {
            return $subscriber;
        }

        $publicProfile = $this->FacebookUsers->publicProfile($id, $page->access_token);

        $data = [
            'facebook_id' => $id,
            'first_name'  => $publicProfile->first_name,
            'last_name'   => $publicProfile->last_name,
            'avatar_url'  => $publicProfile->profile_pic,
            'locale'      => $publicProfile->locale,
            'timezone'    => $publicProfile->timezone,
            'gender'      => $publicProfile->gender,
            'is_active'   => $isActive,
        ];

        if ($isActive) {
            $data['last_subscribed_at'] = Carbon::now();
        }

        return $this->subscriberRepo->create($data, $page);
    }

    /**
     * Make a subscriber "active"
     * @param int  $id the subscriber ID
     * @param Page $page
     * @return Subscriber
     */
    public function resubscribe($id, Page $page)
    {
        $subscriber = $this->findByFacebookId($id, $page);

        return $this->subscriberRepo->resubscribe($subscriber);
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
     * @param Page $page
     * @param      $perPage
     * @param      $filterBy
     * @param      $orderBy
     * @return Paginator
     */
    public function paginate(Page $page, $perPage, $filterBy, $orderBy)
    {
        $logicalOperator = array_get($filterBy, 'filter_type', 'and');
        $targetingIsEnabled = array_get($filterBy, 'filter_enabled', true);

        $filterGroups = $this->normalizeFilterGroups(
            array_get($filterBy, 'filter_groups', [])
        );

        return $this->subscriberRepo->paginateForPage(
            $filterGroups,
            $logicalOperator,
            $targetingIsEnabled,
            $this->normalizeFilterBy($filterBy),
            $this->normalizeOrderBy($orderBy),
            $page,
            $perPage
        );
    }


    /**
     * Normalize the filter groups by removing empty rules and empty groups.
     * @param $groups
     * @return mixed
     */
    private function normalizeFilterGroups($groups)
    {
        foreach ($groups as $i => $group) {
            $groups[$i]['rules'] = $this->removeRulesWithoutValues($group['rules']);
        }

        /**
         * If a group is empty (has no rules), remove it.
         */
        $groups = array_filter($groups, function ($group) {
            return ! empty($group['rules']);
        });

        return $groups;
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

        foreach ($filterBy as $attribute => $value) {

            if (! $this->fieldIsFilterable($attribute) || ($value !== '0' && ! $value)) {
                continue;
            }

            $type = 'exact';

            $attribute = $this->filterFieldsMap[$attribute];

            if (in_array($attribute, ['first_name', 'last_name'])) {
                $type = 'prefix';
            }

            if (in_array($attribute, ['created_at', 'last_contacted_at'])) {
                $type = 'date';
            }

            $ret[] = compact('type', 'attribute', 'value');
        }

        return $ret;
    }

    /**
     * Return an associative array of order fields.
     * Every key is the attribute to be sorted by, and the value is either "asc" / "desc"
     * @param $orderBy
     * @return array
     */
    private function normalizeOrderBy($orderBy)
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
     * Get an ordered list of all active subscribers matching some filtration criteria.
     * @param HasFilterGroupsInterface $model
     * @param array                    $filterBy
     * @param array                    $orderBy
     * @return Collection
     */
    public function getActiveTargetAudience(HasFilterGroupsInterface $model, array $filterBy = [], array $orderBy = [])
    {
        $filterBy = $this->addIsActiveFilter($filterBy);

        $groups = $this->filterRepo->getFilterGroupsAndRulesForModel($model)->toArray();

        return $this->subscriberRepo->getAllForPage(
            $groups,
            $model->filter_type,
            $model->filter_enabled,
            $filterBy,
            $orderBy,
            $model->page
        );
    }

    /**
     * Get the count of active subscribers matching some filtration criteria.
     * @param HasFilterGroupsInterface $model
     * @param array                    $filterBy
     * @return int
     */
    public function activeTargetAudienceCount(HasFilterGroupsInterface $model, array $filterBy = [])
    {
        $filterBy = $this->addIsActiveFilter($filterBy);

        $groups = $this->filterRepo->getFilterGroupsAndRulesForModel($model)->toArray();

        return $this->subscriberRepo->countForPage(
            $groups,
            $model->filter_type,
            $model->filter_enabled,
            $filterBy,
            $model->page
        );
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
        $filterBy = $this->addIsActiveFilter($filterBy);

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
     * @param Page $page
     * @return int
     */
    public function activeSubscribers(Page $page)
    {
        return $this->subscriberRepo->activeSubscriberCountForPage($page);
    }

    /**
     * Return the total number of subscription actions in a given period of time.
     * Calculated as the difference between subscription and unsubscription actions.
     * @param Page          $page
     * @param Carbon|string $date
     * @return integer
     */
    public function totalSubscriptions(Page $page, $date)
    {
        $subscriptions = $this->subscriberHistoryRepo->subscriptionCountForPage($date, $page);
        $unsubscriptions = $this->subscriberHistoryRepo->unsubscriptionCountForPage($date, $page);

        return $subscriptions - $unsubscriptions;
    }

    /**
     * Count the number of subscribers who last unsubscribed in a given time period or on a specific date.
     * @param Page          $page
     * @param Carbon|string $date
     * @return int
     */
    public function newSubscriptions(Page $page, $date)
    {
        return $this->subscriberRepo->LastSubscribedAtCountForPage($date, $page);
    }

    /**
     * Count the number of subscribers who last unsubscribed in a given time period or on a specific date.
     * @param Page          $page
     * @param Carbon|string $date
     * @return int
     */
    public function newUnsubscriptions(Page $page, $date)
    {
        return $this->subscriberRepo->LastUnsubscribedAtCountForPage($date, $page);
    }

    /**
     * @param array $input
     * @param int   $subscriberId
     * @param Page  $page
     */
    public function update(array $input, $subscriberId, Page $page)
    {
        $subscriber = $this->find($subscriberId, $page);

        DB::transaction(function () use ($subscriber, $input, $page) {
            $tags = $this->tags->getOrCreateTags($input['tags'], $page);
            $this->syncTags($subscriber, $tags);
        });
    }

    /**
     * @param array $input
     * @param array $subscriberIds
     * @param Page  $page
     */
    public function batchUpdate(array $input, array $subscriberIds, Page $page)
    {
        DB::transaction(function () use ($input, $subscriberIds, $page) {
            $tagsToAdd = $this->tags->getOrCreateTags($input['tag'], $page);
            $tagsToRemove = $this->tags->getOrCreateTags($input['untag'], $page);
            foreach ($subscriberIds as $subscriberId) {
                $subscriber = $this->find($subscriberId, $page);
                if ($tagsToAdd) {
                    $this->syncTags($subscriber, $tagsToAdd, false);
                }
                if ($tagsToRemove) {
                    $this->subscriberRepo->detachTags($subscriber, $tagsToRemove);
                }
            }
        });
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
     * @param array $filterBy
     * @return array
     */
    private function addIsActiveFilter(array $filterBy)
    {
        $filterBy[] = [
            'type'      => 'exact',
            'attribute' => 'is_active',
            'value'     => true
        ];

        return $filterBy;
    }

    /**
     * @param Subscriber $subscriber
     * @param array      $tags
     * @param bool       $detaching
     */
    public function syncTags(Subscriber $subscriber, array $tags, $detaching = true)
    {
        $this->subscriberRepo->syncTags($subscriber, $tags, $detaching);

        $this->reSyncSequences($subscriber);
    }


    /**
     * Re-sync a subscriber's sequences. i.e., subscribe him to matching sequences, and unsubscribe him from mismatching sequences.
     * @param Subscriber $subscriber
     */
    private function reSyncSequences(Subscriber $subscriber)
    {
        $allSequences = $this->sequenceRepo->getAllForPage($subscriber->page);
        $subscribedSequences = $this->sequenceRepo->getAllForSubscriber($subscriber);

        foreach ($allSequences as $sequence) {

            $isActuallySubscribed = $subscribedSequences->contains($sequence->id);
            $shouldSubscribe = $this->subscriberIsAmongActiveTargetAudience($subscriber, $sequence);

            /**
             * If the subscriber is not subscribed to a sequence that he should subscribe to, then subscribe him.
             */
            if ($shouldSubscribe && ! $isActuallySubscribed) {
                $this->subscribeToSequence($subscriber, $sequence);
            }

            /**
             * If the subscriber is actually subscribed to a sequence that he should not subscribe to, then unsubscribe him.
             */
            if (! $shouldSubscribe && $isActuallySubscribed) {
                $this->unsubscribeFromSequence($subscriber, $sequence);
            }
        }
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

    /**
     * @param Sequence $sequence
     */
    public function updateSequenceSubscribers(Sequence $sequence)
    {
        $oldAudience = $sequence->subscribers;
        $newAudience = $this->getActiveTargetAudience($sequence);

        foreach ($newAudience->diff($oldAudience) as $subscriber) {
            $this->subscribeToSequence($subscriber, $sequence);
        }

        foreach ($oldAudience->diff($newAudience) as $subscriber) {
            $this->unsubscribeFromSequence($subscriber, $sequence);
        }
    }
}