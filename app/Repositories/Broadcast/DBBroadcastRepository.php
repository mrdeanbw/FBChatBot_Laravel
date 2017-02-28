<?php namespace App\Repositories\Broadcast;

use App\Models\Bot;
use Carbon\Carbon;
use App\Models\Broadcast;
use Illuminate\Support\Collection;
use App\Repositories\DBAssociatedWithBotRepository;
use MongoDB\BSON\ObjectID;

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
        $filter = [
            ['operator' => '=', 'key' => 'status', 'value' => 'pending'],
            ['operator' => '<=', 'key' => 'next_send_at', 'value' => Carbon::now()],
        ];

        return $this->getAll($filter);
    }

    /**
     * @todo one query if possible.
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
}
