<?php namespace App\Repositories\Sequence;

use MongoDB\BSON\ObjectID;
use Illuminate\Support\Collection;
use App\Repositories\BaseRepositoryInterface;

interface SequenceScheduleRepositoryInterface extends BaseRepositoryInterface
{

    /**
     * Get list of sending-due sequence message schedules
     *
     * @return Collection
     */
    public function getDue();

    /**
     * @param ObjectID $subscriberId
     * @param array    $sequenceIds
     * @param array    $columns
     * @return Collection
     */
    public function pendingPerSubscriberInSequences(ObjectID $subscriberId, array  $sequenceIds, array $columns = ['*']);
}
