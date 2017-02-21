<?php namespace App\Repositories\Sequence;

use App\Models\SequenceSchedule;
use App\Models\Subscriber;
use App\Repositories\DBBaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     * @param Subscriber $subscriber
     * @param array      $sequenceIds
     * @param array      $columns
     * @return Collection
     */
    public function pendingPerSubscriberInSequences(Subscriber $subscriber, array $sequenceIds, array $columns = ['*'])
    {
        $filterBy = [
            ['key' => 'sequence_id', 'operator' => 'in', 'value' => $sequenceIds],
            ['key' => 'subscriber_id', 'operator' => '=', 'value' => $subscriber->_id]
        ];

        return $this->getAll($filterBy, [], $columns);
    }
}
