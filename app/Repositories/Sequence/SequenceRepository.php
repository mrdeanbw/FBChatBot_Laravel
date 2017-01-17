<?php namespace App\Repositories\Sequence;

use App\Models\Page;
use App\Models\Sequence;
use App\Models\SequenceMessage;
use App\Models\SequenceMessageSchedule;
use App\Models\Subscriber;
use Illuminate\Support\Collection;

interface SequenceRepository
{

    /**
     * Return list of all sequences that belong to a page.
     * @param Page $page
     * @return Collection
     */
    public function getAllForPage(Page $page);

    /**
     * Return list of all sequences that are subscribed to by a subscriber.
     * @param Subscriber $subscriber
     * @return Collection
     */
    public function getAllForSubscriber(Subscriber $subscriber);

    /**
     * Get the first sequence message in a sequence.
     * @param $sequence
     * @return SequenceMessage|null
     */
    public function getFirstSequenceMessage(Sequence $sequence);

    /**
     * Get the last message in a sequence.
     * @param Sequence $sequence
     * @return SequenceMessage|null
     */
    public function getLastSequenceMessage(Sequence $sequence);


    /**
     * Get the next message in a sequence.
     * @param SequenceMessage $sequenceMessage
     * @return SequenceMessage|null
     */
    public function getNextSequenceMessage(SequenceMessage $sequenceMessage);

    /**
     * Delete all scheduled messages from a certain sequence for a specific subscriber.
     * @param Subscriber $subscriber
     * @param Sequence   $sequence
     */
    public function deleteSequenceScheduledMessageForSubscriber(Subscriber $subscriber, Sequence $sequence);

    /**
     * Find a sequence for a given page.
     * @param      $id
     * @param Page $page
     * @return Sequence|null
     */
    public function findByIdForPage($id, Page $page);

    /**
     * Update a sequence.
     * @param Sequence $sequence
     * @param array    $data
     */
    public function update(Sequence $sequence, array $data);

    /**
     * Create a new sequence.
     * @param array $data
     * @param Page  $page
     * @return Sequence
     */
    public function create(array $data, Page $page);

    /**
     * Create a message and attach it to sequence.
     * @param array    $data
     * @param Sequence $sequence
     * @return SequenceMessage
     */
    public function createMessage(array $data, Sequence $sequence);

    /**
     * Delete a sequence.
     * @param Sequence $sequence
     */
    public function delete(Sequence $sequence);

    /**
     * Find a sequence message by ID
     * @param int      $id
     * @param Sequence $sequence
     * @return SequenceMessage
     */
    public function findSequenceMessageById($id, Sequence $sequence);

    /**
     * Update a sequence message.
     * @param SequenceMessage $message
     * @param array           $data
     */
    public function updateMessage(SequenceMessage $message, array $data);

    /**
     * Delete a sequence message.
     * @param SequenceMessage $message
     * @param bool            $completely If set to false, the message will be trashed (using deleted_at)
     * @return
     */
    public function deleteMessage(SequenceMessage $message, $completely = false);


    /**
     * Return a collection of subscribers, who are subscribed to a sequence.
     * @param Sequence $sequence
     * @return Collection
     */
    public function getSequenceSubscribers(Sequence $sequence);

    /**
     * @param array           $data
     * @param SequenceMessage $message
     * @param Subscriber      $subscriber
     * @return SequenceMessageSchedule
     */
    public function createMessageSchedule(array $data, SequenceMessage $message, Subscriber $subscriber);


    /**
     * Get list of sending-due sequence message schedules
     * @return Collection
     */
    public function getDueMessageSchedule();

    /**
     * Return the sequence message associated with this schedule
     * @param SequenceMessageSchedule $schedule
     * @param bool                    $includingSoftDeleted whether or not to return the message if it has been soft deleted
     * @return SequenceMessage|null
     */
    public function getMessageFromSchedule(SequenceMessageSchedule $schedule, $includingSoftDeleted);

    /**
     * Update a sequence message schedule.
     * @param SequenceMessageSchedule $schedule
     * @param array                   $data
     */
    public function updateMessageSchedule(SequenceMessageSchedule $schedule, array $data);

    /**
     * Return the trashed (soft deleted) sequence messages which have no schedules.
     * @return Collection
     */
    public function getTrashedMessagesWithNoSchedules();

}
