<?php namespace App\Services;

use App\Models\HasFilterGroupsInterface;
use App\Models\Page;
use App\Models\Subscriber;
use App\Services\TagService;
use App\Services\Facebook\Makana\FacebookUser;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

class AudienceService
{

    /**
     * @type FacebookUser
     */
    private $MakanaUser;
    /**
     * @type TagService
     */
    private $tags;

    public function __construct(FacebookUser $MakanaUser, TagService $tags)
    {
        $this->MakanaUser = $MakanaUser;
        $this->tags = $tags;
    }


    /**
     * @param      $id
     * @param Page $page
     * @param bool $isActive
     * @return Subscriber|null
     */
    public function persist($id, Page $page, $isActive = false)
    {
        /** @type Subscriber $subscriber */
        $subscriber = $page->subscribers()->firstOrNew(['facebook_id' => $id]);

        if ($subscriber->exists) {
            return $subscriber;
        }

        $profile = $this->MakanaUser->publicProfile($id, $page->access_token);

        $subscriber->first_name = $profile->first_name;
        $subscriber->last_name = $profile->last_name;
        $subscriber->avatar_url = $profile->profile_pic;
        $subscriber->locale = $profile->locale;
        $subscriber->timezone = $profile->timezone;
        $subscriber->gender = $profile->gender;
        $subscriber->is_active = $isActive;
        if ($isActive) {
            $subscriber->last_subscribed_at = Carbon::now();
        }
        $subscriber->save();

        return $subscriber;
    }


    public function subscribe($id, $page)
    {
        $subscriber = $this->findByFacebookId($id, $page);
        $subscriber->is_active = true;
        $subscriber->last_subscribed_at = Carbon::now();
        $subscriber->save();
    }

    public function unsubscribe(Subscriber $subscriber)
    {
        $subscriber->is_active = false;
        $subscriber->last_unsubscribed_at = Carbon::now();
        $subscriber->save();
    }

    /**
     * @param      $id
     * @param Page $page
     * @return Subscriber
     */
    public function find($id, Page $page)
    {
        return $page->subscribers()->findOrFail($id);
    }

    /**
     * @param      $id
     * @param Page $page
     * @return Subscriber|null
     */
    public function findByFacebookId($id, Page $page)
    {
        return $page->subscribers()->whereFacebookId($id)->first();
    }

    /**
     * @param Page $page
     * @param      $perPage
     * @param      $filter
     * @param      $sorting
     * @return Paginator
     */
    public function paginate(Page $page, $perPage, $filter, $sorting)
    {
        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }

        //        \DB::enableQueryLog();


        $attributes = [
            'first_name'          => 'first_name',
            'last_name'           => 'last_name',
            'is_active'           => 'is_active',
            'first_subscribed_at' => 'created_at',
            'last_contacted_at'   => 'last_contacted_at',
            'gender'              => 'gender',
        ];

        $attributeKeys = array_keys($attributes);

        $filterGroups = $this->normalizeFilterGroups(array_get($filter, 'filter_groups', []));
        $filterType = array_get($filter, 'filter_type', 'and');
        $filterEnabled = array_get($filter, 'filter_enabled', true);
        $additionalFilters = $this->normalizeFilterAttributes($filter, $attributeKeys, $attributes);

        $query = $this->filterAudience($filterGroups, $filterType, $filterEnabled, $page, $additionalFilters);

        foreach ($sorting as $attribute => $order) {
            if (in_array($attribute, $attributeKeys)) {
                $query->orderBy($attributes[$attribute], $order);
            }
        }

        $ret = $query->paginate($perPage);

        //        \Log::debug(json_encode(\DB::getQueryLog()));

        return $ret;
    }

    private function normalizeFilterGroups($groups)
    {
        foreach ($groups as $i => $group) {
            $groups[$i]['rules'] = array_filter($group['rules'], function ($rule) {
                return ! empty($rule['value']);
            });
        }

        return $groups;
    }

    /**
     * @param Page $page
     * @return int
     */
    public function activeSubscribers(Page $page)
    {
        return $page->activeSubscribers()->count();
    }


    /**
     * @param Page $page
     * @param      $date
     * @return integer
     */
    public function deltaSubscriptions(Page $page, $date)
    {
        $subscriptions = $page->subscriptionHistory()->where('action', 'subscribed')->date('action_at', $date)->count();
        $unsubscriptions = $page->subscriptionHistory()->where('action', 'unsubscribed')->date('action_at', $date)->count();

        return $subscriptions - $unsubscriptions;
    }

    /**
     * @param Page $page
     * @param      $date
     */
    public function totalSubscriptions(Page $page, Carbon $date)
    {
        $subscriptions = $page->subscriptionHistory()->where('action', 'subscribed')->date('action_at', $date)->count();
        $unsubscriptions = $page->subscriptionHistory()->where('action', 'unsubscribed')->date('action_at', $date)->count();

        return $subscriptions - $unsubscriptions;
    }

    /**
     * @param $page
     * @param $date
     * @return int
     */
    public function newSubscriptions(Page $page, $date)
    {
        return $page->subscribers()->date('last_subscribed_at', $date)->count();
    }

    /**
     * @param $page
     * @param $date
     * @return int
     */
    public function newUnsubscriptions(Page $page, $date)
    {
        return $page->subscribers()->date('last_unsubscribed_at', $date)->count();
    }

    /**
     * @param      $input
     * @param      $id
     * @param Page $page
     */
    public function update($input, $id, Page $page)
    {
        $subscriber = $this->find($id, $page);

        DB::beginTransaction();
        //        $subscriber->sequences()->sync(extract_attribute($input['sequences']));
        $subscriber->syncTags($this->tags->createTags($input['tags'], $page));
        DB::commit();
    }

    /**
     * @param      $input
     * @param      $subscriberIds
     * @param Page $page
     */
    public function batchUpdate($input, $subscriberIds, Page $page)
    {
        DB::beginTransaction();

        //        $subscribe = extract_attribute($input['subscribe']);
        //        $unsubscribe = extract_attribute($input['unsubscribe']);
        $tag = $this->tags->createTags($input['tag'], $page);
        $untag = $this->tags->createTags($input['untag'], $page);

        //        \Log::debug(json_encode($subscriberIds));
        //        \Log::debug(json_encode($subscribe));
        //        \Log::debug(json_encode($unsubscribe));
        //        \Log::debug(json_encode($tag));
        //        \Log::debug(json_encode($untag));

        foreach ($subscriberIds as $subscriberId) {
            $subscriber = $this->find($subscriberId, $page);
            //            if ($subscribe) {
            //                $subscriber->sequences()->sync($subscribe, false);
            //            }
            //            if ($unsubscribe) {
            //                $subscriber->sequences()->sync($unsubscribe);
            //            }
            if ($tag) {
                $subscriber->syncTags($tag, false);
            }
            if ($untag) {
                $subscriber->detachTags($untag);
            }
        }
        //        \Log::debug(json_encode(\DB::getQueryLog()));

        DB::commit();
    }

    /**
     * @param HasFilterGroupsInterface $model
     * @param array                    $additionalFilters
     * @return Builder
     */
    public function targetAudienceQuery(HasFilterGroupsInterface $model, $additionalFilters = [['where', 'is_active', '=', 1]])
    {
        $groups = $model->filter_groups()->with('rules')->get()->toArray();

        return $this->filterAudience($groups, $model->filter_type, $model->filter_enabled, $model->page, $additionalFilters);
    }

    /**
     * @param       $filterGroups
     * @param       $filterType
     * @param       $filterEnabled
     * @param Page  $page
     * @param array $additionalFilters
     * @return Builder
     */
    public function filterAudience($filterGroups, $filterType, $filterEnabled, Page $page, $additionalFilters = [['where', 'is_active', '=', 1]])
    {
        if (! $filterEnabled) {
            // nothing.
            return Subscriber::whereId(-1);
        }

        $query = Subscriber::wherePageId($page->id)->where(function ($query) use ($filterGroups, $filterType) {
            foreach ($filterGroups as $group) {
                $query->{$this->getFilterWherePrefix($filterType)}($this->filterAudienceGroups($group));
            }
        });

        foreach ($additionalFilters as $filter) {
            $methodName = array_shift($filter);
            $query = call_user_func_array([$query, $methodName], $filter);
        }

        return $query;
    }

    /**
     * @param $group
     * @return \Closure
     */
    private function filterAudienceGroups($group)
    {
        return function (Builder $query) use ($group) {
            foreach ($group['rules'] as $rule) {
                $query = $group['type'] == 'none'? $this->negativeFilterRule($query, $rule) : $this->positiveFilterRule($query, $rule, $group['type']);
            }
        };
    }


    /**
     * @param Builder $query
     * @param array   $rule
     * @param         $type
     * @return Builder
     */
    public function positiveFilterRule($query, $rule, $type)
    {
        switch ($rule['key']) {

            case 'gender':
                $query->where('gender', '=', $rule['value'], $type);
                break;

            case 'tag':
                $query->has('tags', '>=', 1, $type, function ($tagQuery) use ($rule) {
                    /** @type \App\Models\Tag $tagQuery */
                    $tagQuery->whereTag($rule['value']);
                });
                break;

            case 'sequence':
                $query->has('sequences', '>=', 1, $type, function ($sequenceQuery) use ($rule) {
                    /** @type \App\Models\Sequence $sequenceQuery */
                    $sequenceQuery->whereId($rule['value']);
                });

                break;
        }

        return $query;
    }

    /**
     * @param Builder $query
     * @param array   $rule
     * @param string  $type
     * @return Builder
     */
    public function negativeFilterRule($query, $rule, $type = 'none')
    {
        switch ($rule['key']) {
            case 'gender':
                $query->where('gender', '!=', $rule['value']);
                break;

            case 'tag':
                $query->has('tags', '<', 1, 'and', function ($tagQuery) use ($rule) {
                    /** @type \App\Models\Tag $tagQuery */
                    $tagQuery->whereTag($rule['value']);
                });
                break;

            case 'sequence':
                $query->has('sequences', '<', 1, 'and', function ($sequenceQuery) use ($rule) {
                    /** @type \App\Models\Sequence $sequenceQuery */
                    $sequenceQuery->whereId($rule['value']);
                });
                break;
        }

        return $query;
    }


    /**
     * @param         $filter
     * @param         $attributeKeys
     * @param         $attributes
     * @return array
     */
    private function normalizeFilterAttributes($filter, $attributeKeys, $attributes)
    {
        $ret = [];

        foreach ($filter as $attribute => $value) {

            if (($value === '0' || $value) && in_array($attribute, $attributeKeys)) {

                $attribute = $attributes[$attribute];

                if (in_array($attribute, ['first_name', 'last_name'])) {
                    $ret[] = ['where', $attribute, 'LIKE', "{$value}%"];
                    continue;
                }

                if (in_array($attribute, ['created_at', 'last_contacted_at'])) {
                    $ret[] = ['date', $attribute, $value];
                    continue;
                }

                $ret[] = ['where', $attribute, '=', $value];
            }

        }

        return $ret;
    }

    private function getFilterWherePrefix($filterType)
    {
        $methodPrefix = $filterType == 'or'? 'or' : '';

        return "{$methodPrefix}Where";
    }

}