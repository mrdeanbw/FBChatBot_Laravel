<?php namespace App\Repositories\Broadcast;

use App\Models\Bot;
use MongoDB\BSON\ObjectID;
use Illuminate\Support\Collection;
use App\Repositories\AssociatedWithBotRepositoryInterface;

interface BroadcastRepositoryInterface extends AssociatedWithBotRepositoryInterface
{

    const STATUS_PENDING = 0;
    const STATUS_RUNNING = 1;
    const STATUS_COMPLETED = 2;

    /**
     * Get list of sending-due broadcasts
     * @return Collection
     */
    public function getDueBroadcasts();

    /**
     * @param Bot      $bot
     * @param ObjectID $broadcastId
     * @param ObjectID $subscriberId
     */
    public function recordClick(Bot $bot, ObjectID $broadcastId, ObjectID $subscriberId);
}
