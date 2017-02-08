<?php namespace App\Repositories\Broadcast;

use DB;
use Carbon\Carbon;
use App\Models\Bot;
use App\Models\Broadcast;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use App\Repositories\BaseDBRepository;

class DBBroadcastRepository extends BaseDBRepository implements BroadcastRepository
{

    public function model()
    {
        return Broadcast::class;
    }

    /**
     * Get all broadcasts that
     * @param Bot $bot
     * @return Collection
     */
    public function getAllForBot(Bot $bot)
    {
        return Broadcast::where('bot_id', $bot->id)->get();
    }

    /**
     * Find a broadcast by id
     * @param int $id
     * @param Bot $bot
     * @return Broadcast|null
     */
    public function findByIdForBot($id, Bot $bot)
    {
        return Broadcast::where('bot_id', $bot->id)->find($id);
    }

    /**
     * @param Broadcast  $broadcast
     * @param Subscriber $subscriber
     * @param int        $incrementBy
     */
    public function updateBroadcastSubscriberClicks(Broadcast $broadcast, Subscriber $subscriber, $incrementBy)
    {
        DB::statement("update `broadcast_subscriber` SET `clicks` = `clicks` + {$incrementBy} WHERE `subscriber_id` = {$subscriber->id} AND `broadcast_id` = {$broadcast->id}");
    }

    /**
     * @param Subscriber $subscriber
     * @param string     $dateTime
     */
    public function updateBroadcastSubscriberDeliveredAt(Subscriber $subscriber, $dateTime)
    {
        DB::statement("update `broadcast_subscriber` SET `delivered_at` = '{$dateTime}' WHERE `subscriber_id` = {$subscriber->id} AND `delivered_at` IS NULL AND `sent_at` <= '{$dateTime}'");
    }

    /**
     * @param Subscriber $subscriber
     * @param string     $dateTime
     */
    public function updateBroadcastSubscriberReadAt(Subscriber $subscriber, $dateTime)
    {
        DB::statement("update `broadcast_subscriber` SET `read_at` = '{$dateTime}' WHERE `subscriber_id` = {$subscriber->id} AND  `read_at` IS NULL AND `sent_at` <= '{$dateTime}'");
    }

    /**
     * Get list of sending-due broadcast schedules
     * @return Collection
     */
    public function getDueBroadcastSchedule()
    {
        return BroadcastSchedule::whereStatus('pending')->where('send_at', '<=', Carbon::now())->get();
    }

    /**
     * Update a broadcast schedule
     * @param BroadcastSchedule $schedule
     * @param array             $data
     */
    public function updateSchedule($schedule, array $data)
    {
        $schedule->update($data);
    }

    /**
     * Does the broadcast still has unprocessed schedule?
     * @param Broadcast $broadcast
     * @return bool
     */
    public function broadcastHasIncompleteSchedule(Broadcast $broadcast)
    {
        return $broadcast->schedule()->where('status', '!=', 'completed')->exists();
    }

    /**
     * Attach a subscriber to broadcast.
     * @param Broadcast  $broadcast
     * @param Subscriber $subscriber
     * @param array      $attributes
     * @param bool       $touch
     */
    public function attachSubscriber(Broadcast $broadcast, Subscriber $subscriber, array $attributes = [], $touch = true)
    {
        $broadcast->subscribers()->attach($subscriber, $attributes, $touch);
    }
}
