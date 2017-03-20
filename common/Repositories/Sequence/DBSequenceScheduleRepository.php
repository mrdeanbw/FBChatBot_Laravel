<?php namespace Common\Repositories\Sequence;

use Carbon\Carbon;
use MongoDB\BSON\ObjectID;
use Illuminate\Support\Collection;
use Common\Models\SequenceSchedule;
use Common\Repositories\DBBaseRepository;

class DBSequenceScheduleRepository extends DBBaseRepository implements SequenceScheduleRepositoryInterface
{

    public function model()
    {
        return SequenceSchedule::class;
    }


    /**
     * Get list of sending-due sequence message schedules
     *
     * @return Collection
     */
    public function getDue()
    {
        $filterBy = [['key' => 'send_at', 'operator' => '<=', 'value' => Carbon::now()]];

        return $this->getAll($filterBy);
    }

    /**
     * @param ObjectID $subscriberId
     * @param array    $sequenceIds
     * @param array    $columns
     * @return Collection
     */
    public function pendingPerSubscriberInSequences(ObjectID $subscriberId, array $sequenceIds, array $columns = ['*'])
    {
        $filterBy = [
            ['key' => 'sequence_id', 'operator' => 'in', 'value' => $sequenceIds],
            ['key' => 'subscriber_id', 'operator' => '=', 'value' => $subscriberId]
        ];

        return $this->getAll($filterBy, [], $columns);
    }
}
