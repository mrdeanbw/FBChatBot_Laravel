<?php namespace App\Repositories\Subscriber;

use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Sequence;
use App\Models\Broadcast;
use App\Models\Subscriber;
use App\Models\AudienceFilter;
use Illuminate\Support\Collection;
use App\Models\AudienceFilterRule;
use App\Models\AudienceFilterGroup;
use App\Models\SubscriptionHistory;
use Jenssegers\Mongodb\Eloquent\Builder;
use App\Repositories\DBAssociatedWithBotRepository;

class DBSubscriberRepository extends DBAssociatedWithBotRepository implements SubscriberRepositoryInterface
{

    public function model()
    {
        return Subscriber::class;
    }

    /**
     * @param array $data
     *
     * @return Subscriber
     */
    public function create(array $data)
    {
        if ($data['active']) {
            $history = ['action' => SubscriberRepositoryInterface::ACTION_SUBSCRIBED, 'action_at' => mongo_date()];
            $data['history'] = [new SubscriptionHistory($history)];
        }

        return parent::create($data);
    }

    /**
     * @param Subscriber $model
     * @param array      $data
     *
     * @return bool
     */
    public function update($model, array $data)
    {
        if (array_get($data, 'active', null) !== null) {
            $history = null;

            if ($model->active && ! $data['active']) {
                $history = new SubscriptionHistory(['action' => SubscriberRepositoryInterface::ACTION_UNSUBSCRIBED, 'action_at' => mongo_date()]);
            }

            if (! $model->active && $data['active']) {
                $history = new SubscriptionHistory(['action' => SubscriberRepositoryInterface::ACTION_SUBSCRIBED, 'action_at' => mongo_date()]);
            }

            if ($history) {
                $data = [
                    '$set'  => $data,
                    '$push' => ['history' => $history]
                ];
            }
        }
        
        return parent::update($model, $data);
    }


    /**
     * Find a subscriber by his Facebook ID.
     *
     * @param int $id
     * @param Bot $bot
     *
     * @return Subscriber|null
     */
    public function findByFacebookIdForBot($id, Bot $bot)
    {
        $filter = [
            ['operator' => '=', 'key' => 'facebook_id', 'value' => $id],
            ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id],
        ];

        return $this->getOne($filter);
    }

    /**
     * Re-subscribe to the bot.
     *
     * @param Subscriber $subscriber
     *
     * @return bool
     */
    public function resubscribe(Subscriber $subscriber)
    {
        // User is already subscribed.
        if ($subscriber->active) {
            return $subscriber;
        }

        return $this->update($subscriber, [
            'active'             => true,
            'last_subscribed_at' => Carbon::now()
        ]);
    }

    /**
     * Unsubscribe from the bot.
     *
     * @param Subscriber $subscriber
     *
     * @return bool
     */
    public function unsubscribe(Subscriber $subscriber)
    {
        // User is already unsubscribed.
        if (! $subscriber->active) {
            return $subscriber;
        }

        return $this->update($subscriber, [
            'active'               => false,
            'last_unsubscribed_at' => Carbon::now()
        ]);
    }

    /**
     * Count the number of active subscribers for a certain page.
     *
     * @param Bot $bot
     *
     * @return Subscriber
     */
    public function activeSubscriberCountForBot(Bot $bot)
    {
        $filter = [
            ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id],
            ['operator' => '=', 'key' => 'active', 'value' => true]
        ];

        return $this->count($filter);
    }

    /**
     * @param Builder $query
     * @param array   $filter
     *
     * @return Builder
     */
    public function applyQueryFilter($query, array $filter)
    {
        if ($filter['operator'] === 'subscriber') {

            // If the filtering is not enabled. Then no subscribers should be matched.
            if (! $filter['filter']->enabled) {
                return $query->where('_id', -1);
            }

            return $this->applyFilterGroups($query, $filter['filter']);
        }

        return parent::applyQueryFilter($query, $filter);
    }

    /**
     * Chaining filter group queries on the initial query.
     *
     * @param Builder        $query
     * @param AudienceFilter $filter
     *
     * @return Builder
     */
    private function applyFilterGroups(Builder $query, AudienceFilter $filter)
    {
        $query->where(function ($query) use ($filter) {

            foreach ($filter->groups as $group) {
                $method = $this->getWhereMethodName($filter->join_type);
                $query->{$method}(function ($subQuery) use ($group) {
                    $this->applyFilterRules($subQuery, $group);
                });
            }
        });

        return $query;
    }

    /**
     * When chaining filter groups:
     * If the logical operator is "AND" then we use Builder's "where" method.
     * If the logical operator is "OR" then we use Builder's "orWhere" method.
     *
     * @param $logicalOperator
     *
     * @return string
     */
    private function getWhereMethodName($logicalOperator)
    {
        $methodPrefix = $logicalOperator == 'or'? 'or' : '';

        return "{$methodPrefix}Where";
    }

    /**
     * Chaining filter rule queries on the parent group query.
     *
     * @param Builder             $query
     * @param AudienceFilterGroup $group
     *
     * @return Builder
     */
    private function applyFilterRules(Builder $query, AudienceFilterGroup $group)
    {
        foreach ($group->rules as $rule) {
            if ($group->join_type == 'none') {
                $this->applyNegatedRuleFiltration($query, $rule);
            } else {
                $this->applyRuleFiltration($query, $rule, $group->join_type);
            }
        }

        return $query;
    }

    /**
     * Handle "And"/"Or" chaining operators.
     *
     * @param Builder            $query
     * @param AudienceFilterRule $rule
     * @param string             $joinType
     *
     * @return Builder
     */
    private function applyRuleFiltration(Builder $query, AudienceFilterRule $rule, $joinType)
    {
        switch ($rule->key) {

            case 'gender':
                $query->where('gender', '=', $rule->value, $joinType);
                break;

            case 'tag':
                $query->where('tags', '=', $rule->value, $joinType);
                break;

            case 'sequence':
                $query->where('sequences', '=', $rule->value, $joinType);
                break;
        }

        return $query;
    }

    /**
     * Handle "None" chaining operator
     *
     * @param Builder            $query
     * @param AudienceFilterRule $rule
     *
     * @return Builder
     */
    private function applyNegatedRuleFiltration(Builder $query, AudienceFilterRule $rule)
    {
        switch ($rule->key) {
            case 'gender':
                $query->where('gender', '!=', $rule->value);
                break;

            case 'tag':
                $query->where('tags', '!=', $rule->value);
                break;

            case 'sequence':
                $query->where('sequences', '!=', $rule->value);
                break;
        }

        return $query;
    }

    /**
     * @param Bot   $bot
     * @param array $subscriberIds
     * @param array $input
     */
    public function bulkAddRemoveTagsAndSequences(Bot $bot, array $subscriberIds, array $input)
    {
        // "MongoDB doesn’t allow multiple operations on the same property in the same update call.
        // This means that the two operations must happen in two individually atomic operations."
        // Therefore we can't do both "add_tags" and "remove_tags" in the same query.
        // The same goes for "subscribe_sequences" and "subscribe_sequences"

        $filter = [
            '$and' => [
                ['bot_id' => $bot->_id],
                ['_id' => ['$in' => $subscriberIds]]
            ]
        ];

        $update = $this->normalizeBatchUpdateArray($input);

        // We need 2 queries.
        if (($input['add_tags'] && $input['remove_tags']) || ($input['add_sequences'] && $input['remove_sequences'])) {

            Subscriber::raw(function ($collection) use ($filter, $update) {
                $collection->updateMany($filter, ['$addToSet' => $update['$addToSet']]);
            });

            Subscriber::raw(function ($collection) use ($filter, $update) {
                $collection->updateMany($filter, ['$pull' => $update['$pull']]);
            });

            return;
        }

        // 1 query is sufficient
        Subscriber::raw(function ($collection) use ($filter, $update) {
            $collection->updateMany($filter, $update);
        });
    }

    /**
     * Count the number of subscribers who last subscribed on a given date, or in a given time period.
     *
     * @param Carbon|string $date
     * @param Bot           $bot
     *
     * @return int
     */
    public function LastSubscribedAtCountForBot($date, Bot $bot)
    {
        $filter = [
            ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id],
            ['operator' => 'date', 'key' => 'last_subscribed_at', 'value' => $date],
        ];

        return $this->count($filter);
    }

    /**
     * Count the number of subscribers who last unsubscribed on a given date, or in a given time period.
     *
     * @param Carbon|string $date
     * @param Bot           $bot
     *
     * @return int
     */
    public function LastUnsubscribedAtCountForBot($date, Bot $bot)
    {
        $filter = [
            ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id],
            ['operator' => 'date', 'key' => 'last_unsubscribed_at', 'value' => $date],
        ];

        return $this->count($filter);
    }

    /**
     * @param array $input
     *
     * @return array
     */
    private function normalizeBatchUpdateArray(array $input)
    {
        $update = [];

        if ($input['add_tags']) {
            array_set($update, '$addToSet.tags.$each', $input['add_tags']);
        }

        if ($input['remove_tags']) {
            array_set($update, '$pull.tags.$in', $input['remove_tags']);
        }

        if ($input['add_sequences']) {
            array_set($update, '$addToSet.sequences.$each', $input['add_sequences']);
            array_set($update, '$pull.removed_sequences.$in', $input['add_sequences']);
        }

        if ($input['remove_sequences']) {
            array_set($update, '$pull.sequences.$in', $input['remove_sequences']);
            array_set($update, '$addToSet.removed_sequences.$each', $input['remove_sequences']);
        }

        return $update;
    }

    /**
     * Get an ordered list of all active subscribers matching some filtration criteria.
     *
     * @param Sequence|Broadcast $model
     * @param array              $filterBy
     * @param array              $orderBy
     *
     * @return \Illuminate\Support\Collection
     */
    public function getActiveTargetAudience($model, array $filterBy = [], array $orderBy = [])
    {
        $filterBy = array_merge([
            ['operator' => '=', 'key' => 'active', 'value' => true],
            ['operator' => '=', 'key' => 'bot_id', 'value' => $model->bot_id],
            ['operator' => 'subscriber', 'filter' => $model->filter],
        ], $filterBy);

        return $this->getAll($filterBy, $orderBy);
    }

    /**
     * The way it works:
     * We subscribe matching target audience. If a user has unsubscribed from this sequence, through an action:
     * button click, opt-in or manually from audience table, we shouldn't resubscribe him.
     * He can only resubscribe through an action.
     *
     * @param Sequence $sequence
     *
     * @return int the number of newly added subscribers
     *
     */
    public function subscribeToSequenceIfNotUnsubscribed(Sequence $sequence)
    {
        $filterBy = [
            ['operator' => 'subscriber', 'filter' => $sequence->filter],
            ['operator' => '=', 'key' => 'active', 'value' => true],
            ['operator' => '=', 'key' => 'bot_id', 'value' => $sequence->bot_id],
            ['operator' => '!=', 'key' => 'removed_sequences', 'value' => $sequence->_id]
        ];

        return $this->applyFilterByAndOrderBy($filterBy)->push('sequences', $sequence->_id, true);
    }

    /**
     * @param Bot           $bot
     * @param string|Carbon $date
     *
     * @return int
     */
    public function subscriptionCountForBot(Bot $bot, $date)
    {
        $filter = [
            ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id],
            ['operator' => '=', 'key' => 'history.action', 'value' => SubscriberRepositoryInterface::ACTION_SUBSCRIBED],
            ['operator' => 'date', 'key' => 'history.action_at', 'value' => $date]
        ];

        return $this->count($filter);
    }

    /**
     * @param Bot           $bot
     * @param string|Carbon $date
     *
     * @return int
     */
    public function unsubscriptionCountForBot(Bot $bot, $date)
    {
        $filter = [
            ['operator' => '=', 'key' => 'bot_id', 'value' => $bot->_id],
            ['operator' => '=', 'key' => 'history.action', 'value' => SubscriberRepositoryInterface::ACTION_UNSUBSCRIBED],
            ['operator' => 'date', 'key' => 'history.action_at', 'value' => $date]
        ];

        return $this->count($filter);
    }

    /**
     * Determine if a subscriber matches given filtering criteria.
     *
     * @param Subscriber     $subscriber
     * @param AudienceFilter $filter
     *
     * @return bool
     */
    public function subscriberMatchesRules(Subscriber $subscriber, AudienceFilter $filter)
    {
        $filterBy = [
            ['operator' => 'subscriber', 'filter' => $filter],
            ['operator' => '=', 'key' => '_id', 'value' => $subscriber->_id]
        ];

        return $this->count($filterBy) === 1;
    }

    /**
     * @param Subscriber $subscriber
     * @param array      $sequences
     */
    public function addSequences(Subscriber $subscriber, array $sequences)
    {
        $subscriber->push('sequences', $sequences, true);
    }

    /**
     * @param Sequence $sequence
     * @param array    $columns
     *
     * @return Collection
     */
    public function subscribersWhoShouldSubscribeToSequence(Sequence $sequence, $columns = ['_id'])
    {
        $filterBy = [
            ['operator' => 'subscriber', 'filter' => $sequence->filter],
            ['operator' => '=', 'key' => 'active', 'value' => true],
            ['operator' => '!=', 'key' => 'sequences', 'value' => $sequence->_id],
            ['operator' => '=', 'key' => 'bot_id', 'value' => $sequence->bot_id],
            ['operator' => '!=', 'key' => 'removed_sequences', 'value' => $sequence->_id]
        ];

        return $this->getAll($filterBy, [], $columns);
    }
}
