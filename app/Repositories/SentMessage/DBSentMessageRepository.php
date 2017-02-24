<?php namespace App\Repositories\SentMessage;

use Carbon\Carbon;
use App\Models\Subscriber;
use MongoDB\BSON\ObjectID;
use App\Models\SentMessage;
use MongoDB\BSON\UTCDatetime;
use App\Repositories\DBAssociatedWithBotRepository;

class DBSentSentMessageRepository extends DBAssociatedWithBotRepository implements SentMessageRepositoryInterface
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
     * @param Subscriber $subscriber
     * @param UTCDatetime     $dateTime
     */
    public function markAsDelivered(Subscriber $subscriber, UTCDatetime $dateTime)
    {
        SentMessage::where('subscriber_id', $subscriber->_id)->whereNull('delivered_at')->where('sent_at', '<=', $dateTime)->update([
            'delivered_at' => $dateTime
        ]);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     * @param Subscriber $subscriber
     * @param UTCDatetime     $dateTime
     */
    public function markAsRead(Subscriber $subscriber, UTCDatetime $dateTime)
    {
        SentMessage::where('subscriber_id', $subscriber->_id)->whereNull('read_at')->where('sent_at', '<=', $dateTime)->update([
            'read_at' => $dateTime
        ]);
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
            $filter[] = ['key' => 'sent_at', 'operator' => '>=', $startDateTime];
        }

        if ($endDateTime) {
            $filter[] = ['key' => 'sent_at', 'operator' => '<', $endDateTime];
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
        $filter = [['key' => 'message_id', 'operator' => '=', 'value' => $messageId]];

        if ($startDateTime) {
            $filter[] = ['key' => 'sent_at', 'operator' => '>=', $startDateTime];
        }

        if ($endDateTime) {
            $filter[] = ['key' => 'sent_at', 'operator' => '<', $endDateTime];
        }

        return $this->applyFilterByAndOrderBy($filter, [])->groupBy('subscriber_id')->count();
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
            $filter[] = ['key' => 'delivered_at', 'operator' => '>=', $startDateTime];
        }

        if ($endDateTime) {
            $filter[] = ['key' => 'delivered_at', 'operator' => '<', $endDateTime];
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
        $filter = [['key' => 'message_id', 'operator' => '=', 'value' => $messageId]];

        if ($startDateTime) {
            $filter[] = ['key' => 'delivered_at', 'operator' => '>=', $startDateTime];
        }

        if ($endDateTime) {
            $filter[] = ['key' => 'delivered_at', 'operator' => '<', $endDateTime];
        }

        return $this->applyFilterByAndOrderBy($filter, [])->groupBy('subscriber_id')->count();
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
            $filter[] = ['key' => 'read_at', 'operator' => '>=', $startDateTime];
        }

        if ($endDateTime) {
            $filter[] = ['key' => 'read_at', 'operator' => '<', $endDateTime];
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
        $filter = [['key' => 'message_id', 'operator' => '=', 'value' => $messageId]];

        if ($startDateTime) {
            $filter[] = ['key' => 'read_at', 'operator' => '>=', $startDateTime];
        }

        if ($endDateTime) {
            $filter[] = ['key' => 'read_at', 'operator' => '<', $endDateTime];
        }

        return $this->applyFilterByAndOrderBy($filter, [])->groupBy('subscriber_id')->count();
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
        $filter = [['key' => 'message_id', 'operator' => '=', 'value' => $textMessageId]];

        $condFilters = ['$and' => []];
        $matchFilters = $startDateTime || $endDateTime? ["buttons.{$buttonId}" => []] : [];

        if ($startDateTime) {
            $filter[] = ['key' => "buttons.{$buttonId}", 'operator' => '>=', $startDateTime];
            $mongoDate = mongo_date($startDateTime);
            $matchFilters["buttons.{$buttonId}"]['$gte'] = $mongoDate;
            $condFilters['$and'][] = ['$gte' => $mongoDate];
        }

        if ($endDateTime) {
            $filter[] = ['key' => "buttons.{$buttonId}", 'operator' => '<', $endDateTime];
            $mongoDate = mongo_date($endDateTime);
            $matchFilters["buttons.{$buttonId}"]['$lt'] = $mongoDate;
            $condFilters['$and'][] = ['$lt' => $mongoDate];
        }

        if ($startDateTime) {
            $filter = [
                [
                    '$match' => [
                        '$and' => array_merge(['message_id' => $textMessageId], $matchFilters)
                    ]
                ],
                [
                    '$group' => [
                        '_id'   => null,
                        'count' => [
                            '$sum' => [
                                '$size' => [
                                    '$filter' => [
                                        'input' => '$buttons.' . $buttonId,
                                        'as'    => 'item',
                                        'cond'  => $condFilters
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        return SentMessage::raw()->aggregate($filter)->first()->count();
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
        $filter = [
            ['key' => 'message_id', 'operator' => '=', 'value' => $textMessageId],
            ['key' => "buttons.{$buttonId}", 'operator' => '>=', $startDateTime],
            ['key' => "buttons.{$buttonId}", 'operator' => '<', $endDateTime],
        ];

        return $this->applyFilterByAndOrderBy($filter, [])->groupBy('subscriber_id')->count();
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
        $filter = [['key' => 'message_id', 'operator' => '=', 'value' => $cardContainerId]];

        $condFilters = ['$and' => []];
        $matchFilters = $startDateTime || $endDateTime? ["cards.{$cardId}.buttons.{$buttonId}" => []] : [];

        if ($startDateTime) {
            $filter[] = ['key' => "cards.{$cardId}.buttons.{$buttonId}", 'operator' => '>=', $startDateTime];
            $mongoDate = mongo_date($startDateTime);
            $matchFilters["cards.{$cardId}.buttons.{$buttonId}"]['$gte'] = $mongoDate;
            $condFilters['$and'][] = ['$gte' => $mongoDate];
        }

        if ($endDateTime) {
            $filter[] = ['key' => "cards.{$cardId}.buttons.{$buttonId}", 'operator' => '<', $endDateTime];
            $mongoDate = mongo_date($endDateTime);
            $matchFilters["cards.{$cardId}.buttons.{$buttonId}"]['$lt'] = $mongoDate;
            $condFilters['$and'][] = ['$lt' => $mongoDate];
        }

        if ($startDateTime) {
            $filter = [
                [
                    '$match' => [
                        '$and' => array_merge(['message_id' => $cardContainerId], $matchFilters)
                    ]
                ],
                [
                    '$group' => [
                        '_id'   => null,
                        'count' => [
                            '$sum' => [
                                '$size' => [
                                    '$filter' => [
                                        'input' => '$cards.' . $cardId . '.buttons.' . $buttonId,
                                        'as'    => 'item',
                                        'cond'  => $condFilters
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        return SentMessage::raw()->aggregate($filter)->first()->count();
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
        $filter = [
            ['key' => 'message_id', 'operator' => '=', 'value' => $cardContainerId],
            ['key' => "cards.{$cardId}.buttons.{$buttonId}", 'operator' => '>=', $startDateTime],
            ['key' => "cards.{$cardId}.buttons.{$buttonId}", 'operator' => '<', $endDateTime],
        ];

        return $this->applyFilterByAndOrderBy($filter, [])->groupBy('subscriber_id')->count();
    }
}
