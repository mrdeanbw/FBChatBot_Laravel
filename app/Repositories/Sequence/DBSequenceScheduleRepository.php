<?php namespace App\Repositories\Sequence;

use App\Models\SequenceSchedule;
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

}
