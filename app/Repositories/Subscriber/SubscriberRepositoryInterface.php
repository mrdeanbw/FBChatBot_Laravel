<?php namespace App\Repositories\Subscriber;

use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use App\Repositories\CommonRepositoryInterface;

interface SubscriberRepositoryInterface extends CommonRepositoryInterface
{

    /**
     * Find a bot subscriber by his artificial ID.
     * @param int $id
     * @param Bot $bot
     * @return Subscriber|null
     */
    public function findByIdForBot($id, Bot $bot);

    /**
     * Find a bot subscriber by his Facebook ID.
     * @param int $id
     * @param Bot $bot
     * @return Subscriber|null
     */
    public function findByFacebookIdForBot($id, Bot $bot);

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
     * @param Bot $page
     * @return Subscriber
     */
    public function activeSubscriberCountForPage(Bot $page);

    /**
     * Get an ordered list of all subscribers matching given criteria.
     * @param array  $filterGroups
     * @param string $logicalOperator
     * @param bool   $targetingIsEnabled
     * @param array  $filterBy
     * @param array  $orderBy
     * @param Bot    $page
     * @return Collection
     */
    public function getAllForPage(array $filterGroups, $logicalOperator, $targetingIsEnabled, array $filterBy, array $orderBy, Bot $page);

    /**
     * Get a paginated ordered list of all subscribers matching given criteria.
     * @param Bot   $bot
     * @param int   $page
     * @param array $filterBy
     * @param array $orderBy
     * @param int   $perPage
     * @return Paginator
     */
    public function paginateForBot(Bot $bot, $page, array $filterBy, array $orderBy, $perPage);

    /**
     * Count the number of subscribers matching given filtering criteria.
     * @param array  $filterGroups
     * @param string $logicalOperator
     * @param bool   $targetingIsEnabled
     * @param array  $filterBy
     * @param Bot    $page
     * @return int
     */
    public function countForPage(array $filterGroups, $logicalOperator, $targetingIsEnabled, array $filterBy, Bot $page);

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
     * @param Bot           $page
     * @return int
     */
    public function LastSubscribedAtCountForPage($date, Bot $page);

    /**
     * Count the number of subscribers who last unsubscribed on a given date, or in a given time period.
     * @param Carbon|string $date
     * @param Bot           $page
     * @return int
     */
    public function LastUnsubscribedAtCountForPage($date, Bot $page);

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
    
    /**
     * @param Bot   $bot
     * @param array $subscriberIds
     * @param array $input
     */
    public function bulkUpdateForBot(Bot $bot, array $subscriberIds, array $input);
}
