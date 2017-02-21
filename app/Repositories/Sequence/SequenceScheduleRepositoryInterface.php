<?php namespace App\Repositories\Sequence;

use App\Models\Subscriber;
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
     * @param Subscriber $subscriber
     * @param array      $sequenceIds
     * @param array      $columns
     * @return Collection
     */
    public function pendingPerSubscriberInSequences(Subscriber $subscriber, array  $sequenceIds, array $columns = ['*']);
}
