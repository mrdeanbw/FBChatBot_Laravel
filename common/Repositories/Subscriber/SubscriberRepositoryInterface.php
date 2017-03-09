<?php namespace Common\Repositories\Subscriber;

use Carbon\Carbon;
use Common\Models\Bot;
use Common\Models\Sequence;
use Common\Models\Broadcast;
use Common\Models\Subscriber;
use Common\Models\AudienceFilter;
use Illuminate\Support\Collection;
use Common\Repositories\AssociatedWithBotRepositoryInterface;

interface SubscriberRepositoryInterface extends AssociatedWithBotRepositoryInterface
{
    
    const ACTION_SUBSCRIBED = 1;
    const ACTION_UNSUBSCRIBED = 0;

    /**
     * Find a bot subscriber by his Facebook ID.
     *
     * @param int $id
     * @param Bot $bot
     *
     * @return Subscriber|null
     */
    public function findByFacebookIdForBot($id, Bot $bot);

    /**
     * Re-subscribe to the bot.
     *
     * @param Subscriber $subscriber
     *
     * @return bool
     */
    public function resubscribe(Subscriber $subscriber);

    /**
     * Unsubscribe from the bot.
     *
     * @param Subscriber $subscriber
     *
     * @return bool
     */
    public function unsubscribe(Subscriber $subscriber);

    /**
     * Count the number of active subscribers for a certain page.
     *
     * @param Bot $page
     *
     * @return Subscriber
     */
    public function activeSubscriberCountForBot(Bot $page);

    /**
     * Count the number of subscribers who last subscribed on a given date, or in a given time period.
     *
     * @param Carbon|string $date
     * @param Bot           $bot
     *
     * @return int
     */
    public function LastSubscribedAtCountForBot($date, Bot $bot);

    /**
     * Count the number of subscribers who last unsubscribed on a given date, or in a given time period.
     *
     * @param Carbon|string $date
     * @param Bot           $bot
     *
     * @return int
     */
    public function LastUnsubscribedAtCountForBot($date, Bot $bot);

    /**
     * @param Bot   $bot
     * @param array $subscriberIds
     * @param array $input
     */
    public function bulkAddRemoveTagsAndSequences(Bot $bot, array $subscriberIds, array $input);

    /**
     * @param Bot           $bot
     * @param string|Carbon $date
     *
     * @return int
     */
    public function subscriptionCountForBot(Bot $bot, $date);

    /**
     * @param Bot           $bot
     * @param string|Carbon $date
     *
     * @return int
     */
    public function unsubscriptionCountForBot(Bot $bot, $date);

    /**
     * @param Broadcast|Sequence $model
     * @param array              $filterBy
     * @param array              $orderBy
     *
     * @return Collection
     */
    public function getActiveTargetAudience($model, array $filterBy = [], array $orderBy = []);

    /**
     * @param Sequence $sequence
     *
     * @return int the number of newly added subscribers
     *
     */
    public function subscribeToSequenceIfNotUnsubscribed(Sequence $sequence);

    /**
     * Determine if a subscriber matches given filtering criteria.
     *
     * @param Subscriber     $subscriber
     * @param AudienceFilter $filter
     *
     * @return bool
     */
    public function subscriberMatchesRules(Subscriber $subscriber, AudienceFilter $filter);

    /**
     * @param Subscriber $subscriber
     * @param array      $sequences
     */
    public function addSequences(Subscriber $subscriber, array $sequences);

    /**
     * @param Sequence $sequence
     * @param array    $columns
     *
     * @return Collection
     */
    public function subscribersWhoShouldSubscribeToSequence(Sequence $sequence, $columns = ['_id']);
}
