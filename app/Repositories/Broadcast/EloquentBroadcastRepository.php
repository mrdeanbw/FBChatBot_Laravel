<?php namespace App\Repositories\Broadcast;

use App\Models\Page;
use App\Models\Broadcast;
use App\Models\Subscriber;
use DB;
use Illuminate\Support\Collection;

class EloquentBroadcastRepository implements BroadcastRepository
{

    /**
     * Get all broadcasts that
     * @param Page $page
     * @return Collection
     */
    public function getAllForPage(Page $page)
    {
        return $page->broadcasts;
    }

    /**
     * Find a broadcast by id
     * @param int  $id
     * @param Page $page
     * @return Broadcast|null
     */
    public function findByIdForPage($id, Page $page)
    {
        return $page->broadcasts()->find($id);
    }

    /**
     * Create a broadcast and associate it with a page.
     * @param array $data
     * @param Page  $page
     * @return Broadcast
     */
    public function createForPage(array $data, Page $page)
    {
        return $page->broadcasts()->create($data);
    }

    /**
     * Update a broadcast
     * @param Broadcast $broadcast
     * @param array     $data
     */
    public function update(Broadcast $broadcast, array $data)
    {
        $broadcast->update($data);
    }

    /**
     * Delete the broadcast schedules.
     * @param Broadcast $broadcast
     */
    public function deleteBroadcastSchedule(Broadcast $broadcast)
    {
        $broadcast->schedule()->delete();
    }

    /**
     * Create schedules for broadcast.
     * @param array     $schedule
     * @param Broadcast $broadcast
     */
    public function createBroadcastSchedule(array $schedule, Broadcast $broadcast)
    {
        $broadcast->schedule()->createMany($schedule);
    }

    /**
     * Delete a broadcast
     * @param Broadcast $broadcast
     */
    public function delete(Broadcast $broadcast)
    {
        $broadcast->delete();
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
}
