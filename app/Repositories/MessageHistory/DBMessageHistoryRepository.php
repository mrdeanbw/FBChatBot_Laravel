<?php namespace App\Repositories\MessageInstance;

use Carbon\Carbon;
use App\Models\Subscriber;
use MongoDB\BSON\UTCDatetime;
use App\Models\MessageHistory;
use App\Repositories\DBAssociatedWithBotRepository;

class DBMessageHistoryRepository extends DBAssociatedWithBotRepository implements MessageHistoryRepositoryInterface
{

    /**
     * @return string
     */
    public function model()
    {
        return MessageHistory::class;
    }
    
    /**
     * Mark all messages sent to a subscriber before a specific date as delivered.
     * @param Subscriber $subscriber
     * @param Carbon     $dateTime
     */
    public function markAsDelivered(Subscriber $subscriber, Carbon $dateTime)
    {
        MessageHistory::where('subscriber_id', $subscriber->_id)->whereNull('delivered_at')->where('sent_at', '<=', $dateTime)->update([
            'delivered_at' => new UTCDateTime($dateTime->getTimestamp() * 1000)
        ]);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     * @param Subscriber $subscriber
     * @param Carbon     $dateTime
     */
    public function markAsRead(Subscriber $subscriber, Carbon $dateTime)
    {
        MessageHistory::where('subscriber_id', $subscriber->_id)->whereNull('read_at')->where('sent_at', '<=', $dateTime)->update([
            'read_at' => new UTCDateTime($dateTime->getTimestamp() * 1000)
        ]);
    }

}
