<?php namespace App\Repositories\MessageInstance;

use App\Models\MessageInstanceClick;
use App\Models\Bot;
use App\Models\Subscriber;
use App\Models\Message;
use App\Models\MessageInstance;

class EloquentMessageInstanceRepository implements MessageInstanceRepository
{

    /**
     * Create a new message block, and associate it with a given model.
     * @param array      $data
     * @param Message    $messageBlock
     * @param Subscriber $subscriber
     * @return MessageInstance
     */
    public function create(array $data, Message $messageBlock, Subscriber $subscriber)
    {
        $data['subscriber_id'] = $subscriber->id;
        $data['page_id'] = $messageBlock->page->id;

        return $messageBlock->instances()->create($data);
    }

    /**
     * Update an existing message block.
     * @param MessageInstance $messageInstance
     * @param array           $data
     * @return mixed
     */
    public function update(MessageInstance $messageInstance, array $data)
    {
        $messageInstance->update($data);
    }

    /**
     * Find a message instance that belongs to a certain page.
     * @param      $id
     * @param Bot  $page
     * @return MessageInstance|null
     */
    public function findByIdForPage($id, Bot $page)
    {
        return $page->messageInstances()->find($id);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as delivered.
     * @param Subscriber $subscriber
     * @param string     $dateTime
     */
    public function markAsDelivered(Subscriber $subscriber, $dateTime)
    {
        $subscriber->messageInstances()
                   ->where('delivered_at', null)
                   ->where('sent_at', '<=', $dateTime)
                   ->update(['delivered_at' => $dateTime]);
    }

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     * @param Subscriber $subscriber
     * @param string     $dateTime
     */
    public function markAsRead(Subscriber $subscriber, $dateTime)
    {
        $subscriber->messageInstances()
                   ->where('read_at', null)
                   ->where('sent_at', '<=', $dateTime)
                   ->update(['read_at' => $dateTime]);
    }

    /**
     * Create a message instance click for a message instance.
     * @param MessageInstance $instance
     * @return MessageInstanceClick
     */
    public function createMessageInstanceClick(MessageInstance $instance)
    {
        return $instance->clicks()->create([]);
    }
}
