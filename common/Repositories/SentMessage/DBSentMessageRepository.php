<?php namespace Common\Repositories\SentMessage;

use Carbon\Carbon;
use Common\Models\Bot;
use Common\Models\Message;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDatetime;
use Common\Models\Subscriber;
use Common\Models\SentMessage;
use Illuminate\Support\Collection;
use Common\Repositories\DBAssociatedWithBotRepository;

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
     * @param array       $cardOrButtonPath
     * @param UTCDatetime $dateTime
     */
    public function recordClick(SentMessage $sentMessage, array $cardOrButtonPath, UTCDatetime $dateTime)
    {
        $path = $this->normalizePath($sentMessage, $cardOrButtonPath) . '.clicks';
        $sentMessage->push($path, $dateTime);
    }

    /**
     * @param SentMessage|Message $model
     * @param array               $path
     * @return string
     * @throws \Exception
     */
    protected function normalizePath($model, array $path)
    {
        if (! $path) {
            return '';
        }

        $index = null;

        $container = $path[0];
        $messages = is_array($model)? $model[$container] : $model->{$container};

        foreach ($messages as $i => $message) {
            if ((string)$message['id'] == $path[1]) {
                $index = $i;
                break;
            }
        }

        if (is_null($index)) {
            throw new \Exception("Invalid button/card path");
        }

        $ret = "{$container}.{$index}";
        if ($temp = $this->normalizePath($messages[$index], array_slice($path, 2))) {
            $ret .= '.' . $temp;
        }

        return $ret;
    }

    /**
     * @param ObjectID    $messageId
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function totalSentForMessage(ObjectID $messageId, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $filter = [['key' => 'message_id', 'operator' => '=', 'value' => $messageId],];

        if ($startDateTime) {
            $filter[] = ['key' => 'sent_at', 'operator' => '>=', 'value' => $startDateTime];
        }

        if ($endDateTime) {
            $filter[] = ['key' => 'sent_at', 'operator' => '<', 'value' => $endDateTime];
        }

        if (! $startDateTime && ! $endDateTime) {
            $filter[] = ['key' => 'sent_at', 'operator' => '!=', 'value' => null];
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

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$ne' => null]];
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

        if (! $startDateTime && ! $endDateTime) {
            $filter[] = ['key' => 'delivered_at', 'operator' => '!=', 'value' => null];
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

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ['delivered_at' => ['$ne' => null]];
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

        if (! $startDateTime && ! $endDateTime) {
            $filter[] = ['key' => 'read_at', 'operator' => '!=', 'value' => null];
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

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ['read_at' => ['$ne' => null]];
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
        $matchFilters = [
            '$and' => [
                ['message_id' => $textMessageId],
                ['buttons.id' => $buttonId],
            ]
        ];

        if ($startDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$lt' => mongo_date($endDateTime)]];
        }

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$ne' => null]];
        }

        $aggregate = [
            ['$match' => $matchFilters],
            ['$project' => ['buttons' => 1]],
            ['$unwind' => '$buttons'],
            ['$match' => ['buttons.id' => $buttonId]],
            ['$group' => ['_id' => null, 'count' => ['$sum' => ['$size' => '$buttons.clicks']]]]
        ];

        $ret = SentMessage::raw()->aggregate($aggregate)->toArray();

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
        $matchFilters['$and'][] = ["buttons.id" => $buttonId];

        if ($startDateTime) {
            $matchFilters['$and'][] = ["buttons.clicks" => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ["buttons.clicks" => ['$lt' => mongo_date($endDateTime)]];
        }

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ["buttons.clicks.0" => ['$exists' => true]];
        }

        $aggregate = [
            ['$match' => ['message_id' => $textMessageId]],
            ['$project' => ['buttons' => 1, 'subscriber_id' => 1]],
            ['$unwind' => '$buttons'],
            ['$match' => $matchFilters],
            ['$group' => ['_id' => '$subscriber_id']],
            ['$group' => ['_id' => null, 'count' => ['$sum' => 1]]]
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
        $matchFilters = [
            '$and' => [
                ['message_id' => $cardContainerId],
                ["cards.id" => $cardId],
                ["cards.buttons.id" => $buttonId]
            ]
        ];

        if ($startDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$lt' => mongo_date($endDateTime)]];
        }

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$ne' => null]];
        }

        $filter = [
            ['$match' => $matchFilters],
            ['$project' => ['cards' => 1]],
            ['$unwind' => '$cards'],
            ['$match' => ['cards.id' => $cardId]],
            ['$unwind' => '$cards.buttons'],
            ['$match' => ['cards.buttons.id' => $buttonId]],
            ['$group' => ['_id' => null, 'count' => ['$sum' => ['$size' => '$cards.buttons.clicks']]]]
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
        $matchFilters = ['$and' => [["cards.buttons.id" => $buttonId]]];

        if ($startDateTime) {
            $matchFilters['$and'][] = ["cards.buttons.clicks" => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ["cards.buttons.clicks" => ['$lt' => mongo_date($endDateTime)]];
        }

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ["cards.buttons.clicks.0" => ['$exists' => true]];
        }

        $aggregate = [
            ['$match' => ['message_id' => $cardContainerId]],
            ['$project' => ['cards' => 1, 'subscriber_id' => 1]],
            ['$unwind' => '$cards'],
            ['$match' => ['cards.id' => $cardId]],
            ['$unwind' => '$cards.buttons'],
            ['$match' => $matchFilters],
            ['$group' => ['_id' => '$subscriber_id']],
            ['$group' => ['_id' => null, 'count' => ['$sum' => 1]]]
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
        $matchFilters = [
            '$and' => [
                ['message_id' => $cardContainerId],
                ['cards.id' => $cardId],
            ]
        ];

        if ($startDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$lt' => mongo_date($endDateTime)]];
        }

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$ne' => null]];
        }

        $aggregate = [
            ['$match' => $matchFilters],
            ['$project' => ['cards' => 1]],
            ['$unwind' => '$cards'],
            ['$match' => ['cards.id' => $cardId]],
            ['$group' => ['_id' => null, 'count' => ['$sum' => ['$size' => '$cards.clicks']]]]
        ];

        $ret = SentMessage::raw()->aggregate($aggregate)->toArray();

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
        $matchFilters['$and'][] = ["cards.id" => $cardId];

        if ($startDateTime) {
            $matchFilters['$and'][] = ["cards.clicks" => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ["cards.clicks" => ['$lt' => mongo_date($endDateTime)]];
        }

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ["cards.clicks.0" => ['$exists' => true]];
        }

        $aggregate = [
            ['$match' => ['message_id' => $cardContainerId]],
            ['$project' => ['cards' => 1, 'subscriber_id' => 1]],
            ['$unwind' => '$cards'],
            ['$match' => $matchFilters],
            ['$group' => ['_id' => '$subscriber_id']],
            ['$group' => ['_id' => null, 'count' => ['$sum' => 1]]]
        ];

        $result = SentMessage::raw()->aggregate($aggregate)->toArray();

        return count($result)? $result[0]->count : 0;
    }

    /**
     * @param Bot    $bot
     * @param Carbon $startDateTime
     * @param Carbon $endDateTime
     * @return int
     */
    public function totalMessageClicksForBot(Bot $bot, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $matchFilters = [
            '$and' => [
                ['bot_id' => $bot->_id],
                ['buttons' => ['$exists' => true]],
            ]
        ];

        if ($startDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$lt' => mongo_date($endDateTime)]];
        }

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$ne' => null]];
        }

        $filter = [
            ['$match' => $matchFilters],
            ['$project' => ['buttons' => 1]],
            ['$unwind' => '$buttons'],
            ['$group' => ['_id' => null, 'count' => ['$sum' => ['$size' => '$buttons.clicks']]]]
        ];

        $ret = SentMessage::raw()->aggregate($filter)->toArray();

        return count($ret)? $ret[0]->count : 0;
    }

    /**
     * @param Bot         $bot
     * @param Carbon|null $startDateTime
     * @param Carbon|null $endDateTime
     * @return int
     */
    public function perSubscriberMessageClicksForBot(Bot $bot, Carbon $startDateTime = null, Carbon $endDateTime = null)
    {
        $matchFilters = ['$and' => []];

        if ($startDateTime) {
            $matchFilters['$and'][] = ["buttons.clicks" => ['$gte' => mongo_date($startDateTime)]];
        }

        if ($endDateTime) {
            $matchFilters['$and'][] = ["buttons.clicks" => ['$lt' => mongo_date($endDateTime)]];
        }

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ["buttons.clicks.0" => ['$exists' => true]];
        }

        $aggregate = [
            ['$match' => ['$and' => [['bot_id' => $bot->_id], ['buttons' => ['$exists' => true]]]]],
            ['$project' => ['buttons' => 1, 'subscriber_id' => 1]],
            ['$unwind' => '$buttons'],
            ['$match' => $matchFilters],
            ['$group' => ['_id' => '$subscriber_id']],
            ['$group' => ['_id' => null, 'count' => ['$sum' => 1]]]
        ];

        $result = SentMessage::raw()->aggregate($aggregate)->toArray();

        return count($result)? $result[0]->count : 0;
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

        if (! $startDateTime && ! $endDateTime) {
            $matchFilters['$and'][] = ['sent_at' => ['$ne' => null]];
        }

        return $this->count($filters);
    }

    /**
     * @param ObjectID $messageId
     * @param array    $columns
     * @return Collection
     */
    public function getAllForMessage(ObjectID $messageId, $columns = ['*'])
    {
        $filter = [['key' => 'message_id', 'operator' => '=', 'value' => $messageId]];

        return $this->getAll($filter, [], $columns);
    }

    /**
     * @param Subscriber $subscriber
     * @return bool
     */
    public function wasContacted24HoursAfterLastInteraction(Subscriber $subscriber)
    {
        $copy = $subscriber->last_interaction_at->addDay(1);

        return SentMessage::where('subscriber_id', $subscriber->_id)->where('sent_at', '>=', $copy)->exists();
    }

    /**
     * @param Collection $subscribers
     * @return array
     */
    public function followupFilter(Collection $subscribers)
    {
        $subscriberIds = $subscribers->pluck('_id')->toArray();
        $subscribersCopy = $subscribers->keyBy('_id');

        $aggregate = [
            ['$sort' => ['subscriber_id' => 1, 'sent_at' => -1]],
            ['$match' => ['subscriber_id' => ['$in' => $subscriberIds]]],
            ['$group' => ['_id' => '$subscriber_id', 'sent_at' => ['$first' => '$sent_at']]],
        ];

        $result = SentMessage::raw()->aggregate($aggregate)->toArray();

        $ret = [];

        foreach ($result as $document) {
            $lastInteractionAt = $subscribersCopy->get((string)$document->_id)->last_interaction_at;
            $compare = $lastInteractionAt->copy()->addDay();
            $sentAt = carbon_date($document->sent_at);
             if ($sentAt->lt($compare)) {
                $ret[] = $document->_id;
            }
        }

        return $ret;
    }
}
