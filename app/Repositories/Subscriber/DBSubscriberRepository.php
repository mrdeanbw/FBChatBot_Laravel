<?php namespace App\Repositories\Subscriber;

use App\Models\Broadcast;
use App\Models\Sequence;
use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Subscriber;
use Illuminate\Database\Eloquent\Builder;
use App\Repositories\DBAssociatedWithBotRepository;

class DBSubscriberBaseRepository extends DBAssociatedWithBotRepository implements SubscriberRepositoryInterface
{

    public function model()
    {
        return Subscriber::class;
    }

    /**
     * Find a subscriber by his Facebook ID.
     * @param int $id
     * @param Bot $bot
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
     * @param Subscriber $subscriber
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
     * @param Subscriber $subscriber
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
     * @param Bot $bot
     * @return Subscriber
     */
    public function activeSubscriberCountForPage(Bot $bot)
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
     * @return Builder
     */
    public function applyQueryFilter($query, array $filter)
    {
        if ($filter['operator'] === 'subscriber') {

            // If the filtering is not enabled. Then no subscribers should be matched.
            if (! $filter['filter']['enabled']) {
                return $query->where('_id', -1);
            }

            $this->applyFilterGroups($query, $filter['filter']);
        }

        return parent::applyQueryFilter($query, $filter);
    }

    /**
     * Chaining filter group queries on the initial query.
     * @param Builder $query
     * @param array   $filter
     * @return Builder
     */
    private function applyFilterGroups(Builder $query, array $filter)
    {
        $query->where(function ($query) use ($filter) {

            foreach ($filter['groups'] as $group) {
                $method = $this->getWhereMethodName($filter['join_type']);
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
     * @param $logicalOperator
     * @return string
     */
    private function getWhereMethodName($logicalOperator)
    {
        $methodPrefix = $logicalOperator == 'or'? 'or' : '';

        return "{$methodPrefix}Where";
    }

    /**
     * Chaining filter rule queries on the parent group query.
     * @param Builder $query
     * @param array   $group
     * @return Builder
     */
    private function applyFilterRules(Builder $query, array $group)
    {
        foreach ($group['rules'] as $rule) {
            if ($group['join_type'] == 'none') {
                $this->applyNegatedRuleFiltration($query, $rule);
            } else {
                $this->applyRuleFiltration($query, $rule, $group['join_type']);
            }
        }

        return $query;
    }

    /**
     * Handle "And"/"Or" chaining operators.
     * @param Builder $query
     * @param array   $rule
     * @param string  $joinType
     * @return Builder
     */
    private function applyRuleFiltration(Builder $query, array $rule, $joinType)
    {
        switch ($rule['key']) {

            case 'gender':
                $query->where('gender', '=', $rule['value'], $joinType);
                break;

            case 'tag':
                $query->where('tags', '=', $rule['value'], $joinType);
                break;

            case 'sequence':
                $query->where('sequences', '=', $rule['value'], $joinType);
                break;
        }

        return $query;
    }

    /**
     * Handle "None" chaining operator
     * @param Builder $query
     * @param array   $rule
     * @return Builder
     */
    private function applyNegatedRuleFiltration(Builder $query, array $rule)
    {
        switch ($rule['key']) {
            case 'gender':
                $query->where('gender', '!=', $rule['value']);
                break;

            case 'tag':
                $query->where('tags', '!=', $rule['value']);
                break;

            case 'sequence':
                $query->where('sequences', '!=', $rule['value']);
                break;
        }

        return $query;
    }

    /**
     * @param Bot   $bot
     * @param array $subscriberIds
     * @param array $input
     */
    public function bulkUpdateForBot(Bot $bot, array $subscriberIds, array $input)
    {
        // "MongoDB doesn’t allow multiple operations on the same property in the same update call.
        // This means that the two operations must happen in two individually atomic operations."
        // Therefore we can't do both "add_tags" and "remove_tags" in the same query.
        // The same goes for "subscribe_sequences" and "subscribe_sequences"

        $filter = [
            '$and' => [
                ['bot_id' => $bot->_id],
                ['id' => ['$in' => $subscriberIds]]
            ]
        ];

        $update = $this->normalizeBatchUpdateArray($input);

        // We need 2 queries.
        if (($input['add_tags'] && $input['remove_tags']) || ($input['add_sequences'] && $input['remove_sequences'])) {

            Subscriber::raw(function ($collection) use ($filter, $update) {
                $collection->updateMany($filter, ['$push' => $update['$push']]);
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
     * Determine if a subscriber matches given filtering criteria.
     * @param Subscriber $subscriber
     * @param array      $filterGroups
     * @param string     $logicalOperator
     * @param bool       $targetingIsEnabled
     * @param array      $filterBy
     * @return bool
     */
    public function subscriberMatchesFilteringCriteria(Subscriber $subscriber, array $filterGroups, $logicalOperator, $targetingIsEnabled, array $filterBy)
    {
        return $this->filter(
            $filterGroups,
            $logicalOperator,
            $targetingIsEnabled,
            $filterBy,
            $subscriber->page
        )->where('_id', $subscriber->_id)->exists();
    }

    /**
     * Count the number of subscribers who last subscribed on a given date, or in a given time period.
     * @param Carbon|string $date
     * @param Bot           $page
     * @return int
     */
    public function LastSubscribedAtCountForPage($date, Bot $page)
    {
        return $page->subscribers()->date('last_subscribed_at', $date)->count();
    }

    /**
     * Count the number of subscribers who last unsubscribed on a given date, or in a given time period.
     * @param Carbon|string $date
     * @param Bot           $page
     * @return int
     */
    public function LastUnsubscribedAtCountForPage($date, Bot $page)
    {
        return $page->subscribers()->date('last_unsubscribed_at', $date)->count();
    }

    /**
     * Sync a subscriber's tags
     * @param Subscriber $subscriber
     * @param array      $tags
     * @param bool       $detaching
     * @return void
     */
    public function syncTags(Subscriber $subscriber, array $tags, $detaching = true)
    {
        $subscriber->tags()->sync($tags, $detaching);
    }

    /**
     * Detach tags from a subscriber.
     * @param Subscriber $subscriber
     * @param array      $tags
     * @param bool       $touch
     */
    public function detachTags(Subscriber $subscriber, array $tags, $touch = true)
    {
        $subscriber->tags()->detach($tags, $touch);
    }

    /**
     * Attach sequences to subscriber.
     * @param Subscriber $subscriber
     * @param array      $sequences
     * @param array      $attributes
     * @param bool       $touch
     */
    public function attachSequences(Subscriber $subscriber, array $sequences, array $attributes = [], $touch = true)
    {
        $subscriber->sequences()->attach($sequences, $attributes, $touch);
    }

    /**
     * Detach sequences from a subscriber.
     * @param Subscriber $subscriber
     * @param array      $sequences
     * @param bool       $touch
     */
    public function detachSequences(Subscriber $subscriber, array $sequences, $touch = true)
    {
        $subscriber->sequences()->detach($sequences, $touch);
    }

    /**
     * @param array $input
     * @return array
     */
    private function normalizeBatchUpdateArray(array $input)
    {
        $update = [];

        if ($input['add_tags']) {
            array_set($update, '$push.tags', $input['add_tags']);
        }

        if ($input['remove_tags']) {
            array_set($update, '$pull.tags', $input['remove_tags']);
        }

        if ($input['add_sequences']) {
            array_set($update, '$push.sequences', $input['add_sequences']);
        }

        if ($input['remove_sequences']) {
            array_set($update, '$pull.sequences', $input['remove_sequences']);
        }

        return $update;
    }


    /**
     * Get an ordered list of all active subscribers matching some filtration criteria.
     * @param Sequence|Broadcast $model
     * @param array              $filterBy
     * @param array              $orderBy
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
}
