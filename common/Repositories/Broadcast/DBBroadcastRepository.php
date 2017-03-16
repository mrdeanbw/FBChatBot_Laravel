<?php namespace Common\Repositories\Broadcast;

use Carbon\Carbon;
use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
use Common\Models\Broadcast;
use Illuminate\Support\Collection;
use Common\Repositories\DBAssociatedWithBotRepository;

class DBBroadcastRepository extends DBAssociatedWithBotRepository implements BroadcastRepositoryInterface
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
        $now = Carbon::now();

        return Broadcast::where(function ($query) use ($now) {
            $query->where('status', BroadcastRepositoryInterface::STATUS_PENDING)->where('send_at', '<=', $now);
        })->orWhere(function ($query) use ($now) {
            $query->where('schedules.status', BroadcastRepositoryInterface::STATUS_PENDING)->where('schedules.send_at', '<=', $now);
        })->get();
    }

    /**
     * @param Bot      $bot
     * @param ObjectID $broadcastId
     * @param ObjectID $subscriberId
     */
    public function recordClick(Bot $bot, ObjectID $broadcastId, ObjectID $subscriberId)
    {
        $pushed = Broadcast::where('bot_id', $bot->_id)->where('_id', $broadcastId)->push('subscriber_clicks', $subscriberId, true);

        $update = ['$inc' => ['stats.clicked.total' => 1]];
        if ($pushed) {
            $update['$inc']['stats.clicked.per_subscriber'] = 1;
        }

        $filter = [
            '$and' => [
                ['_id' => $broadcastId],
                ['bot_id' => $bot->_id],
            ]
        ];

        Broadcast::raw()->updateOne($filter, $update);
    }

    /**
     * @param Broadcast $broadcast
     * @return mixed
     */
    public function markAsRunning(Broadcast $broadcast)
    {
        $this->update($broadcast, ['status' => BroadcastRepositoryInterface::STATUS_RUNNING]);
    }

    /**
     * @param Broadcast $broadcast
     * @param int       $count
     */
    public function setTargetAudienceAndMarkAsCompleted(Broadcast $broadcast, $count)
    {
        $this->update($broadcast, [
            'remaining_target' => 0,
            'stats.target'     => $count,
            'status'           => BroadcastRepositoryInterface::STATUS_COMPLETED,
            'completed_at'     => Carbon::now(),
        ]);
    }
}
