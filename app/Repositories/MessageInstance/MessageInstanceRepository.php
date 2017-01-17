<?php namespace App\Repositories\MessageInstance;

use App\Models\MessageInstanceClick;
use App\Models\Page;
use App\Models\Subscriber;
use App\Models\MessageBlock;
use App\Models\MessageInstance;

interface MessageInstanceRepository
{

    /**
     * Create a new message block, and associate it with a given model.
     * @param array        $data
     * @param MessageBlock $messageBlock
     * @param Subscriber   $subscriber
     * @return MessageInstance
     */
    public function create(array $data, MessageBlock $messageBlock, Subscriber $subscriber);

    /**
     * Update an existing message block.
     * @param MessageInstance $messageInstance
     * @param array           $data
     * @return mixed
     */
    public function update(MessageInstance $messageInstance, array $data);


    /**
     * Find a message instance that belongs to a certain page.
     * @param      $id
     * @return MessageInstance|null
     */
    public function findById($id);

    /**
     * Find a message instance that belongs to a certain page.
     * @param      $id
     * @param Page $page
     * @return MessageInstance|null
     */
    public function findByIdForPage($id, Page $page);

    /**
     * Mark all messages sent to a subscriber before a specific date as delivered.
     * @param Subscriber $subscriber
     * @param string     $dateTime
     */
    public function markAsDelivered(Subscriber $subscriber, $dateTime);

    /**
     * Mark all messages sent to a subscriber before a specific date as read.
     * @param Subscriber $subscriber
     * @param string     $dateTime
     */
    public function markAsRead(Subscriber $subscriber, $dateTime);

    /**
     * Create a message instance click for a message instance.
     * @param MessageInstance $instance
     * @return MessageInstanceClick
     */
    public function createMessageInstanceClick(MessageInstance $instance);
}
