<?php namespace App\Repositories\Sequence;

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
}
