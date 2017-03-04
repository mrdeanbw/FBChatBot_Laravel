<?php namespace App\Repositories\Sequence;

use Carbon\Carbon;
use App\Models\Sequence;
use MongoDB\BSON\ObjectID;
use App\Models\SequenceMessage;
use App\Repositories\DBAssociatedWithBotRepository;

class DBSequenceRepository extends DBAssociatedWithBotRepository implements SequenceRepositoryInterface
{

    public function model()
    {
        return Sequence::class;
    }

    /**
     * Create a message and attach it to sequence.
     *
     * @param Sequence        $sequence
     * @param SequenceMessage $message
     *
     * @return SequenceMessage
     */
    public function addMessageToSequence(Sequence $sequence, SequenceMessage $message)
    {
        $sequence->push('messages', $message);
    }

    /**
     * Update a sequence message.
     *
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
     *
     * @param Sequence        $sequence
     * @param SequenceMessage $message
     */
    public function deleteSequenceMessage(Sequence $sequence, SequenceMessage $message)
    {
        Sequence::where('_id', $sequence->_id)->pull('messages', ['id' => $message->id]);
    }

    /**
     * Delete a sequence message.
     *
     * @param Sequence        $sequence
     * @param SequenceMessage $message
     */
    public function softDeleteSequenceMessage(Sequence $sequence, SequenceMessage $message)
    {
        $message->deleted_at = Carbon::now();
        Sequence::where('_id', $sequence->_id)->where('messages.id', $message->id)->update([
            'messages.$.deleted_at' => mongo_date($message->deleted_at)
        ]);
    }

    /**
     * Find a sequence message by ID
     *
     * @param ObjectID $id
     * @param Sequence $sequence
     *
     * @return SequenceMessage
     */
    public function findSequenceMessageById(ObjectID $id, Sequence $sequence)
    {
        return array_first($sequence->messages, function (SequenceMessage $message) use ($id) {
            return $message->id == $id;
        });
    }

    public function completelyDeleteSoftDeletedSequenceMessagesWithNoPeopleQueued()
    {
        Sequence::raw(function ($collection) {
            return $collection->updateMany([], ['$pull' => ['messages' => ['deleted_at' => ['$ne' => null]]]]);
        });
    }

    /**
     * @param Sequence $sequence
     *
     * @return SequenceMessage|null
     */
    public function getFirstSendableMessage(Sequence $sequence)
    {
        foreach ($sequence->messages as $temp) {
            if (is_null($temp->deleted_at)) {
                return $temp;
            }
        }

        return null;
    }

    /**
     * @param Sequence        $sequence
     * @param SequenceMessage $current
     *
     * @return SequenceMessage|null
     */
    public function getNextSendableMessage(Sequence $sequence, SequenceMessage $current)
    {
        $currentPassed = false;

        foreach ($sequence->messages as $temp) {
            if ($currentPassed && is_null($temp->deleted_at)) {
                return $temp;
            }
            if ($temp->id == $current->id) {
                $currentPassed = true;
            }
        }

        return null;
    }

    /**
     * @param Sequence $sequence
     * @param ObjectID $messageId
     * @return int|null
     *
     */
    public function getMessageIndexInSequence(Sequence $sequence, ObjectID $messageId)
    {
        foreach ($sequence->messages as $i => $temp) {
            if ($temp->id == $messageId) {
                return $i;
            }
        }

        return null;
    }
}
