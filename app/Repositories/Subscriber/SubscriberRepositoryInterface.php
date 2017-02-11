<?php namespace App\Repositories\Subscriber;

use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Sequence;
use App\Models\Broadcast;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use App\Repositories\AssociatedWithBotRepositoryInterface;

interface SubscriberRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

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
     * @return bool
     */
    public function resubscribe(Subscriber $subscriber);

    /**
     * Unsubscribe from the bot.
     * @param Subscriber $subscriber
     * @return bool
     */
    public function unsubscribe(Subscriber $subscriber);

    /**
     * Count the number of active subscribers for a certain page.
     * @param Bot $page
     * @return Subscriber
     */
    public function activeSubscriberCountForPage(Bot $page);

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


    /**
     * @param Bot           $bot
     * @param string|Carbon $date
     * @return int
     */
    public function subscriptionCountForBot(Bot $bot, $date);

    /**
     * @param Bot           $bot
     * @param string|Carbon $date
     * @return int
     */
    public function unsubscriptionCountForBot(Bot $bot, $date);

    /**
     * @param Broadcast|Sequence $model
     * @param array              $filterBy
     * @param array              $orderBy
     * @return Collection
     */
    public function getActiveTargetAudience($model, array $filterBy = [], array $orderBy = []);

}
