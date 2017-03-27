<?php namespace Common\Repositories\SentMessage;

use Carbon\Carbon;
use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
use Common\Models\Subscriber;
use MongoDB\BSON\UTCDatetime;
use Common\Models\SentMessage;
use Illuminate\Support\Collection;
use Common\Repositories\AssociatedWithBotRepositoryInterface;

interface SentMessageRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

    /**
     * Mark all messages sent to a subscriber before a specific date as delivered.
     * @param Subscriber  $subscriber
     * @param UTCDatetime $dateTime
     */
    public function markAsDelivered(Subscriber $subscriber, UTCDatetime $dateTime);

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     * @param Subscriber  $subscriber
     * @param UTCDatetime $dateTime
     * @return
     */
    public function markAsRead(Subscriber $subscriber, UTCDatetime $dateTime);

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalSentForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberSentForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalDeliveredForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberDeliveredForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalReadForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberReadForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param ObjectID    $buttonId
     * @param ObjectID    $textMessageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalTextMessageButtonClicks(ObjectID $buttonId, ObjectID $textMessageId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param ObjectID    $buttonId
     * @param ObjectID    $textMessageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberTextMessageButtonClicks(ObjectID $buttonId, ObjectID $textMessageId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param ObjectID    $buttonId
     * @param ObjectID    $cardId
     * @param ObjectID    $cardContainerId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalCardButtonClicks(ObjectID $buttonId, ObjectID $cardId, ObjectID $cardContainerId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param ObjectID    $buttonId
     * @param ObjectID    $cardId
     * @param ObjectID    $cardContainerId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberCardButtonClicks(ObjectID $buttonId, ObjectID $cardId, ObjectID $cardContainerId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param SentMessage $sentMessage
     * @param array       $cardOrButtonPath
     * @param UTCDatetime $dateTime
     * @return
     */
    public function recordClick(SentMessage $sentMessage, array $cardOrButtonPath, UTCDatetime $dateTime);


    /**
     * @param ObjectID    $cardId
     * @param ObjectID    $cardContainerId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalCardClicks(ObjectID $cardId, ObjectID $cardContainerId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param ObjectID    $cardId
     * @param ObjectID    $cardContainerId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberCardClicks(ObjectID $cardId, ObjectID $cardContainerId, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param Bot         $bot
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalSentForBot(Bot $bot, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param Bot         $bot
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalMessageClicksForBot(Bot $bot, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param Bot         $bot
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberMessageClicksForBot(Bot $bot, Carbon $startDateTime = null, Carbon $endDateTime = null);

    /**
     * @param ObjectID $messageId
     * @param array    $columns
     * @return Collection
     */
    public function getAllForMessage(ObjectID $messageId, $columns = ['*']);

    /**
     * @param Subscriber $subscriber
     * @return bool
     */
    public function wasContacted24HoursAfterLastInteraction(Subscriber $subscriber);

    /**
     * @param Collection $subscribers
     * @return array
     */
    public function followupFilter(Collection $subscribers);
}
