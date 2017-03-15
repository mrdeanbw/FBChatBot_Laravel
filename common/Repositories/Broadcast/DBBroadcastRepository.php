<?php namespace Common\Repositories\Broadcast;

use Carbon\Carbon;
use Common\Models\Bot;
use MongoDB\BSON\ObjectID;
use Common\Models\Broadcast;
use Illuminate\Support\Collection;
use Common\Models\BroadcastSchedule;
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
            'stats.target' => $count,
            'status'       => BroadcastRepositoryInterface::STATUS_COMPLETED,
            'completed_at' => Carbon::now(),
        ]);
    }

    /**
     * @param array     $dueSchedules
     * @param Broadcast $broadcast
     * @param int       $count
     * @return mixed
     */
    public function incrementTargetAudienceAndMarkSchedulesAsCompleted(array $dueSchedules, Broadcast $broadcast, $count)
    {
        $update = [];
        foreach ($broadcast->schedules as $i => $schedule) {
            if (in_array($schedule->utc_offset, $dueSchedules)) {
                $update["schedules.{$i}.status"] = BroadcastRepositoryInterface::STATUS_COMPLETED;
            }
        }

        if ($this->allBroadcastSchedulesAreProcessed($broadcast, $dueSchedules)) {
            $update['status'] = BroadcastRepositoryInterface::STATUS_COMPLETED;
            $update['completed_at'] = Carbon::now();
        }

        $this->update($broadcast, [
            '$inc' => ['stats.target' => $count],
            '$set' => $update
        ]);

    }

    /**
     * @param Broadcast $broadcast
     * @param array     $dueSchedules
     * @return bool
     */
    private function allBroadcastSchedulesAreProcessed(Broadcast $broadcast, array $dueSchedules)
    {
        return ! array_first($broadcast->schedules, function (BroadcastSchedule $schedule) use ($dueSchedules) {
            return ! in_array($schedule->utc_offset, $dueSchedules) && $schedule->status != BroadcastRepositoryInterface::STATUS_COMPLETED;
        });
    }
}
