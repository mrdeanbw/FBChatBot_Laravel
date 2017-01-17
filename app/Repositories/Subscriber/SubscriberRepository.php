<?php namespace App\Repositories\Subscriber;

use Carbon\Carbon;
use App\Models\Page;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;

interface SubscriberRepository
{
    
    /**
     * Find a subscriber by his artificial ID.
     * @param int $id
     * @return Subscriber|null
     */
    public function findById($id);

    /**
     * Find a page subscriber by his artificial ID.
     * @param int  $id
     * @param Page $page
     * @return Subscriber|null
     */
    public function findByIdForPage($id, Page $page);

    /**
     * Find a page subscriber by his Facebook ID.
     * @param int  $id
     * @param Page $page
     * @return Subscriber|null
     */
    public function findByFacebookIdForPage($id, Page $page);

    /**
     * Create a new subscriber.
     * @param array $data
     * @param Page  $page
     * @return Subscriber
     */
    public function create(array $data, Page $page);

    /**
     * Re-subscribe to the bot.
     * @param Subscriber $subscriber
     * @return Subscriber
     */
    public function resubscribe(Subscriber $subscriber);

    /**
     * Unsubscribe from the bot.
     * @param Subscriber $subscriber
     * @return Subscriber
     */
    public function unsubscribe(Subscriber $subscriber);

    /**
     * Count the number of active subscribers for a certain page.
     * @param Page $page
     * @return Subscriber
     */
    public function activeSubscriberCountForPage(Page $page);

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
    public function getAllForPage(array $filterGroups, $logicalOperator, $targetingIsEnabled, array $filterBy, array $orderBy, Page $page);

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
    public function paginateForPage(array $filterGroups, $logicalOperator, $targetingIsEnabled, array $filterBy, array $orderBy, Page $page, $perPage);

    /**
     * Count the number of subscribers matching given filtering criteria.
     * @param array  $filterGroups
     * @param string $logicalOperator
     * @param bool   $targetingIsEnabled
     * @param array  $filterBy
     * @param Page   $page
     * @return int
     */
    public function countForPage(array $filterGroups, $logicalOperator, $targetingIsEnabled, array $filterBy, Page $page);

    /**
     * Determine if a subscriber matches given filtering criteria.
     * @param Subscriber $subscriber
     * @param array      $filterGroups
     * @param string     $logicalOperator
     * @param bool       $targetingIsEnabled
     * @param array      $filterBy
     * @return bool
     */
    public function subscriberMatchesFilteringCriteria(Subscriber $subscriber, array $filterGroups, $logicalOperator, $targetingIsEnabled, array $filterBy);

    /**
     * Count the number of subscribers who last subscribed on a given date, or in a given time period.
     * @param Carbon|string $date
     * @param Page          $page
     * @return int
     */
    public function LastSubscribedAtCountForPage($date, Page $page);

    /**
     * Count the number of subscribers who last unsubscribed on a given date, or in a given time period.
     * @param Carbon|string $date
     * @param Page          $page
     * @return int
     */
    public function LastUnsubscribedAtCountForPage($date, Page $page);

    /**
     * Sync a subscriber's tags
     * @param Subscriber $subscriber
     * @param array      $tags
     * @param bool       $detaching Whether or not to detach the attached tags which are not included in the passed $tags
     */
    public function syncTags(Subscriber $subscriber, array $tags, $detaching = true);

    /**
     * Attach tags to subscriber.
     * @param Subscriber $subscriber
     * @param array      $tags
     * @param array      $attributes
     * @param bool       $touch
     */
    public function attachTags(Subscriber $subscriber, array $tags, array $attributes = [], $touch = true);

    /**
     * Detach tags from a subscriber.
     * @param Subscriber $subscriber
     * @param array      $tags
     * @param bool       $touch
     */
    public function detachTags(Subscriber $subscriber, array $tags, $touch = true);

    /**
     * Sync a subscriber's sequences
     * @param Subscriber $subscriber
     * @param array      $sequences
     * @param bool       $detaching Whether or not to detach the attached tags which are not included in the passed $tags
     */
    public function syncSequences(Subscriber $subscriber, array $sequences, $detaching = true);

    /**
     * Attach sequences to subscriber.
     * @param Subscriber $subscriber
     * @param array      $sequences
     * @param array      $attributes
     * @param bool       $touch
     */
    public function attachSequences(Subscriber $subscriber, array $sequences, array $attributes = [], $touch = true);

    /**
     * Detach sequences from a subscriber.
     * @param Subscriber $subscriber
     * @param array      $sequences
     * @param bool       $touch
     */
    public function detachSequences(Subscriber $subscriber, array $sequences, $touch = true);

}
