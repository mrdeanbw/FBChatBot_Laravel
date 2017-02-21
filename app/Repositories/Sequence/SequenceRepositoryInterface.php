<?php namespace App\Repositories\Sequence;

use App\Models\Sequence;
use MongoDB\BSON\ObjectID;
use App\Models\SequenceMessage;
use App\Repositories\AssociatedWithBotRepositoryInterface;

interface SequenceRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

    /**
     * Create a message and attach it to sequence.
     *
     * @param Sequence        $sequence
     * @param SequenceMessage $message
     *
     * @return SequenceMessage
     */
    public function addMessageToSequence(Sequence $sequence, SequenceMessage $message);

    /**
     * Update a sequence message.
     *
     * @param Sequence        $sequence
     * @param SequenceMessage $message
     *
     * @return
     */
    public function updateSequenceMessage(Sequence $sequence, SequenceMessage $message);

    /**
     * Find a sequence message by ID
     *
     * @param ObjectID $id
     * @param Sequence $sequence
     *
     * @return SequenceMessage
     */
    public function findSequenceMessageById(ObjectID $id, Sequence $sequence);

    /**
     * Delete a sequence message.
     *
     * @param Sequence        $sequence
     * @param SequenceMessage $message
     */
    public function deleteSequenceMessage(Sequence $sequence, SequenceMessage $message);

    /**
     * Delete a sequence message.
     *
     * @param Sequence        $sequence
     * @param SequenceMessage $message
     */
    public function softDeleteSequenceMessage(Sequence $sequence, SequenceMessage $message);


    public function completelyDeleteSoftDeletedSequenceMessagesWithNoPeopleQueued();

    /**
     * @param Sequence $sequence
     *
     * @return SequenceMessage|null
     */
    public function getFirstSendableMessage(Sequence $sequence);

    /**
     * @param Sequence        $sequence
     * @param SequenceMessage $message
     *
     * @return mixed
     */
    public function getNextSendableMessage(Sequence $sequence, SequenceMessage $message);

    /**
     * @param Sequence $sequence
     * @param ObjectID $messageId
     * @return int|null
     */
    public function getMessageIndexInSequence(Sequence $sequence, ObjectID $messageId);
}
