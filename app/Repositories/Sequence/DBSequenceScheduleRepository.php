<?php namespace App\Repositories\Sequence;

use App\Models\SequenceSchedule;
use App\Repositories\DBBaseRepository;

class DBSequenceScheduleRepository extends DBBaseRepository  implements SequenceScheduleRepositoryInterface
{

    public function model()
    {
        return SequenceSchedule::class;
    }
}
