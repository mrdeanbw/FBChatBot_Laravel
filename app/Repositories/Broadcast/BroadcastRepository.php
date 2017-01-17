<?php namespace App\Repositories\Broadcast;

use App\Models\Page;
use App\Models\Broadcast;
use App\Models\Subscriber;
use Illuminate\Support\Collection;

interface BroadcastRepository
{

    /**
     * Get all broadcasts that
     * @param Page $page
     * @return Collection
     */
    public function getAllForPage(Page $page);

    /**
     * Find a broadcast by id
     * @param int  $id
     * @param Page $page
     * @return Broadcast|null
     */
    public function findByIdForPage($id, Page $page);

    /**
     * Create a broadcast and associate it with a page.
     * @param array $data
     * @param Page  $page
     * @return Broadcast
     */
    public function createForPage(array $data, Page $page);

    /**
     * Update a broadcast
     * @param Broadcast $broadcast
     * @param array     $data
     */
    public function update(Broadcast $broadcast, array $data);

    /**
     * Delete the broadcast schedules.
     * @param Broadcast $broadcast
     */
    public function deleteBroadcastSchedule(Broadcast $broadcast);

    /**
     * Create schedules for broadcast.
     * @param array     $schedule
     * @param Broadcast $broadcast
     */
    public function createBroadcastSchedule(array $schedule, Broadcast $broadcast);

    /**
     * Delete a broadcast
     * @param Broadcast $broadcast
     */
    public function delete(Broadcast $broadcast);

    /**
     * @param Broadcast  $broadcast
     * @param Subscriber $subscriber
     * @param int        $incrementBy
     */
    public function updateBroadcastSubscriberClicks(Broadcast $broadcast, Subscriber $subscriber, $incrementBy);

    /**
     * @param Subscriber $subscriber
     * @param string     $dateTime
     */
    public function updateBroadcastSubscriberDeliveredAt(Subscriber $subscriber, $dateTime);

    /**
     * @param Subscriber $subscriber
     * @param string     $dateTime
     */
    public function updateBroadcastSubscriberReadAt(Subscriber $subscriber, $dateTime);

}
