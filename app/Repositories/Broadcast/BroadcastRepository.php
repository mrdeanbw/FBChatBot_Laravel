<?php namespace App\Repositories\Broadcast;

use App\Models\Bot;
use App\Models\Broadcast;
use App\Models\Subscriber;
use Illuminate\Support\Collection;
use App\Repositories\CommonRepositoryInterface;

interface BroadcastRepository extends CommonRepositoryInterface
{

    /**
     * Get all broadcasts that
     * @param Bot $page
     * @return Collection
     */
    public function getAllForBot(Bot $page);

    /**
     * Find a broadcast by id
     * @param int $id
     * @param Bot $page
     * @return Broadcast|null
     */
    public function findByIdForBot($id, Bot $page);

    /**
     * Create a broadcast and associate it with a page.
     * @param array $data
     * @param Bot   $page
     * @return Broadcast
     */
    public function createForPage(array $data, Bot $page);

    /**
     * Update a broadcast
     * @param Broadcast $broadcast
     * @param array     $data
     */
    public function update($broadcast, array $data);

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
    public function delete($broadcast);

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

    /**
     * Get list of sending-due broadcast schedules
     * @return Collection
     */
    public function getDueBroadcastSchedule();

    /**
     * Update a broadcast schedule
     * @param       $schedule
     * @param array $data
     */
    public function updateSchedule($schedule, array $data);

    /**
     * Does the broadcast still has unprocessed schedule?
     * @param Broadcast $broadcast
     * @return bool
     */
    public function broadcastHasIncompleteSchedule(Broadcast $broadcast);

    /**
     * Attach a subscriber to broadcast.
     * @param Broadcast  $broadcast
     * @param Subscriber $subscriber
     * @param array      $attributes
     * @param bool       $touch
     */
    public function attachSubscriber(Broadcast $broadcast, Subscriber $subscriber, array $attributes = [], $touch = true);
}
