<?php namespace App\Repositories\Broadcast;

use Carbon\Carbon;
use App\Models\Broadcast;
use Illuminate\Support\Collection;
use App\Repositories\DBAssociatedWithBotRepository;

class DBBroadcastBaseRepository extends DBAssociatedWithBotRepository implements BroadcastRepositoryInterface
{

    public function model()
    {
        return Broadcast::class;
    }

    /**
     * Get list of sending-due broadcasts
     * @return Collection
     */
    public function getDueBroadcasts()
    {
        $filter = [
            ['operator' => '=', 'key' => 'status', 'value' => 'pending'],
            ['operator' => '<=', 'key' => 'next_send_at', 'value' => Carbon::now()],
        ];

        return $this->getAll($filter);
    }
}
