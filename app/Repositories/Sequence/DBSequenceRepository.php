<?php namespace App\Repositories\Sequence;

use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Sequence;
use App\Models\Subscriber;
use App\Models\SequenceMessage;
use Illuminate\Support\Collection;
use App\Repositories\DBAssociatedWithBotRepository;

class DBSequenceBaseRepository extends DBAssociatedWithBotRepository implements SequenceRepositoryInterface
{

    public function model()
    {
        return Sequence::class;
    }
    
    /**
     * Create a message and attach it to sequence.
     * @param Sequence        $sequence
     * @param SequenceMessage $message
     * @return SequenceMessage
     */
    public function addMessageToSequence(Sequence $sequence, SequenceMessage $message)
    {
        $sequence->push('messages', $message);
    }

    /**
     * Update a sequence message.
     * @param Sequence        $sequence
     * @param SequenceMessage $message
     */
    public function updateSequenceMessage(Sequence $sequence, SequenceMessage $message)
    {
        Sequence::where('_id', $sequence->_id)->where('messages.id', $message->id)->update([
            'messages.$' => $message
        ]);
    }

    /**
     * Delete a sequence message.
     * @param Sequence        $sequence
     * @param SequenceMessage $message
     */
    public function deleteMessage(Sequence $sequence, SequenceMessage $message)
    {
        Sequence::where('_id', $sequence->_id)->where('messages.id', $message->id)->pull('messages', 'message.$');
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
     * Find a sequence message by ID
     * @param int      $id
     * @param Sequence $sequence
     * @return SequenceMessage
     */
    public function findSequenceMessageById($id, Sequence $sequence)
    {
        return array_first($sequence->messages, function (SequenceMessage $message) use ($id) {
            return $message->id->__toString() === $id;
        });
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
