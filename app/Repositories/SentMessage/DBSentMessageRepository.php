<?php namespace App\Repositories\SentMessage;

use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Subscriber;
use MongoDB\BSON\ObjectID;
use App\Models\SentMessage;
use MongoDB\BSON\UTCDatetime;
use App\Repositories\DBAssociatedWithBotRepository;

class DBSentMessageRepository extends DBAssociatedWithBotRepository implements SentMessageRepositoryInterface
{

    /**
     * @return string
     */
    public function model()
    {
        return SentMessage::class;
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as delivered.
     * @param Subscriber  $subscriber
     * @param UTCDatetime $dateTime
     */
    public function markAsDelivered(Subscriber $subscriber, UTCDatetime $dateTime)
    {
        SentMessage::where('subscriber_id', $subscriber->_id)->whereNull('delivered_at')->where('sent_at', '<=', $dateTime)->update([
            'delivered_at' => $dateTime
        ]);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     * @param Subscriber  $subscriber
     * @param UTCDatetime $dateTime
     */
    public function markAsRead(Subscriber $subscriber, UTCDatetime $dateTime)
    {
        SentMessage::where('subscriber_id', $subscriber->_id)->whereNull('read_at')->where('sent_at', '<=', $dateTime)->update([
            'read_at' => $dateTime
        ]);

        //If delivered at is null, set to $dateTime
        SentMessage::where('subscriber_id', $subscriber->_id)->whereNotNull('read_at')->whereNull('delivered_at')->update([
            'delivered_at' => $dateTime
        ]);
    }

    /**
     * @param SentMessage $sentMessage
     * @param string      $cardOrButtonPath
     * @param UTCDatetime $dateTime
     */
    public function recordClick(SentMessage $sentMessage, $cardOrButtonPath, UTCDatetime $dateTime)
    {
        $sentMessage->push($cardOrButtonPath, $dateTime);
    }

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalSentForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $filter = [['key' => 'message_id', 'operator' => '=', 'value' => $messageId]];

        if ($startDateTime) {
            $filter[] = ['key' => 'sent_at', 'operator' => '>=', 'value' => $startDateTime];
        }

        if ($endDateTime) {
            $filter[] = ['key' => 'sent_at', 'operator' => '<', 'value' => $endDateTime];
        }

        return $this->count($filter);
    }

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberSentForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $matchFilters = ['$and' => [['message_id' => $messageId]]];

        if ($startDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$lt' => mongo_date($endDateTime)]];
        }

        $aggregate = [
            ['$match' => $matchFilters],
            ['$group' => ['_id' => '$subscriber_id']],
            ['$group' => ['_id' => 1, 'count' => ['$sum' => 1]]]
        ];

        $result = SentMessage::raw()->aggregate($aggregate)->toArray();

        return count($result)? $result[0]->count : 0;
    }

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalDeliveredForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $filter = [['key' => 'message_id', 'operator' => '=', 'value' => $messageId]];

        if ($startDateTime) {
            $filter[] = ['key' => 'delivered_at', 'operator' => '>=', 'value' => $startDateTime];
        }

        if ($endDateTime) {
            $filter[] = ['key' => 'delivered_at', 'operator' => '<', 'value' => $endDateTime];
        }

        return $this->count($filter);
    }

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberDeliveredForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $matchFilters = ['$and' => [['message_id' => $messageId]]];

        if ($startDateTime) {
            $matchFilters['$and'][] = ['delivered_at' => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ['delivered_at' => ['$lt' => mongo_date($endDateTime)]];
        }

        $aggregate = [
            ['$match' => $matchFilters],
            ['$group' => ['_id' => '$subscriber_id']],
            ['$group' => ['_id' => 1, 'count' => ['$sum' => 1]]]
        ];

        $result = SentMessage::raw()->aggregate($aggregate)->toArray();

        return count($result)? $result[0]->count : 0;
    }

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalReadForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $filter = [['key' => 'message_id', 'operator' => '=', 'value' => $messageId]];

        if ($startDateTime) {
            $filter[] = ['key' => 'read_at', 'operator' => '>=', 'value' => $startDateTime];
        }

        if ($endDateTime) {
            $filter[] = ['key' => 'read_at', 'operator' => '<', 'value' => $endDateTime];
        }

        return $this->count($filter);
    }

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberReadForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $matchFilters = ['$and' => [['message_id' => $messageId]]];

        if ($startDateTime) {
            $matchFilters['$and'][] = ['read_at' => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ['read_at' => ['$lt' => mongo_date($endDateTime)]];
        }

        $aggregate = [
            ['$match' => $matchFilters],
            ['$group' => ['_id' => '$subscriber_id']],
            ['$group' => ['_id' => 1, 'count' => ['$sum' => 1]]]
        ];

        $result = SentMessage::raw()->aggregate($aggregate)->toArray();

        return count($result)? $result[0]->count : 0;
    }

    /**
     * @param ObjectID    $buttonId
     * @param ObjectID    $textMessageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalTextMessageButtonClicks(ObjectID $buttonId, ObjectID $textMessageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $matchFilters = ['$and' => [['message_id' => $textMessageId]]];

        if ($startDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$lt' => mongo_date($endDateTime)]];
        }

        $filter = [
            ['$match' => $matchFilters],
            ['$group' => ['_id' => null, 'count' => ['$sum' => ['$size' => '$buttons.' . $buttonId]]]]
        ];

        $ret = SentMessage::raw()->aggregate($filter)->toArray();

        return count($ret)? $ret[0]->count : 0;
    }

    /**
     * @param ObjectID    $buttonId
     * @param ObjectID    $textMessageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberTextMessageButtonClicks(ObjectID $buttonId, ObjectID $textMessageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $matchFilters = ['$and' => [['message_id' => $textMessageId]]];

        if ($startDateTime) {
            $matchFilters['$and'][] = ["buttons.{$buttonId}" => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ["buttons.{$buttonId}" => ['$lt' => mongo_date($endDateTime)]];
        }

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ["buttons.{$buttonId}.0" => ['$exists' => true]];
        }

        $aggregate = [
            ['$match' => $matchFilters],
            ['$group' => ['_id' => '$subscriber_id']],
            ['$group' => ['_id' => 1, 'count' => ['$sum' => 1]]]
        ];

        $result = SentMessage::raw()->aggregate($aggregate)->toArray();

        return count($result)? $result[0]->count : 0;
    }

    /**
     * @param ObjectID    $buttonId
     * @param ObjectID    $cardId
     * @param ObjectID    $cardContainerId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalCardButtonClicks(ObjectID $buttonId, ObjectID $cardId, ObjectID $cardContainerId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $matchFilters = ['$and' => [['message_id' => $cardContainerId]]];

        if ($startDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$lt' => mongo_date($endDateTime)]];
        }

        $filter = [
            ['$match' => $matchFilters],
            ['$group' => ['_id' => null, 'count' => ['$sum' => ['$size' => '$cards.' . $cardId . '.buttons.' . $buttonId]]]]
        ];

        $ret = SentMessage::raw()->aggregate($filter)->toArray();

        return count($ret)? $ret[0]->count : 0;

    }

    /**
     * @param ObjectID    $buttonId
     * @param ObjectID    $cardId
     * @param ObjectID    $cardContainerId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberCardButtonClicks(ObjectID $buttonId, ObjectID $cardId, ObjectID $cardContainerId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $matchFilters = ['$and' => [['message_id' => $cardContainerId]]];

        if ($startDateTime) {
            $matchFilters['$and'][] = ["cards.{$cardId}.buttons.{$buttonId}" => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ["cards.{$cardId}.buttons.{$buttonId}" => ['$lt' => mongo_date($endDateTime)]];
        }

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ["cards.{$cardId}.buttons.{$buttonId}.0" => ['$exists' => true]];
        }

        $aggregate = [
            ['$match' => $matchFilters],
            ['$group' => ['_id' => '$subscriber_id']],
            ['$group' => ['_id' => 1, 'count' => ['$sum' => 1]]]
        ];

        $result = SentMessage::raw()->aggregate($aggregate)->toArray();

        return count($result)? $result[0]->count : 0;
    }

    /**
     * @param ObjectID    $cardId
     * @param ObjectID    $cardContainerId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalCardClicks(ObjectID $cardId, ObjectID $cardContainerId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $matchFilters = ['$and' => [['message_id' => $cardContainerId]]];

        if ($startDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$lt' => mongo_date($endDateTime)]];
        }

        $filter = [
            ['$match' => $matchFilters],
            ['$group' => ['_id' => null, 'count' => ['$sum' => ['$size' => '$cards.' . $cardId]]]]
        ];

        $ret = SentMessage::raw()->aggregate($filter)->toArray();

        return count($ret)? $ret[0]->count : 0;
    }

    /**
     * @param ObjectID    $cardId
     * @param ObjectID    $cardContainerId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberCardClicks(ObjectID $cardId, ObjectID $cardContainerId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $matchFilters = ['$and' => [['message_id' => $cardContainerId]]];

        if ($startDateTime) {
            $matchFilters['$and'][] = ["cards.{$cardId}" => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ["cards.{$cardId}" => ['$lt' => mongo_date($endDateTime)]];
        }

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ["cards.{$cardId}.0" => ['$exists' => true]];
        }

        $aggregate = [
            ['$match' => $matchFilters],
            ['$group' => ['_id' => '$subscriber_id']],
            ['$group' => ['_id' => 1, 'count' => ['$sum' => 1]]]
        ];

        $result = SentMessage::raw()->aggregate($aggregate)->toArray();

        return count($result)? $result[0]->count : 0;
    }

    /**
     * @todo implement function
     * @param Bot    $bot
     * @param Carbon $startDateTime
     * @param Carbon $endDateTime
     * @return int
     */
    public function totalMessageClicksForBot(Bot $bot, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
    }

    /**
     * @todo implement function
     * @param Bot         $bot
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberMessageClicksForBot(Bot $bot, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
    }

    /**
     * @param Bot         $bot
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalSentForBot(Bot $bot, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $filters = [['key' => 'bot_id', 'operator' => '=', 'value' => $bot->_id]];

        if ($startDateTime) {
            $filters[] = ['key' => 'sent_at', 'operator' => '>=', 'value' => $startDateTime];
        }

        if ($endDateTime) {
            $filters[] = ['key' => 'sent_at', 'operator' => '<', 'value' => $endDateTime];
        }

        return $this->count($filters);
    }
}
