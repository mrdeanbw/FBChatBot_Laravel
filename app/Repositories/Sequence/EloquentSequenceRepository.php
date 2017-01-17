<?php namespace App\Repositories\Sequence;

use Carbon\Carbon;
use App\Models\Page;
use App\Models\Sequence;
use App\Models\Subscriber;
use App\Models\SequenceMessage;
use Illuminate\Support\Collection;
use App\Models\SequenceMessageSchedule;
use App\Repositories\BaseEloquentRepository;

class EloquentSequenceRepository extends BaseEloquentRepository implements SequenceRepository
{

    /**
     * Return list of all sequences that belong to a page.
     * @param Page $page
     * @return Collection
     */
    public function getAllForPage(Page $page)
    {
        return $page->sequences;
    }

    /**
     * Return list of all sequences that are subscribed to by a subscriber.
     * @param Subscriber $subscriber
     * @return Collection
     */
    public function getAllForSubscriber(Subscriber $subscriber)
    {
        return $subscriber->sequences;
    }

    /**
     * Get the first sequence message in a sequence.
     * @param $sequence
     * @return SequenceMessage|null
     */
    public function getFirstSequenceMessage(Sequence $sequence)
    {
        return $sequence->messages()->first();
    }

    /**
     * Get the last message in a sequence.
     * @param Sequence $sequence
     * @return SequenceMessage|null
     */
    public function getLastSequenceMessage(Sequence $sequence)
    {
        return $sequence->unorderedMessages()->orderBy('order', 'desc')->first();
    }

    /**
     * Get the next message in a sequence.
     * @param SequenceMessage $sequenceMessage
     * @return SequenceMessage|null
     */
    public function getNextSequenceMessage(SequenceMessage $sequenceMessage)
    {
        return $sequenceMessage->next();
    }

    /**
     * Delete all scheduled messages from a certain sequence for a specific subscriber.
     * @param Subscriber $subscriber
     * @param Sequence   $sequence
     */
    public function deleteSequenceScheduledMessageForSubscriber(Subscriber $subscriber, Sequence $sequence)
    {
        $subscriber->sequenceSchedules()->whereSequenceId($sequence->id)->whereStatus('pending')->delete();
    }

    /**
     * Find a sequence for a given page.
     * @param      $id
     * @param Page $page
     * @return Sequence|null
     */
    public function findByIdForPage($id, Page $page)
    {
        return $page->sequences()->find($id);
    }

    /**
     * Update a sequence.
     * @param Sequence $sequence
     * @param array    $data
     */
    public function update(Sequence $sequence, array $data)
    {
        $sequence->update($data);
    }

    /**
     * Create a new sequence.
     * @param array $data
     * @param Page  $page
     * @return Sequence
     */
    public function create(array $data, Page $page)
    {
        return $page->sequences()->create($data);
    }

    /**
     * Create a message and attach it to sequence.
     * @param array    $data
     * @param Sequence $sequence
     * @return SequenceMessage
     */
    public function createMessage(array $data, Sequence $sequence)
    {
        return $sequence->messages()->create($data);
    }

    /**
     * Delete a sequence.
     * @param Sequence $sequence
     */
    public function delete(Sequence $sequence)
    {
        $sequence->delete();
    }

    /**
     * Find a sequence message by ID
     * @param int      $id
     * @param Sequence $sequence
     * @return SequenceMessage
     */
    public function findSequenceMessageById($id, Sequence $sequence)
    {
        return $sequence->messages()->find($id);
    }

    /**
     * Update a sequence message.
     * @param SequenceMessage $message
     * @param array           $data
     */
    public function updateMessage(SequenceMessage $message, array $data)
    {
        $message->update($data);
    }

    /**
     * Delete a sequence message.
     * @param SequenceMessage $message
     * @param bool            $completely
     */
    public function deleteMessage(SequenceMessage $message, $completely = false)
    {
        if ($completely) {
            $message->forceDelete();
        } else {
            $message->delete();
        }
    }

    /**
     * Return a collection of subscribers, who are subscribed to a sequence.
     * @param Sequence $sequence
     * @return Collection
     */
    public function getSequenceSubscribers(Sequence $sequence)
    {
        return $sequence->subscribers;
    }

    /**
     * @param array           $data
     * @param SequenceMessage $message
     * @param Subscriber      $subscriber
     * @return SequenceMessageSchedule
     */
    public function createMessageSchedule(array $data, SequenceMessage $message, Subscriber $subscriber)
    {
        $data['subscriber_id'] = $subscriber->id;

        return $message->schedules()->create($data);
    }

    /**
     * Get list of sending-due sequence message schedules
     * @return Collection
     */
    public function getDueMessageSchedule()
    {
        return SequenceMessageSchedule::whereStatus('pending')->where('send_at', '<=', Carbon::now())->get();
    }

    /**
     * Return the sequence message associated with this schedule
     * @param SequenceMessageSchedule $schedule
     * @param bool                    $includingSoftDeleted whether or not to return the message if it has been soft deleted
     * @return SequenceMessage|null
     */
    public function getMessageFromSchedule(SequenceMessageSchedule $schedule, $includingSoftDeleted)
    {
        $query = $schedule->sequenceMessage();
        if ($includingSoftDeleted) {
            $query->withTrashed();
        }

        return $query->first();
    }

    /**
     * Update a sequence message schedule.
     * @param SequenceMessageSchedule $schedule
     * @param array                   $data
     */
    public function updateMessageSchedule(SequenceMessageSchedule $schedule, array $data)
    {
        $schedule->update($data);
    }

    /**
     * Return the trashed (soft deleted) sequence messages which have no schedules.
     * @return Collection
     */
    public function getTrashedMessagesWithNoSchedules()
    {
        $inCompleteSchedule = function ($query) {
            $query->where('status', '!=', 'completed');
        };

        return SequenceMessage::onlyTrashed()->whereHas('schedules', $inCompleteSchedule, '=', 0);
    }
}
