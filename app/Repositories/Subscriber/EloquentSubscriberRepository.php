<?php namespace App\Repositories\Subscriber;

use Carbon\Carbon;
use App\Models\Page;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use App\Repositories\BaseEloquentRepository;

class EloquentSubscriberRepository extends BaseEloquentRepository implements SubscriberRepository
{

    /**
     * Find a subscriber by his ID.
     * @param int  $id
     * @param Page $page
     * @return Subscriber|null
     */
    public function findByIdForPage($id, Page $page)
    {
        return $page->subscribers()->findOrFail($id);
    }

    /**
     * Find a subscriber by his Facebook ID.
     * @param int  $id
     * @param Page $page
     * @return Subscriber|null
     */
    public function findByFacebookIdForPage($id, Page $page)
    {
        return $page->subscribers()->whereFacebookId($id)->first();
    }

    /**
     * Create a new subscriber.
     * @param array $data
     * @param Page  $page
     * @return Subscriber
     */
    public function create(array $data, Page $page)
    {
        return $page->subscribers()->create($data);
    }

    /**
     * Re-subscribe to the bot.
     * @param Subscriber $subscriber
     * @return Subscriber
     */
    public function resubscribe(Subscriber $subscriber)
    {
        // User is already subscribed.
        if ($subscriber->is_active) {
            return $subscriber;
        }

        $subscriber->is_active = true;
        $subscriber->last_subscribed_at = Carbon::now();
        $subscriber->save();

        return $subscriber;
    }

    /**
     * Unsubscribe from the bot.
     * @param Subscriber $subscriber
     * @return Subscriber
     */
    public function unsubscribe(Subscriber $subscriber)
    {
        // User is already unsubscribed.
        if (! $subscriber->is_active) {
            return $subscriber;
        }

        $subscriber->is_active = false;
        $subscriber->last_unsubscribed_at = Carbon::now();
        $subscriber->save();

        return $subscriber;
    }

    /**
     * Count the number of active subscribers for a certain page.
     * @param Page $page
     * @return Subscriber
     */
    public function activeSubscriberCountForPage(Page $page)
    {
        return $page->subscribers()->whereIsActive(1)->count();
    }

    /**
     * Count the number of subscribers matching given filtering criteria.
     * @param array  $filterGroups
     * @param string $logicalOperator
     * @param bool   $targetingIsEnabled
     * @param array  $filterBy
     * @param Page   $page
     * @return int
     */
    public function countForPage(array $filterGroups, $logicalOperator, $targetingIsEnabled, array $filterBy, Page $page)
    {
        $query = $this->filter($filterGroups, $logicalOperator, $targetingIsEnabled, $filterBy, $page);

        return $query->count();
    }

    /**
     * Get an ordered list of all subscribers matching given criteria.
     * @param array  $filterGroups
     * @param string $logicalOperator
     * @param bool   $targetingIsEnabled
     * @param array  $filterBy
     * @param array  $orderBy
     * @param Page   $page
     * @return Collection
     */
    public function getAllForPage(array $filterGroups, $logicalOperator, $targetingIsEnabled, array $filterBy, array $orderBy, Page $page)
    {
        $query = $this->filter($filterGroups, $logicalOperator, $targetingIsEnabled, $filterBy, $page);

        return $query->get();
    }

    /**
     * Get a paginated ordered list of all subscribers matching given criteria.
     * @param array  $filterGroups
     * @param string $logicalOperator
     * @param bool   $targetingIsEnabled
     * @param array  $filterBy
     * @param array  $orderBy
     * @param Page   $page
     * @param int    $perPage
     * @return Paginator
     */
    public function paginateForPage(array $filterGroups, $logicalOperator, $targetingIsEnabled, array $filterBy, array $orderBy, Page $page, $perPage)
    {
        $query = $this->filter($filterGroups, $logicalOperator, $targetingIsEnabled, $filterBy, $page);

        foreach ($orderBy as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array  $filterGroups
     * @param string $logicalOperator
     * @param bool   $targetingIsEnabled
     * @param array  $filterBy
     * @param Page   $page
     * @return Builder
     */
    private function filter(array $filterGroups, $logicalOperator, $targetingIsEnabled, array $filterBy, Page $page)
    {
        // If audience targeting is not enabled, return an impossible query.
        if (! $targetingIsEnabled) {
            return Subscriber::whereId(-1);
        }

        // Page query.
        $query = Subscriber::wherePageId($page->id);

        // Apply the filter groups on the query.
        $this->applyFilterGroups($query, $filterGroups, $logicalOperator);

        // Apply the column filtering on the query.
        $this->applyColumnFiltering($query, $filterBy);

        return $query;
    }

    /**
     * Chaining filter group queries on the initial query.
     * @param Builder $query
     * @param array   $filterGroups
     * @param string  $logicalOperator
     * @return Builder
     */
    private function applyFilterGroups(Builder $query, array $filterGroups, $logicalOperator)
    {
        $query->where(function ($query) use ($filterGroups, $logicalOperator) {

            foreach ($filterGroups as $group) {
                $method = $this->getWhereMethodName($logicalOperator);

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
            if ($group['type'] == 'none') {
                $this->applyNegatedRuleFiltration($query, $rule);
            } else {
                $this->applyRuleFiltration($query, $rule, $group['type']);
            }
        }

        return $query;
    }

    /**
     * Handle "And"/"Or" chaining operators.
     * @param Builder $query
     * @param array   $rule
     * @param string  $logicalOperator
     * @return Builder
     */
    private function applyRuleFiltration(Builder $query, array $rule, $logicalOperator)
    {
        switch ($rule['key']) {

            case 'gender':
                $query->where('gender', '=', $rule['value'], $logicalOperator);
                break;

            case 'tag':
                $query->has('tags', '>=', 1, $logicalOperator, function ($tagQuery) use ($rule) {
                    $tagQuery->whereTag($rule['value']);
                });
                break;

            case 'sequence':
                $query->has('sequences', '>=', 1, $logicalOperator, function ($sequenceQuery) use ($rule) {
                    $sequenceQuery->whereId($rule['value']);
                });

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
                $query->has('tags', '<', 1, 'and', function ($tagQuery) use ($rule) {
                    $tagQuery->whereTag($rule['value']);
                });
                break;

            case 'sequence':
                $query->has('sequences', '<', 1, 'and', function ($sequenceQuery) use ($rule) {
                    $sequenceQuery->whereId($rule['value']);
                });
                break;
        }

        return $query;
    }

    /**
     * @param array   $filterBy with the following keys: type, attribute and value
     * @param Builder $query
     * @return Builder
     */
    private function applyColumnFiltering($query, array $filterBy)
    {
        foreach ($filterBy as $filter) {

            switch ($filter['type']) {
                case 'exact':
                    $query->where($filter['attribute'], '=', $filter['value']);
                    break;

                case 'prefix':
                    $query->where($filter['attribute'], 'LIKE', "{$filter['value']}%");
                    break;

                case 'date':
                    $query->date($filter['attribute'], $filter['value']);
                    break;
            }
        }

        return $query;
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
        )->whereId($subscriber->id)->exists();
    }

    /**
     * Count the number of subscribers who last subscribed on a given date, or in a given time period.
     * @param Carbon|string $date
     * @param Page          $page
     * @return int
     */
    public function LastSubscribedAtCountForPage($date, Page $page)
    {
        return $page->subscribers()->date('last_subscribed_at', $date)->count();
    }

    /**
     * Count the number of subscribers who last unsubscribed on a given date, or in a given time period.
     * @param Carbon|string $date
     * @param Page          $page
     * @return int
     */
    public function LastUnsubscribedAtCountForPage($date, Page $page)
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
     * Attach tags to subscriber.
     * @param Subscriber $subscriber
     * @param array      $tags
     * @param array      $attributes
     * @param bool       $touch
     */
    public function attachTags(Subscriber $subscriber, array $tags, array $attributes = [], $touch = true)
    {
        $subscriber->tags()->attach($tags, $attributes, $touch);
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
     * Sync a subscriber's sequences
     * @param Subscriber $subscriber
     * @param array      $sequences
     * @param bool       $detaching Whether or not to detach the attached tags which are not included in the passed $tags
     */
    public function syncSequences(Subscriber $subscriber, array $sequences, $detaching = true)
    {
        $subscriber->sequences()->sync($sequences, $detaching);
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
}
