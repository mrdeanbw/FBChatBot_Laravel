<?php namespace App\Repositories\MessageHistory;

use Carbon\Carbon;
use App\Models\Subscriber;
use App\Repositories\AssociatedWithBotRepositoryInterface;

interface MessageHistoryRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

    /**
     * Mark all messages sent to a subscriber before a specific date as delivered.
     * @param Subscriber $subscriber
     * @param Carbon     $dateTime
     */
    public function markAsDelivered(Subscriber $subscriber, Carbon $dateTime);

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     * @param Subscriber $subscriber
     * @param Carbon     $dateTime
     */
    public function markAsRead(Subscriber $subscriber, Carbon $dateTime);
}
