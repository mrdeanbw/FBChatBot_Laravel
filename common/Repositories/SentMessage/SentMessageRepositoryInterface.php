<?php namespace Common\Repositories\SentMessage;

use Carbon\Carbon;
use Common\Models\Bot;
use Common\Models\Button;
use Common\Models\Card;
use Common\Models\Message;
use Common\Models\MessageRevision;
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
     * @param MessageRevision $revision
     * @return int
     */
    public function totalSentForRevision(MessageRevision $revision);

    /**
     * @param MessageRevision $revision
     * @return int
     */
    public function perSubscriberSentForRevision(MessageRevision $revision);

    /**
     * @param MessageRevision $revision
     * @return int
     */
    public function totalDeliveredForRevision(MessageRevision $revision);

    /**
     * @param MessageRevision $revision
     * @return int
     */
    public function perSubscriberDeliveredForRevision(MessageRevision $revision);

    /**
     * @param MessageRevision $revision
     * @return int
     */
    public function totalReadForRevision(MessageRevision $revision);

    /**
     * @param MessageRevision $revision
     * @return int
     */
    public function perSubscriberReadForRevision(MessageRevision $revision);

    /**
     * @param Button          $button
     * @param MessageRevision $revision
     * @return int
     */
    public function totalTextMessageButtonClicksForRevision(Button $button, MessageRevision $revision);

    /**
     * @param Button          $button
     * @param MessageRevision $revision
     * @return int
     */
    public function perSubscriberTextMessageButtonClicksForRevision(Button $button, MessageRevision $revision);

    /**
     * @param Button          $button
     * @param Card            $card
     * @param MessageRevision $revision
     * @return int
     */
    public function totalCardButtonClicksForRevision(Button $button, Card $card, MessageRevision $revision);

    /**
     * @param Button          $button
     * @param Card            $card
     * @param MessageRevision $revision
     * @return int
     */
    public function perSubscriberCardButtonClicksForRevision(Button $button, Card $card, MessageRevision $revision);

    /**
     * @param Card            $card
     * @param MessageRevision $revision
     * @return int
     */
    public function totalCardClicksForRevision(Card $card, MessageRevision $revision);

    /**
     * @param Card            $card
     * @param MessageRevision $revision
     * @return int
     */
    public function perSubscriberCardClicksForRevision(Card $card, MessageRevision $revision);

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
     * @param SentMessage $sentMessage
     * @param array       $cardOrButtonPath
     * @param UTCDatetime $dateTime
     * @return
     */
    public function recordClick(SentMessage $sentMessage, array $cardOrButtonPath, UTCDatetime $dateTime);

    /**
     * @param ObjectID $id
     * @param int      $cardIndex
     */
    public function recordCardClick(ObjectID $id, $cardIndex);

    /**
     * @param ObjectID $id
     * @param int      $buttonIndex
     */
    public function recordTextButtonClick(ObjectID $id, $buttonIndex);

    /**
     * @param ObjectID $id
     * @param int      $cardIndex
     * @param int      $buttonIndex
     * @return
     */
    public function recordCardButtonClick(ObjectID $id, $cardIndex, $buttonIndex);

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

    /**
     * @param Message $message
     * @return int
     */
    public function totalSentForMessage(Message $message);

    /**
     * @param Message $message
     * @return int
     */
    public function perSubscriberSentForMessage(Message $message);

    /**
     * @param Message $message
     * @return int
     */
    public function totalDeliveredForMessage(Message $message);

    /**
     * @param Message $message
     * @return int
     */
    public function perSubscriberDeliveredForMessage(Message $message);

    /**
     * @param Message $message
     * @return int
     */
    public function totalReadForMessage(Message $message);

    /**
     * @param Message $message
     * @return int
     */
    public function perSubscriberReadForMessage(Message $message);

    /**
     * @param Button  $button
     * @param Message $message
     * @return int
     */
    public function totalTextMessageButtonClicksForMessage(Button $button, Message $message);

    /**
     * @param Button  $button
     * @param Message $message
     * @return int
     */
    public function perSubscriberTextMessageButtonClicksForMessage(Button $button, Message $message);

    /**
     * @param Button  $button
     * @param Card    $card
     * @param Message $message
     * @return int
     */
    public function totalCardButtonClicksForMessage(Button $button, Card $card, Message $message);

    /**
     * @param Button  $button
     * @param Card    $card
     * @param Message $message
     * @return int
     */
    public function perSubscriberCardButtonClicksForMessage(Button $button, Card $card, Message $message);

    /**
     * @param Card    $card
     * @param Message $message
     * @return int
     */
    public function totalCardClicksForMessage(Card $card, Message $message);

    /**
     * @param Card    $card
     * @param Message $message
     * @return int
     */
    public function perSubscriberCardClicksForMessage(Card $card, Message $message);
}
